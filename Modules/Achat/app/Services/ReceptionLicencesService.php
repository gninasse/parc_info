<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Exceptions\ReceptionLicencesException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\ReceptionLicences;
use Modules\Achat\Models\TamponLicence;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Licence;

/**
 * D-13 — la réception des natures NON STOCKABLES (A-05, SFD §7.4).
 *
 * Une licence n'entre pas en magasin : elle devient une fiche du Parc
 * Informatique. Le wizard collecte les clés une à une dans un TAMPON
 * (sauvegardé à chaque saisie, IA-8 : une coupure réseau ne perd rien),
 * puis la finalisation crée les N licences EN UNE TRANSACTION.
 *
 * Les trois invariants du recueil :
 *
 *   - IA-7 atomicité : si la 20e licence échoue, les 19 premières
 *     n'existent pas — jamais de succès partiel affiché (ENF-FIA-04) ;
 *   - IA-8 tampon : persistant entre deux sessions, purgé à la finalisation
 *     comme à l'abandon (jamais une seconde source de vérité) ;
 *   - IA-9 garde logiciel : l'absence de logiciel rattaché bloque À
 *     L'OUVERTURE, pas à la 25e clé.
 */
class ReceptionLicencesService
{
    public const EVENEMENT_OUVERTURE = 'ouverture_reception_licences';

    public const EVENEMENT_FINALISATION = 'reception_licences';

    public const EVENEMENT_ABANDON = 'abandon_reception_licences';

    public const EVENEMENT_SERVICE_FAIT = 'service_fait';

    public function __construct(private readonly AchatReceptionService $receptions) {}

    /**
     * Ouvre (ou reprend) une session de saisie — pré-écran A-05.
     *
     * IA-9 : la garde du logiciel rattaché est ici, à l'ouverture. Reprendre
     * une session en cours plutôt qu'en créer une seconde évite deux tampons
     * concurrents sur la même ligne.
     *
     * @throws ReceptionLicencesException
     */
    public function ouvrir(LigneCommande $ligne, User $auteur, float $quantite): ReceptionLicences
    {
        $this->verifierLigneReceptionnable($ligne);

        $enCours = ReceptionLicences::query()
            ->enCours()
            ->where('ligne_commande_id', $ligne->id)
            ->first();

        if ($enCours !== null) {
            return $enCours;
        }

        if ($quantite <= 0 || $quantite > $ligne->reste) {
            throw ReceptionLicencesException::quantiteHorsReste($quantite, $ligne->reste);
        }

        return DB::transaction(function () use ($ligne, $auteur, $quantite) {
            $reception = ReceptionLicences::create([
                'ligne_commande_id' => $ligne->id,
                'quantite' => $quantite,
                'created_by' => $auteur->id,
            ]);

            activity('achat')
                ->performedOn($ligne->bonCommande)
                ->causedBy($auteur)
                ->withProperties([
                    'ligne' => $ligne->designation,
                    'quantite' => $quantite,
                ])
                ->log(self::EVENEMENT_OUVERTURE);

            return $reception;
        });
    }

    /**
     * Enregistre (ou met à jour) UNE clé du tampon — appelé à chaque Entrée.
     *
     * L'unicité est contrôlée à la saisie contre le tampon de la session ET
     * contre le parc : découvrir un doublon à la 25e clé serait cruel.
     *
     * @throws ReceptionLicencesException
     */
    public function saisirCle(
        ReceptionLicences $reception,
        string $cle,
        ?string $dateActivation = null,
        ?string $dateExpiration = null,
        ?int $tamponId = null,
    ): TamponLicence {
        $cle = trim($cle);

        if ($cle === '') {
            throw ReceptionLicencesException::cleVide();
        }

        if (! $reception->estEnCours()) {
            throw ReceptionLicencesException::sessionClose($reception->statut);
        }

        $this->verifierUniciteCle($reception, $cle, $tamponId);

        // Le tampon ne peut pas dépasser la quantité annoncée.
        $dejaSaisies = $reception->tampon()->count();

        if ($tamponId === null && $dejaSaisies >= (int) $reception->quantite) {
            throw ReceptionLicencesException::tamponComplet((int) $reception->quantite);
        }

        return DB::transaction(function () use ($reception, $cle, $dateActivation, $dateExpiration, $tamponId) {
            $attributs = [
                'cle' => $cle,
                'date_activation' => $dateActivation,
                'date_expiration' => $dateExpiration,
            ];

            if ($tamponId !== null) {
                $ligne = $reception->tampon()->findOrFail($tamponId);
                $ligne->update($attributs);

                return $ligne;
            }

            return $reception->tampon()->create($attributs);
        });
    }

    /**
     * Collage / import CSV : une clé par ligne, avec RAPPORT (SPEC_UX A-05).
     *
     * Les doublons et les vides ne font pas échouer l'import — ils sont
     * comptés et rapportés. Le magasinier colle ce qu'il a reçu du
     * fournisseur, l'écran lui dit ce qui a été retenu.
     *
     * @return array{acceptees: int, doublons: int, vides: int, hors_quantite: int}
     */
    public function importerEnMasse(ReceptionLicences $reception, string $texte, ?string $dateActivation = null): array
    {
        $rapport = ['acceptees' => 0, 'doublons' => 0, 'vides' => 0, 'hors_quantite' => 0];

        foreach (preg_split('/\r\n|\r|\n/', $texte) ?: [] as $ligneTexte) {
            $cle = trim((string) $ligneTexte);

            if ($cle === '') {
                $rapport['vides']++;

                continue;
            }

            try {
                $this->saisirCle($reception, $cle, $dateActivation);
                $rapport['acceptees']++;
            } catch (ReceptionLicencesException $e) {
                if ($e->getCode() === ReceptionLicencesException::CODE_TAMPON_COMPLET) {
                    $rapport['hors_quantite']++;
                } else {
                    $rapport['doublons']++;
                }
            }
        }

        return $rapport;
    }

    public function supprimerCle(ReceptionLicences $reception, int $tamponId): void
    {
        $reception->tampon()->findOrFail($tamponId)->delete();
    }

    /**
     * SW-03 — finalisation : LA transaction de D-13.
     *
     * Re-contrôles (complétude, unicité, reste sous verrou), création des N
     * licences ParcInfo, incrément du livré via AchatReceptionService (qui
     * recalcule le statut du BC), purge du tampon, session FINALISEE,
     * journal. Toute erreur annule l'ensemble : jamais 19 licences sur 20.
     *
     * @return array{licences: int, reception: ReceptionLicences}
     *
     * @throws ReceptionLicencesException
     */
    public function finaliser(ReceptionLicences $reception, User $auteur): array
    {
        if (! $reception->estEnCours()) {
            throw ReceptionLicencesException::sessionClose($reception->statut);
        }

        return DB::transaction(function () use ($reception, $auteur) {
            // Verrou sur la session : deux finalisations concurrentes se
            // sérialisent, la seconde voit FINALISEE et s'arrête.
            $reception = ReceptionLicences::query()->lockForUpdate()->findOrFail($reception->id);

            if (! $reception->estEnCours()) {
                throw ReceptionLicencesException::sessionClose($reception->statut);
            }

            $ligne = LigneCommande::query()->lockForUpdate()->findOrFail($reception->ligne_commande_id);
            $bon = BonCommande::query()->findOrFail($ligne->bon_commande_id);

            $this->verifierLigneReceptionnable($ligne);

            $tampon = $reception->tampon()->orderBy('id')->get();

            // Complétude : autant de clés que d'unités annoncées.
            if ($tampon->count() !== (int) $reception->quantite) {
                throw ReceptionLicencesException::tamponIncomplet(
                    (int) $reception->quantite - $tampon->count()
                );
            }

            // Re-contrôle d'unicité SOUS transaction : une clé a pu être
            // créée ailleurs pendant la saisie.
            $this->verifierUniciteFinale($tampon->pluck('cle')->all());

            // Reste sous verrou : la ligne a pu être servie entre-temps.
            if ((float) $reception->quantite > $ligne->reste) {
                throw ReceptionLicencesException::quantiteHorsReste(
                    (float) $reception->quantite,
                    $ligne->reste
                );
            }

            $article = Article::query()->find($ligne->article_id);

            foreach ($tampon as $cle) {
                Licence::create([
                    'logiciel_id' => $article->logiciel_id,
                    'cle_licence' => $cle->cle,
                    'date_acquisition' => $bon->date_document,
                    'date_activation' => $cle->date_activation,
                    'date_expiration' => $cle->date_expiration,
                    // Coût = PRIX FIGÉ de la ligne (jamais le prix courant).
                    'cout_unitaire' => $ligne->prix_unitaire_ht,
                    'cout_total' => $ligne->prix_unitaire_ht,
                    'fournisseur_id' => $bon->fournisseur_id,
                    'numero_contrat' => null,
                    'statut' => 'actif',
                    'actif' => true,
                    'notes' => "Reçue sur le bon de commande {$bon->numero_affiche}.",
                ]);
            }

            /*
             * Le livré passe par le MÊME service que les réceptions physiques
             * (AchatReceptionService) : un seul endroit incrémente le livré et
             * recalcule le statut du BC. La clé d'idempotence est la session
             * de réception, préfixée pour ne pas collisionner avec les
             * identifiants de bons d'entrée Stock.
             */
            $this->receptions->integrer(
                $bon->id,
                self::cleIdempotence($reception),
                [['article_id' => $ligne->article_id, 'quantite' => (float) $reception->quantite]],
                "Licences — session #{$reception->id}",
                $auteur->id,
            );

            // Le tampon a fini son office : les données vivent dans ParcInfo.
            $reception->tampon()->delete();

            $reception->forceFill([
                'statut' => ReceptionLicences::STATUT_FINALISEE,
                'finalisee_le' => now(),
            ])->save();

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties([
                    'ligne' => $ligne->designation,
                    'licences_creees' => $tampon->count(),
                    'session' => $reception->id,
                ])
                ->log(self::EVENEMENT_FINALISATION);

            return ['licences' => $tampon->count(), 'reception' => $reception->refresh()];
        });
    }

    /**
     * SW-05 — retour en arrière : la session est ABANDONNÉE, le tampon vidé,
     * l'acte tracé. Rien n'est créé, rien n'est incrémenté.
     */
    public function abandonner(ReceptionLicences $reception, User $auteur): ReceptionLicences
    {
        if (! $reception->estEnCours()) {
            throw ReceptionLicencesException::sessionClose($reception->statut);
        }

        return DB::transaction(function () use ($reception, $auteur) {
            $perdues = $reception->tampon()->count();

            $reception->tampon()->delete();
            $reception->forceFill(['statut' => ReceptionLicences::STATUT_ABANDONNEE])->save();

            activity('achat')
                ->performedOn($reception->ligne->bonCommande)
                ->causedBy($auteur)
                ->withProperties([
                    'ligne' => $reception->ligne->designation,
                    'cles_perdues' => $perdues,
                ])
                ->log(self::EVENEMENT_ABANDON);

            return $reception->refresh();
        });
    }

    /**
     * M-04 — constat de SERVICE FAIT (prestations, P0-A).
     *
     * Une prestation ne produit ni stock ni fiche : elle se solde d'un coup,
     * par une date et un commentaire. La ligne est alors intégralement
     * livrée — le reste tombe à zéro et le BC recalcule son statut.
     *
     * @throws ReceptionLicencesException
     */
    public function constaterServiceFait(
        LigneCommande $ligne,
        User $auteur,
        string $date,
        ?string $commentaire = null,
    ): LigneCommande {
        if (! $ligne->estPrestation()) {
            throw ReceptionLicencesException::pasUnePrestation($ligne->designation);
        }

        if ($ligne->estSoldee()) {
            throw ReceptionLicencesException::serviceDejaFait($ligne->designation);
        }

        return DB::transaction(function () use ($ligne, $auteur, $date, $commentaire) {
            $ligne = LigneCommande::query()->lockForUpdate()->findOrFail($ligne->id);
            $bon = BonCommande::query()->findOrFail($ligne->bon_commande_id);

            if (! in_array($bon->statut, BonCommande::STATUTS_RECEPTIONNABLES, true)) {
                throw ReceptionLicencesException::bonNonReceptionnable($bon->numero_affiche, $bon->statut_label);
            }

            $reste = $ligne->reste;

            $ligne->forceFill([
                'service_fait_le' => Carbon::parse($date),
                'service_fait_par' => $auteur->id,
                'service_fait_commentaire' => $commentaire,
            ])->save();

            // Le solde passe par le même service que tout le reste.
            $this->receptions->integrer(
                $bon->id,
                self::cleIdempotenceServiceFait($ligne),
                [['article_id' => $ligne->article_id, 'quantite' => $reste]],
                "Service fait — {$ligne->designation}",
                $auteur->id,
            );

            activity('achat')
                ->performedOn($bon)
                ->causedBy($auteur)
                ->withProperties([
                    'ligne' => $ligne->designation,
                    'date' => $date,
                    'commentaire' => $commentaire,
                ])
                ->log(self::EVENEMENT_SERVICE_FAIT);

            return $ligne->refresh();
        });
    }

    // ── Gardes ─────────────────────────────────────────────────────────────

    /**
     * IA-9 — le contrôle d'OUVERTURE : bon réceptionnable, ligne non soldée,
     * nature immatérielle, et logiciel rattaché pour une licence.
     *
     * @throws ReceptionLicencesException
     */
    private function verifierLigneReceptionnable(LigneCommande $ligne): void
    {
        $bon = $ligne->bonCommande;

        if (! in_array($bon->statut, BonCommande::STATUTS_RECEPTIONNABLES, true)) {
            throw ReceptionLicencesException::bonNonReceptionnable($bon->numero_affiche, $bon->statut_label);
        }

        if (! $ligne->estLicence()) {
            throw ReceptionLicencesException::pasUneLicence($ligne->designation);
        }

        if ($ligne->estSoldee()) {
            throw ReceptionLicencesException::ligneSoldee($ligne->designation);
        }

        $article = Article::query()->find($ligne->article_id);

        if ($article === null || $article->logiciel_id === null) {
            throw ReceptionLicencesException::logicielNonRattache(
                $article?->code ?? $ligne->designation,
                $article?->id
            );
        }
    }

    /** Unicité d'une clé : dans la session, puis dans tout le parc. */
    private function verifierUniciteCle(ReceptionLicences $reception, string $cle, ?int $tamponId): void
    {
        $dansLaSession = $reception->tampon()
            ->where('cle', $cle)
            ->when($tamponId !== null, fn ($query) => $query->where('id', '!=', $tamponId))
            ->first();

        if ($dansLaSession !== null) {
            throw ReceptionLicencesException::doublonDansLaSession($cle);
        }

        if (Licence::query()->where('cle_licence', $cle)->exists()) {
            throw ReceptionLicencesException::doublonDansLeParc($cle);
        }
    }

    /**
     * Re-contrôle final : doublons internes au tampon (défense en profondeur,
     * l'unicité SQL les empêche déjà) et clés apparues dans le parc pendant
     * la saisie.
     *
     * @param  list<string>  $cles
     */
    private function verifierUniciteFinale(array $cles): void
    {
        $internes = collect($cles)->duplicates();

        if ($internes->isNotEmpty()) {
            throw ReceptionLicencesException::doublonDansLaSession($internes->first());
        }

        $connue = Licence::query()->whereIn('cle_licence', $cles)->value('cle_licence');

        if ($connue !== null) {
            throw ReceptionLicencesException::doublonDansLeParc($connue);
        }
    }

    /**
     * Clés d'idempotence des intégrations immatérielles.
     *
     * `entree_id` référence des bons d'entrée Stock ; les réceptions
     * dématérialisées n'en ont pas. On dérive un identifiant hors de portée
     * des séquences Stock, stable et reproductible — rejouer une
     * finalisation retombe sur la même clé et n'incrémente rien.
     */
    public static function cleIdempotence(ReceptionLicences $reception): int
    {
        return 900_000_000 + $reception->id;
    }

    public static function cleIdempotenceServiceFait(LigneCommande $ligne): int
    {
        return 800_000_000 + $ligne->id;
    }
}
