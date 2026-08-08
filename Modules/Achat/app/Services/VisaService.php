<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Exceptions\TransitionInterditeException;
use Modules\Achat\Exceptions\ValidationRefuseeException;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;

/**
 * Le visa — cœur transactionnel du module (SFD §7.2, décision A11).
 *
 * La validation est l'instant où le bon devient un ENGAGEMENT : numéro
 * attribué sous verrou, montants et libellés dénormalisés, immutabilité. Tout
 * se joue dans UNE transaction : soit le bon ressort validé, numéroté et
 * journalisé, soit rien n'a changé.
 *
 * Les SIGNAUX de SW-02 (cumul fournisseur, écarts de prix, fournisseur récent,
 * auto-validation) sont assemblés ici et sont INFORMATIFS : leur présence ne
 * bloque jamais (SPEC_UX A-04). Le logiciel éclaire le validateur, il ne juge
 * pas à sa place. Ce qui bloque, en revanche : un statut autre que SOUMIS, un
 * article ou un fournisseur désactivé au Catalogue depuis la soumission.
 */
class VisaService
{
    /** Fenêtre du signal « fournisseur récent » (UX4-03). */
    public const FENETRE_FOURNISSEUR_RECENT_JOURS = 30;

    public const EVENEMENT_VALIDATION = 'validation';

    public function __construct(
        private readonly NumerotationService $numerotation,
        private readonly CalculMontantsService $montants,
        private readonly ReferencePrixService $referencePrix,
    ) {}

    /**
     * Les signaux de SW-02, assemblés pour l'écran AVANT la confirmation.
     *
     * @return array{
     *   cumul: array{nb_bc_mois: int, cumul_ttc_mois: float, rang_du_mois: int},
     *   fournisseur_recent: ?array{cree_le: string, anciennete_jours: int, premier_bc: bool},
     *   ecarts_prix: list<array{numero: int, designation: string, ecart_pct: float, reference: ?string}>,
     *   auto_validation: bool
     * }
     */
    public function signaux(BonCommande $bon, User $validateur): array
    {
        return [
            'cumul' => $this->cumulFournisseur($bon),
            'fournisseur_recent' => $this->fournisseurRecent($bon),
            'ecarts_prix' => $this->ecartsDePrix($bon),
            // UX4-07 : l'auto-validation n'est pas bloquée en v1, mais elle est
            // MARQUÉE — au Swal, sur la fiche, et au rapport Signaux.
            'auto_validation' => $bon->created_by !== null && $bon->created_by === $validateur->id,
        ];
    }

    /**
     * Valide le bon : LA transaction du module.
     *
     * Idempotente par nature (IA-5) : un bon déjà validé est renvoyé tel quel,
     * sans nouvelle écriture ni nouveau numéro — c'est le double clic, pas une
     * erreur. Toute autre situation hors SOUMIS est un vrai conflit (409).
     */
    public function valider(BonCommande $bon, User $validateur): BonCommande
    {
        return DB::transaction(function () use ($bon, $validateur) {
            /*
             * Verrou AVANT lecture d'état : deux validations simultanées se
             * sérialisent ici. La seconde relit un bon déjà VALIDE et ressort
             * par la branche idempotente, sans consommer de numéro.
             */
            $bon = BonCommande::query()->lockForUpdate()->findOrFail($bon->id);

            if ($bon->statut === BonCommande::STATUT_VALIDE) {
                return $bon; // double clic / rejeu : un seul effet (IA-5)
            }

            if ($bon->statut !== BonCommande::STATUT_SOUMIS) {
                throw TransitionInterditeException::pour(
                    $bon->numero_affiche,
                    $bon->statut,
                    BonCommande::STATUT_VALIDE
                );
            }

            /*
             * Re-contrôles au moment du visa (SFD §7.2) : le Catalogue a pu
             * bouger entre la soumission et la validation. Un référentiel
             * désactivé rend le refus EXPLICITE — l'utilisateur doit savoir
             * quoi faire, pas seulement que c'est refusé.
             */
            $this->verifierReferentiels($bon);

            // Les montants font foi une dernière fois avant l'immutabilité.
            $this->montants->recalculer($bon);

            $fournisseur = $bon->fournisseur;

            /*
             * Numéro sous verrou + engagement d'un seul mouvement : il
             * n'existe aucun instant où un bon non engagé porte un numéro
             * (CHECK chk_bc_numero_si_engage). La dénormalisation du libellé
             * fournisseur fige ce que le document dira toujours, même si le
             * référentiel change ensuite.
             */
            $bon->forceFill([
                'fournisseur_libelle' => $fournisseur?->raison_sociale,
                'valide_par' => $validateur->id,
                'valide_le' => now(),
            ]);

            $this->numerotation->attribuer($bon, $validateur->id);

            activity('achat')
                ->performedOn($bon)
                ->withProperties([
                    'numero' => $bon->numero,
                    'montant_ttc' => (float) $bon->montant_ttc,
                    'auto_validation' => $bon->created_by === $validateur->id,
                ])
                ->log(self::EVENEMENT_VALIDATION);

            return $bon->refresh();
        });
    }

    /**
     * Refus explicite si un référentiel a été désactivé depuis la soumission.
     */
    private function verifierReferentiels(BonCommande $bon): void
    {
        $fournisseur = Fournisseur::query()->find($bon->fournisseur_id);

        if ($fournisseur === null || ! $fournisseur->est_actif) {
            throw ValidationRefuseeException::fournisseurDesactive(
                $fournisseur?->raison_sociale ?? 'inconnu'
            );
        }

        $inactifs = Article::query()
            ->whereIn('id', $bon->lignes()->pluck('article_id')->filter()->all())
            ->where('est_actif', false)
            ->pluck('nom');

        if ($inactifs->isNotEmpty()) {
            throw ValidationRefuseeException::articlesDesactives($inactifs->all());
        }
    }

    /**
     * 📊 « 3ᵉ bon de ce fournisseur ce mois — cumul : 12,4 M FCFA » (UX2-08).
     * Le bon en cours de visa compte dans le rang : c'est le rang qu'il AURA.
     */
    private function cumulFournisseur(BonCommande $bon): array
    {
        $duMois = BonCommande::query()
            ->engages()
            ->horsRegularisation()
            ->where('fournisseur_id', $bon->fournisseur_id)
            ->whereBetween('date_document', [now()->startOfMonth(), now()->endOfMonth()])
            ->get(['montant_ttc']);

        return [
            'nb_bc_mois' => $duMois->count(),
            'cumul_ttc_mois' => round((float) $duMois->sum('montant_ttc'), 2),
            'rang_du_mois' => $duMois->count() + 1,
        ];
    }

    /**
     * ⚠ « Fournisseur créé il y a 6 jours — premier BC » (UX4-03). Nul si le
     * fournisseur est établi : un signal permanent n'est plus un signal.
     * Public : le bandeau de la fiche A-04 porte le même badge que le Swal.
     */
    public function fournisseurRecent(BonCommande $bon): ?array
    {
        $fournisseur = DB::table('catalogue_fournisseurs')
            ->where('id', $bon->fournisseur_id)
            ->first(['created_at']);

        if ($fournisseur?->created_at === null) {
            return null;
        }

        $creeLe = \Illuminate\Support\Carbon::parse($fournisseur->created_at);
        $anciennete = (int) $creeLe->diffInDays(now());

        if ($anciennete > self::FENETRE_FOURNISSEUR_RECENT_JOURS) {
            return null;
        }

        $dejaEngages = BonCommande::query()
            ->engages()
            ->where('fournisseur_id', $bon->fournisseur_id)
            ->count();

        return [
            'cree_le' => $creeLe->toDateString(),
            'anciennete_jours' => $anciennete,
            'premier_bc' => $dejaEngages === 0,
        ];
    }

    /**
     * ⚠ « Ligne 2 : +29 % vs dernier payé » — même définition de l'écart que
     * l'écran de saisie et le futur rapport Signaux (ReferencePrixService).
     *
     * @return list<array{numero: int, designation: string, ecart_pct: float, reference: ?string}>
     */
    private function ecartsDePrix(BonCommande $bon): array
    {
        $ecarts = [];

        foreach ($bon->lignes()->get() as $index => $ligne) {
            if ($ligne->article_id === null) {
                continue;
            }

            $ecart = $this->referencePrix->ecart($ligne->article_id, (float) $ligne->prix_unitaire_ht);

            if (! $ecart['depasse_seuil']) {
                continue;
            }

            $ecarts[] = [
                'numero' => $index + 1,
                'designation' => $ligne->designation,
                'ecart_pct' => $ecart['ecart_pct'],
                'reference' => $ecart['reference'],
            ];
        }

        return $ecarts;
    }
}
