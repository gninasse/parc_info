<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;

/**
 * PO-01 — « Décomposition du prix » (SPEC_UX A-03, décision A14).
 *
 * La référence affichée à l'acheteur doit être NON MANIPULABLE : c'est le
 * **dernier prix réellement payé** sur un bon engagé, et non le prix indicatif
 * du Catalogue, qu'un utilisateur peut ajuster à la hausse juste avant de
 * commander pour faire disparaître l'écart. Le prix indicatif reste affiché,
 * mais comme second point de comparaison.
 *
 * La mention « réf. modifiée le … » relit le journal du Catalogue : si le prix
 * indicatif a bougé récemment, l'acheteur doit le savoir au moment où il
 * l'utilise comme repère.
 */
class ReferencePrixService
{
    /** Nombre de bons pris en compte dans la moyenne (SPEC_UX A-03, PO-01). */
    public const PROFONDEUR_MOYENNE = 3;

    /** Une modification du prix indicatif reste « récente » ce nombre de jours. */
    public const FENETRE_MODIFICATION_JOURS = 30;

    public function __construct(private readonly AchatParametres $parametres) {}

    /**
     * Décomposition complète du prix d'un article.
     *
     * @return array{
     *   article_id: int,
     *   prix_indicatif: ?string,
     *   dernier_paye: ?array{prix: string, numero: ?string, date: ?string},
     *   moyenne_3_derniers: ?string,
     *   nb_bc_references: int,
     *   reference: ?string,
     *   origine_reference: string,
     *   seuil_ecart_pct: int,
     *   modification_recente: ?array{ancien: ?string, nouveau: ?string, date: string}
     * }
     */
    public function pour(int $articleId): array
    {
        $article = Article::query()->find($articleId);
        $lignes = $this->lignesEngagees($articleId);
        $dernier = $lignes->first();

        $prixIndicatif = $article?->prix_indicatif;
        $dernierPaye = $dernier === null ? null : $this->montant($dernier->prix_unitaire_ht);

        /*
         * La référence de comparaison est le dernier prix payé ; à défaut
         * seulement, le prix indicatif (UX4-02). L'origine est renvoyée pour
         * que l'interface dise laquelle elle affiche — une pilule d'écart
         * dont on ignore la base ne veut rien dire.
         */
        $reference = $dernierPaye ?? ($prixIndicatif !== null ? $this->montant($prixIndicatif) : null);

        return [
            'article_id' => $articleId,
            'prix_indicatif' => $prixIndicatif === null ? null : $this->montant($prixIndicatif),
            'dernier_paye' => $dernier === null ? null : [
                'prix' => $this->montant($dernier->prix_unitaire_ht),
                'numero' => $dernier->numero,
                'date' => $dernier->valide_le ? Carbon::parse($dernier->valide_le)->toDateString() : null,
            ],
            'moyenne_3_derniers' => $lignes->isEmpty()
                ? null
                : $this->montant($lignes->take(self::PROFONDEUR_MOYENNE)->avg('prix_unitaire_ht')),
            'nb_bc_references' => $lignes->count(),
            'reference' => $reference,
            'origine_reference' => $dernierPaye !== null ? 'dernier_paye' : ($reference !== null ? 'prix_indicatif' : 'aucune'),
            'seuil_ecart_pct' => $this->parametres->seuilEcartPrixPct(),
            'modification_recente' => $this->modificationRecenteDuPrixIndicatif($articleId),
        ];
    }

    /**
     * Écart en pourcentage d'un prix saisi par rapport à la référence, et
     * dépassement du seuil paramétré.
     *
     * Le calcul est fait ICI, côté serveur, et non en JavaScript : c'est la
     * même règle qui alimentera l'écran de saisie, le récapitulatif de
     * soumission et le rapport Signaux. Une seule définition de l'écart.
     *
     * @return array{ecart_pct: ?float, depasse_seuil: bool, reference: ?string}
     */
    public function ecart(int $articleId, float $prixSaisi): array
    {
        $decomposition = $this->pour($articleId);
        $reference = $decomposition['reference'] === null ? null : (float) $decomposition['reference'];

        // Sans référence, ou face à une référence nulle, aucun écart n'est
        // calculable : on ne prétend pas à un « +100 % » qui n'a pas de sens.
        if ($reference === null || $reference <= 0.0) {
            return ['ecart_pct' => null, 'depasse_seuil' => false, 'reference' => $decomposition['reference']];
        }

        $ecart = round(($prixSaisi - $reference) / $reference * 100, 1);

        return [
            'ecart_pct' => $ecart,
            // Seul un écart À LA HAUSSE alerte : payer moins cher que la
            // dernière fois n'est pas un signal de vigilance.
            'depasse_seuil' => $ecart >= $decomposition['seuil_ecart_pct'],
            'reference' => $decomposition['reference'],
        ];
    }

    /**
     * Lignes de cet article sur des bons engagés, du plus récent au plus
     * ancien. Les brouillons et les bons soumis en sont exclus : un prix qui
     * n'a pas été validé n'a jamais été payé.
     */
    private function lignesEngagees(int $articleId)
    {
        return LigneCommande::query()
            ->join('achat_bons_commande', 'achat_bons_commande.id', '=', 'achat_lignes_commande.bon_commande_id')
            ->where('achat_lignes_commande.article_id', $articleId)
            ->whereIn('achat_bons_commande.statut', BonCommande::STATUTS_ENGAGES)
            ->orderByDesc('achat_bons_commande.valide_le')
            ->orderByDesc('achat_bons_commande.id')
            ->get([
                'achat_lignes_commande.prix_unitaire_ht',
                'achat_bons_commande.numero',
                'achat_bons_commande.valide_le',
            ]);
    }

    /**
     * Dernière modification du prix indicatif au Catalogue, si elle date de
     * moins de 30 jours (A14). Lue dans le journal d'activité commun : le
     * module Achat n'écrit rien dans le Catalogue et se contente de le lire.
     *
     * @return array{ancien: ?string, nouveau: ?string, date: string}|null
     */
    private function modificationRecenteDuPrixIndicatif(int $articleId): ?array
    {
        $entree = DB::table('activity_log')
            ->where('subject_type', Article::class)
            ->where('subject_id', $articleId)
            ->where('event', 'updated')
            ->where('created_at', '>=', now()->subDays(self::FENETRE_MODIFICATION_JOURS))
            ->orderByDesc('id')
            ->get(['properties', 'created_at'])
            // Le journal enregistre toutes les modifications de l'article :
            // on ne retient que celles qui ont touché le prix.
            ->first(function ($ligne) {
                $proprietes = json_decode((string) $ligne->properties, true);

                return isset($proprietes['attributes']['prix_indicatif'])
                    && array_key_exists('prix_indicatif', $proprietes['old'] ?? [])
                    && $proprietes['attributes']['prix_indicatif'] != ($proprietes['old']['prix_indicatif'] ?? null);
            });

        if ($entree === null) {
            return null;
        }

        $proprietes = json_decode((string) $entree->properties, true);

        return [
            'ancien' => $this->montantOuNull($proprietes['old']['prix_indicatif'] ?? null),
            'nouveau' => $this->montantOuNull($proprietes['attributes']['prix_indicatif'] ?? null),
            'date' => Carbon::parse($entree->created_at)->toDateString(),
        ];
    }

    /** Montant en chaîne à 2 décimales — même convention que l'API Catalogue. */
    private function montant(mixed $valeur): string
    {
        return number_format((float) $valeur, 2, '.', '');
    }

    private function montantOuNull(mixed $valeur): ?string
    {
        return $valeur === null ? null : $this->montant($valeur);
    }
}
