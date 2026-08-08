<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\ArticleInactifException;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Exceptions\ValidationEntreeException;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\TamponEquipement;

/**
 * Validation d'un bon d'entrée (S3/D10/D12 — I9/I11/I14) : préconditions
 * revérifiées transactionnellement, puis UNE transaction pour tout —
 * fiches ParcInfo (SerialisationService), mouvements (MouvementService),
 * rattachements, dénormalisations, numéro (NumerotationService), purge du
 * tampon, transition → VALIDE. Idempotente par jeton.
 *
 * Raccordement PRQ-05 (RACCORDEMENT §3) : si l'entrée est liée à un bon de
 * commande, l'intégration Achat se fait DANS la même transaction — appel de
 * service interne, pas d'événement asynchrone (ENF-TEC-04). Le re-contrôle
 * du reste sous verrou appartient à AchatReceptionService : s'il refuse,
 * TOUTE la validation Stock échoue et rien n'est écrit nulle part.
 */
class ValiderEntreeService
{
    public function __construct(
        private MouvementService $mouvements,
        private NumerotationService $numerotation,
        private SerialisationService $serialisation,
        private AffectationService $affectations,
    ) {}

    /**
     * @return array le récapitulatif chiffré (SW-VALIDER-ENT)
     */
    public function valider(Entree $entree, ?string $jeton = null, ?int $userId = null): array
    {
        // ── Idempotence par jeton (I9) : rejouer renvoie le premier résultat ──
        if ($entree->estValide()) {
            if ($jeton !== null && $entree->jeton_validation === $jeton) {
                return array_merge($this->recapDocument($entree), ['deja_valide' => true]);
            }

            throw TransitionInterditeException::pour($entree->numero_affiche, $entree->statut, Entree::STATUT_VALIDE);
        }

        if (! $entree->canValidate()) {
            throw TransitionInterditeException::pour($entree->numero_affiche, $entree->statut, Entree::STATUT_VALIDE);
        }

        $lignes = $entree->lignes()->with(['article', 'equipement', 'tampons'])->orderBy('id')->get();

        if ($lignes->isEmpty()) {
            throw ValidationEntreeException::sansLigne();
        }

        return DB::transaction(function () use ($entree, $lignes, $jeton, $userId) {
            // Préconditions I11/I14 revérifiées SOUS transaction : rien n'est
            // écrit si l'une échoue, le statut reste inchangé.
            $this->verifierPreconditions($entree, $lignes);

            $fichesCreees = 0;
            $rattachements = 0;

            foreach ($lignes as $ligne) {
                match (true) {
                    $ligne->equipement_id !== null => $rattachements += $this->rattacherUnite($entree, $ligne, $userId),
                    $ligne->article->nature === Article::NATURE_EQUIPEMENT => $fichesCreees += $this->serialiserLigne($entree, $ligne, $userId),
                    default => $this->entreeQuantitative($entree, $ligne, $userId),
                };
            }

            $this->denormaliserBeneficiaire($entree);

            $numero = $this->numerotation->attribuer($entree);

            /*
             * Raccordement PRQ-05 : notification transactionnelle à Achat,
             * APRÈS le numéro (la trace côté Achat porte « ENT-2026-0034 »,
             * pas « Brouillon #58 ») et AVANT le commit — si Achat refuse
             * (reste insuffisant sous verrou, bon annulé entre-temps), tout
             * ce qui précède est annulé avec.
             */
            $this->notifierAchat($entree, $lignes, $numero, $userId);

            // Purge du tampon (les références vivent désormais sur les fiches)
            TamponEquipement::query()
                ->whereIn('ligne_entree_id', $lignes->pluck('id'))
                ->delete();

            $entree->valider($userId);
            $entree->forceFill(['jeton_validation' => $jeton])->save();

            activity('stock')
                ->performedOn($entree)
                ->withProperties([
                    'numero' => $numero,
                    'fiches_creees' => $fichesCreees,
                    'rattachements' => $rattachements,
                ])
                ->log('validation_entree');

            return $this->recapDocument($entree->refresh());
        });
    }

    /** Récapitulatif chiffré (SW-VALIDER-ENT) — recalculable après coup (I9). */
    public function recapDocument(Entree $entree): array
    {
        $lignes = $entree->lignes()->with('article:id,nature,prix_indicatif')->get();

        $quantitatives = $lignes->filter(fn (LigneEntree $l) => $l->article_id !== null && $l->article->nature !== Article::NATURE_EQUIPEMENT);
        $modeles = $lignes->filter(fn (LigneEntree $l) => $l->article_id !== null && $l->article->nature === Article::NATURE_EQUIPEMENT);
        $rattachements = $lignes->whereNotNull('equipement_id');

        return [
            'numero' => $entree->numero,
            'magasin' => $entree->magasin?->libelle ?? $entree->magasin?->code,
            'fournisseur' => $entree->fournisseur?->raison_sociale,
            'articles' => $quantitatives->count(),
            'unites_articles' => (float) $quantitatives->sum('quantite'),
            'fiches_creees' => (int) $modeles->sum('quantite'),
            'rattachements' => $rattachements->count(),
            'total_fcfa' => (float) $lignes->sum(fn (LigneEntree $l) => (float) $l->quantite * (float) ($l->cout_unitaire ?? 0)),
        ];
    }

    // ── Préconditions (I11/I14) ────────────────────────────────────────────

    private function verifierPreconditions(Entree $entree, Collection $lignes): void
    {
        // Articles actifs (entrée = acquisition)
        foreach ($lignes->whereNotNull('article_id') as $ligne) {
            if (! $ligne->article->est_actif) {
                throw ArticleInactifException::pour($ligne->article->nom);
            }
        }

        // I14 — tampon complet ligne par ligne
        $modeles = $lignes->filter(fn (LigneEntree $l) => $l->article_id !== null && $l->article->nature === Article::NATURE_EQUIPEMENT);
        $manquantes = 0;

        foreach ($modeles as $ligne) {
            $saisis = $ligne->tampons->whereNotNull('numero_serie')->count();
            $manquantes += max(0, (int) $ligne->quantite - $saisis);

            if ($ligne->tampons->count() !== (int) $ligne->quantite) {
                $manquantes = max($manquantes, 1); // tampon jamais généré ou incohérent
            }
        }

        if ($manquantes > 0) {
            throw ValidationEntreeException::tamponIncomplet($manquantes);
        }

        // I11 — doublons revérifiés transactionnellement
        $series = $modeles->flatMap(fn (LigneEntree $l) => $l->tampons->pluck('numero_serie'))->filter();

        $internes = $series->duplicates();
        if ($internes->isNotEmpty()) {
            throw ValidationEntreeException::doublonsInternes($internes->unique()->values()->all());
        }

        if ($series->isNotEmpty()) {
            $connu = Equipement::query()->whereIn('numero_serie', $series)->first();

            if ($connu !== null) {
                throw ValidationEntreeException::serieConnue($connu->numero_serie, $connu->code_inventaire);
            }
        }

        // Rattachements re-contrôlés : unité disponible, retour motivé
        foreach ($lignes->whereNotNull('equipement_id') as $ligne) {
            $unite = $ligne->equipement;

            if (EquipementMagasin::query()->where('equipement_id', $unite->id)->exists()) {
                throw ValidationEntreeException::uniteIndisponible($unite->code_inventaire, 'déjà rattachée à un magasin — c\'est un transfert');
            }

            if ($entree->nature === Entree::NATURE_RETOUR) {
                if (blank($entree->observation) && blank($entree->observation_type)) {
                    throw ValidationEntreeException::motifRetourRequis();
                }
            } elseif (! str_starts_with((string) $unite->statut, 'en_stock')) {
                throw ValidationEntreeException::uniteIndisponible($unite->code_inventaire, 'statut « '.$unite->statut.' »');
            }
        }
    }

    // ── Écritures par type de ligne ────────────────────────────────────────

    private function entreeQuantitative(Entree $entree, LigneEntree $ligne, ?int $userId): void
    {
        $this->mouvements->entree([
            'entree_id' => $entree->id,
            'magasin_id' => $entree->magasin_id,
            'article_id' => $ligne->article_id,
            'quantite' => (float) $ligne->quantite,
            'cout_unitaire' => $ligne->cout_unitaire,
            'created_by' => $userId,
        ]);
    }

    /** D10 : une fiche + un mouvement unitaire + un rattachement par n° de série. */
    private function serialiserLigne(Entree $entree, LigneEntree $ligne, ?int $userId): int
    {
        $creees = 0;

        foreach ($ligne->tampons as $tampon) {
            $fiche = $this->serialisation->creerFiche(
                $ligne->article,
                $tampon->numero_serie,
                $ligne->cout_unitaire !== null ? (float) $ligne->cout_unitaire : null,
                $tampon->etat
            );

            // SFD §6.2 : equipement_id renseigné à la validation (purge en fin de transaction)
            $tampon->update(['equipement_id' => $fiche->id]);

            $this->mouvements->entree([
                'entree_id' => $entree->id,
                'magasin_id' => $entree->magasin_id,
                'equipement_id' => $fiche->id,
                'quantite' => 1,
                'cout_unitaire' => $ligne->cout_unitaire,
                'created_by' => $userId,
            ]);

            EquipementMagasin::create([
                'equipement_id' => $fiche->id,
                'magasin_id' => $entree->magasin_id,
                'date_rattachement' => now(),
            ]);

            $creees++;
        }

        return $creees;
    }

    private function rattacherUnite(Entree $entree, LigneEntree $ligne, ?int $userId): int
    {
        // Retour d'équipement : clôture d'affectation + retour « en stock » (D8)
        if ($entree->nature === Entree::NATURE_RETOUR) {
            $motif = $entree->observation
                ?: config('stock.motifs_observation_entree')[$entree->observation_type] ?? 'Retour';

            $this->affectations->retournerEnStock($ligne->equipement, $motif);
        }

        $this->mouvements->entree([
            'entree_id' => $entree->id,
            'magasin_id' => $entree->magasin_id,
            'equipement_id' => $ligne->equipement_id,
            'quantite' => 1,
            'cout_unitaire' => $ligne->cout_unitaire,
            'created_by' => $userId,
        ]);

        EquipementMagasin::create([
            'equipement_id' => $ligne->equipement_id,
            'magasin_id' => $entree->magasin_id,
            'date_rattachement' => now(),
        ]);

        return 1;
    }

    /** Libellé du bénéficiaire d'origine figé à la validation (retours). */
    private function denormaliserBeneficiaire(Entree $entree): void
    {
        if ($entree->beneficiaire_type === null) {
            return;
        }

        $libelle = match ($entree->beneficiaire_type) {
            'direction' => $entree->beneficiaireDirection?->libelle,
            'service' => $entree->beneficiaireService?->libelle,
            'unite' => $entree->beneficiaireUnite?->libelle,
            'poste' => $entree->beneficiairePoste?->libelle,
            'local' => $entree->beneficiaireLocal?->libelle,
            'employe' => $entree->beneficiaireEmploye ? trim($entree->beneficiaireEmploye->prenom.' '.$entree->beneficiaireEmploye->nom) : null,
            default => null,
        };

        if ($libelle !== null) {
            $entree->forceFill(['beneficiaire_libelle' => $libelle])->save();
        }
    }

    // ── Raccordement PRQ-05 : la notification transactionnelle ─────────────

    /**
     * Intègre la réception au bon de commande lié — DANS la transaction de
     * validation (RACCORDEMENT §3, étape 4).
     *
     * AchatReceptionService re-contrôle le reste SOUS VERROU ligne à ligne :
     * deux bons d'entrée concurrents sur le même reste ne peuvent pas le
     * dépasser (IA-4), et un rejeu de la même entrée n'incrémente rien
     * (idempotence par entree_id, IA-5). Son refus est traduit en
     * ValidationEntreeException : la validation Stock échoue EN ENTIER,
     * mouvements et fiches compris.
     *
     * Seules les lignes d'ARTICLES notifient : les rattachements d'unités
     * existantes ne livrent pas une commande (et la Request les refuse sur
     * un bon lié).
     */
    private function notifierAchat(Entree $entree, Collection $lignes, string $numero, ?int $userId): void
    {
        if ($entree->bon_commande_id === null) {
            return;
        }

        $recues = $lignes
            ->whereNotNull('article_id')
            ->map(fn (LigneEntree $ligne) => [
                'article_id' => (int) $ligne->article_id,
                'quantite' => (float) $ligne->quantite,
            ])
            ->values()
            ->all();

        if ($recues === []) {
            return;
        }

        try {
            app(\Modules\Achat\Services\AchatReceptionService::class)->integrer(
                $entree->bon_commande_id,
                $entree->id,
                $recues,
                $numero,
                $userId
            );
        } catch (\Modules\Achat\Exceptions\AchatReceptionException $e) {
            // 422 ligne à ligne, message métier d'Achat conservé tel quel :
            // il dit QUELLE ligne dépasse et propose la sortie.
            throw new ValidationEntreeException($e->getMessage());
        }
    }
}
