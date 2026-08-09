<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Achat\Models\BonCommande;

/**
 * LA source unique des chiffres du module (D-16, critère de cohérence
 * contractuelle : dashboard = rapport = export).
 *
 * Tout agrégat affiché quelque part vient d'ICI. Deux calculs du même
 * chiffre finissent toujours par diverger — un arrondi, un périmètre, une
 * borne de date — et le jour où le tableau de bord annonce 12,4 M et
 * l'export 12,1 M, plus personne ne fait confiance à l'application.
 *
 * Deux règles transverses :
 *
 *   - les RÉGULARISATIONS sont exclues par défaut PARTOUT (elles documentent
 *     le passé, elles ne sont pas de l'activité d'achat) — chaque méthode
 *     accepte `$avecRegularisations` pour la case à cocher de l'écran ;
 *   - les montants sont arrondis au CENTIME une seule fois, en sortie.
 */
class StatistiquesAchatService
{
    /** Bons ENGAGÉS (validé, partiel, livré, clôturé) : la dépense réelle. */
    public function requeteEngages(bool $avecRegularisations = false, ?string $du = null, ?string $au = null)
    {
        $query = BonCommande::query()->engages();

        if (! $avecRegularisations) {
            $query->horsRegularisation();
        }

        if ($du !== null) {
            $query->whereDate('date_document', '>=', $du);
        }

        if ($au !== null) {
            $query->whereDate('date_document', '<=', $au);
        }

        return $query;
    }

    /**
     * État des bons de commande : effectifs et montants par statut.
     *
     * @return list<array{statut: string, statut_label: string, nombre: int, montant_ht: float, montant_ttc: float}>
     */
    public function etatDesBons(bool $avecRegularisations = false, ?string $du = null, ?string $au = null, ?int $fournisseurId = null): array
    {
        $query = BonCommande::query();

        if (! $avecRegularisations) {
            $query->horsRegularisation();
        }

        if ($du !== null) {
            $query->whereDate('date_document', '>=', $du);
        }

        if ($au !== null) {
            $query->whereDate('date_document', '<=', $au);
        }

        if ($fournisseurId !== null) {
            $query->where('fournisseur_id', $fournisseurId);
        }

        $lignes = $query
            ->groupBy('statut')
            ->selectRaw('statut, COUNT(*) AS nombre, COALESCE(SUM(montant_ht), 0) AS ht, COALESCE(SUM(montant_ttc), 0) AS ttc')
            ->get()
            ->keyBy('statut');

        // Tous les statuts sont présents, même à zéro : un statut absent se
        // lit comme « pas encore calculé », pas comme « aucun ».
        return collect(BonCommande::STATUTS)
            ->map(fn (string $statut) => [
                'statut' => $statut,
                'statut_label' => BonCommande::STATUT_LABELS[$statut],
                'nombre' => (int) ($lignes[$statut]->nombre ?? 0),
                'montant_ht' => round((float) ($lignes[$statut]->ht ?? 0), 2),
                'montant_ttc' => round((float) ($lignes[$statut]->ttc ?? 0), 2),
            ])
            ->all();
    }

    /**
     * Dépenses par fournisseur (bons engagés).
     *
     * @return list<array{fournisseur: string, nombre: int, montant_ht: float, montant_ttc: float}>
     */
    public function depensesParFournisseur(bool $avecRegularisations = false, ?string $du = null, ?string $au = null): array
    {
        return $this->requeteEngages($avecRegularisations, $du, $au)
            ->leftJoin('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
            ->groupBy('achat_bons_commande.fournisseur_id', 'catalogue_fournisseurs.raison_sociale')
            ->selectRaw('catalogue_fournisseurs.raison_sociale AS fournisseur')
            ->selectRaw('COUNT(*) AS nombre')
            ->selectRaw('COALESCE(SUM(achat_bons_commande.montant_ht), 0) AS ht')
            ->selectRaw('COALESCE(SUM(achat_bons_commande.montant_ttc), 0) AS ttc')
            ->orderByDesc('ttc')
            ->get()
            ->map(fn ($ligne) => [
                'fournisseur' => $ligne->fournisseur ?? '— fournisseur supprimé —',
                'nombre' => (int) $ligne->nombre,
                'montant_ht' => round((float) $ligne->ht, 2),
                'montant_ttc' => round((float) $ligne->ttc, 2),
            ])
            ->all();
    }

    /**
     * D-24 — dépenses par IMPUTATION COMPTABLE.
     *
     * Calculée sur le compte FIGÉ à la ligne (et non sur celui de l'article
     * aujourd'hui) : c'est la différence entre « ce qui a été imputé » et
     * « ce qu'on imputerait maintenant ». Réaffecter un article à un autre
     * compte au Catalogue ne doit pas réécrire un exercice clos.
     *
     * Les lignes sans imputation ne sont **jamais masquées** : elles
     * apparaissent sous « Non imputé ». Un état qui tairait ce qu'il ne sait
     * pas classer laisserait croire que le total est complet, et personne
     * n'irait chercher les 30 % manquants.
     *
     * @return list<array{compte: string, nombre_lignes: int, montant_ht: float, montant_ttc: float, non_impute: bool}>
     */
    public function depensesParImputation(bool $avecRegularisations = false, ?string $du = null, ?string $au = null): array
    {
        if (! Schema::hasColumn('achat_lignes_commande', 'compte_comptable')) {
            return [];
        }

        $bons = $this->requeteEngages($avecRegularisations, $du, $au)->select('achat_bons_commande.id');

        return DB::table('achat_lignes_commande')
            ->whereIn('bon_commande_id', $bons)
            ->groupBy('compte_comptable')
            ->selectRaw('compte_comptable')
            ->selectRaw('COUNT(*) AS nombre_lignes')
            ->selectRaw('COALESCE(SUM(quantite * prix_unitaire_ht), 0) AS ht')
            // La TVA est portée par la ligne : le TTC se recompose ici plutôt
            // que d'être lu sur le bon, dont les lignes peuvent relever de
            // comptes différents. Division par 100.0 — en entier, elle vaut 0.
            ->selectRaw('COALESCE(SUM(quantite * prix_unitaire_ht * (1 + taux_tva / 100.0)), 0) AS ttc')
            ->orderByDesc('ht')
            ->get()
            ->map(function ($ligne) {
                $compte = $ligne->compte_comptable;
                $nonImpute = $compte === null || trim((string) $compte) === '';

                return [
                    'compte' => $nonImpute ? 'Non imputé' : $compte,
                    'nombre_lignes' => (int) $ligne->nombre_lignes,
                    'montant_ht' => round((float) $ligne->ht, 2),
                    'montant_ttc' => round((float) $ligne->ttc, 2),
                    'non_impute' => $nonImpute,
                ];
            })
            ->all();
    }

    /**
     * Dépenses par catégorie du Catalogue — calculées sur les LIGNES (une
     * commande peut mélanger des catégories, l'imputer entière à l'une
     * d'elles serait faux).
     *
     * @return list<array{categorie: string, nombre_lignes: int, montant_ht: float}>
     */
    public function depensesParCategorie(bool $avecRegularisations = false, ?string $du = null, ?string $au = null): array
    {
        $bons = $this->requeteEngages($avecRegularisations, $du, $au)->select('achat_bons_commande.id');

        return DB::table('achat_lignes_commande')
            ->whereIn('achat_lignes_commande.bon_commande_id', $bons)
            ->leftJoin('catalogue_articles', 'catalogue_articles.id', '=', 'achat_lignes_commande.article_id')
            ->leftJoin('catalogue_categories', 'catalogue_categories.id', '=', 'catalogue_articles.categorie_id')
            ->groupBy('catalogue_categories.id', 'catalogue_categories.libelle')
            ->selectRaw('catalogue_categories.libelle AS categorie')
            ->selectRaw('COUNT(*) AS nombre_lignes')
            ->selectRaw('COALESCE(SUM(achat_lignes_commande.quantite * achat_lignes_commande.prix_unitaire_ht), 0) AS ht')
            ->orderByDesc('ht')
            ->get()
            ->map(fn ($ligne) => [
                'categorie' => $ligne->categorie ?? '— sans catégorie —',
                'nombre_lignes' => (int) $ligne->nombre_lignes,
                'montant_ht' => round((float) $ligne->ht, 2),
            ])
            ->all();
    }

    /**
     * Évolution sur 12 mois glissants — LES MOIS VIDES COMPRIS, en ordre
     * chronologique. Un graphique qui saute les mois sans dépense donne
     * l'illusion d'une activité continue.
     *
     * @return list<array{mois: string, libelle: string, nombre: int, montant_ht: float, montant_ttc: float}>
     */
    public function evolutionDouzeMois(bool $avecRegularisations = false, ?Carbon $finPeriode = null): array
    {
        $fin = ($finPeriode ?? now())->copy()->endOfMonth();
        $debut = $fin->copy()->subMonths(11)->startOfMonth();

        $bons = $this->requeteEngages($avecRegularisations)
            ->whereBetween('date_document', [$debut, $fin])
            ->get(['date_document', 'montant_ht', 'montant_ttc']);

        // Regroupement en PHP : `strftime`/`to_char` divergent entre SQLite et
        // PostgreSQL, et un format de mois n'a pas à dépendre du pilote.
        $parMois = $bons->groupBy(fn ($bon) => $bon->date_document->format('Y-m'));

        $mois = [];
        /*
         * On itère sur le PREMIER du mois, pas sur la date de fin : partir du
         * 31 et appeler addMonth() donne le 1er mars pour le 31 janvier
         * (débordement), ce qui SAUTE février — on n'obtenait que 11 mois sur
         * 12 selon le jour d'exécution. Un graphique amputé d'un mois est
         * pire qu'un graphique absent : il se lit sans qu'on le soupçonne.
         */
        $curseur = $debut->copy()->startOfMonth();

        for ($index = 0; $index < 12; $index++) {
            $cle = $curseur->format('Y-m');
            $duMois = $parMois->get($cle, collect());

            $mois[] = [
                'mois' => $cle,
                'libelle' => $curseur->locale('fr')->isoFormat('MMM YYYY'),
                'nombre' => $duMois->count(),
                'montant_ht' => round((float) $duMois->sum(fn ($bon) => (float) $bon->montant_ht), 2),
                'montant_ttc' => round((float) $duMois->sum(fn ($bon) => (float) $bon->montant_ttc), 2),
            ];

            $curseur->addMonthNoOverflow();
        }

        return $mois;
    }

    /**
     * Le montant ENGAGÉ d'une période — le chiffre du tableau de bord.
     *
     * @return array{ht: float, ttc: float, nombre: int}
     */
    public function engageSurPeriode(?string $du = null, ?string $au = null, bool $avecRegularisations = false): array
    {
        $bons = $this->requeteEngages($avecRegularisations, $du, $au)
            ->get(['montant_ht', 'montant_ttc']);

        return [
            'ht' => round((float) $bons->sum(fn ($bon) => (float) $bon->montant_ht), 2),
            'ttc' => round((float) $bons->sum(fn ($bon) => (float) $bon->montant_ttc), 2),
            'nombre' => $bons->count(),
        ];
    }

    /** Le mois courant, tel que l'affiche le tableau de bord (KPI Z1). */
    public function engageDuMois(): array
    {
        return $this->engageSurPeriode(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString()
        );
    }
}
