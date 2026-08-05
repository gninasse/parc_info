<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;

/**
 * Agrégats du tableau de bord statistique (UX §7 — page « Statistiques »).
 *
 * Contrairement au tableau de bord opérationnel (qui répond à « que dois-je
 * faire maintenant ? »), cette page répond à « comment le stock se comporte-t-il
 * dans le temps ? » : séries mensuelles, structure de la valeur, palmarès,
 * qualité de la saisie. Tout est calculé sur une fenêtre glissante paramétrable
 * (12 mois par défaut) et filtrable par magasin.
 */
class StatistiqueService
{
    /** Palette partagée avec les graphiques (Chart.js) — cohérence Bootstrap 5. */
    public const PALETTE = ['#0d6efd', '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14', '#20c997', '#6610f2', '#adb5bd'];

    /**
     * @param  array{magasin_id?: int|null, mois?: int}  $filtres
     */
    public function tout(array $filtres = []): array
    {
        $magasinId = ! empty($filtres['magasin_id']) ? (int) $filtres['magasin_id'] : null;
        $mois = max(3, min(36, (int) ($filtres['mois'] ?? 12)));
        $debut = now()->copy()->subMonthsNoOverflow($mois - 1)->startOfMonth();

        return [
            'parametres' => [
                'magasin_id' => $magasinId,
                'mois' => $mois,
                'debut' => $debut->toDateString(),
                'fin' => now()->toDateString(),
            ],
            'synthese' => $this->synthese($magasinId, $debut),
            'serie_mensuelle' => $this->serieMensuelle($magasinId, $debut, $mois),
            'valeur_par_magasin' => $this->valeurParMagasin($magasinId),
            'valeur_par_nature' => $this->valeurParNature($magasinId),
            'top_categories' => $this->topCategories($magasinId),
            'top_articles_sortis' => $this->topArticlesSortis($magasinId, $debut),
            'top_beneficiaires' => $this->topBeneficiaires($magasinId, $debut),
            'motifs_sortie' => $this->motifsSortie($magasinId, $debut),
            'sante_alertes' => $this->santeAlertes($magasinId),
            'qualite_saisie' => $this->qualiteSaisie($magasinId),
            'activite_utilisateurs' => $this->activiteUtilisateurs($magasinId, $debut),
        ];
    }

    // ── Synthèse (cartes du haut) ──────────────────────────────────────────

    private function synthese(?int $magasinId, Carbon $debut): array
    {
        $niveaux = $this->requeteNiveaux($magasinId)
            ->selectRaw('COUNT(*) AS references_total')
            ->selectRaw('COALESCE(SUM(CASE WHEN stock_niveaux.quantite > 0 THEN 1 ELSE 0 END), 0) AS references_en_stock')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->selectRaw('COALESCE(SUM(CASE WHEN '.Niveau::sqlStatutAlerte()." = 'RUPTURE' THEN 1 ELSE 0 END), 0) AS ruptures")
            ->selectRaw('COALESCE(SUM(CASE WHEN '.Niveau::sqlStatutAlerte()." = 'SOUS_SEUIL' THEN 1 ELSE 0 END), 0) AS sous_seuil")
            ->first();

        $flux = DB::table('stock_mouvements')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('created_at', '>=', $debut)
            ->selectRaw('COUNT(*) AS ecritures')
            ->selectRaw('COALESCE(SUM(CASE WHEN sens > 0 THEN quantite * COALESCE(cout_unitaire, 0) ELSE 0 END), 0) AS valeur_entree')
            ->selectRaw('COALESCE(SUM(CASE WHEN sens < 0 THEN quantite * COALESCE(cout_unitaire, 0) ELSE 0 END), 0) AS valeur_sortie')
            ->first();

        $equipements = DB::table('stock_equipements_magasins')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->count();

        $referencesTotal = (int) ($niveaux->references_total ?? 0);
        $ruptures = (int) ($niveaux->ruptures ?? 0);
        $sousSeuil = (int) ($niveaux->sous_seuil ?? 0);
        $valeur = (float) ($niveaux->valeur ?? 0);
        $valeurSortie = (float) ($flux->valeur_sortie ?? 0);
        $joursFenetre = max(1, (int) $debut->diffInDays(now()));

        // Rotation annualisée : consommation valorisée ramenée au stock moyen
        // approché par le stock actuel (approximation assumée en l'absence
        // d'historique de valorisation quotidienne).
        $rotationAnnuelle = $valeur > 0 ? round(($valeurSortie / $joursFenetre * 365) / $valeur, 2) : null;

        return [
            'valeur_stock' => $valeur,
            'references_en_stock' => (int) ($niveaux->references_en_stock ?? 0),
            'references_total' => $referencesTotal,
            'equipements' => $equipements,
            'ruptures' => $ruptures,
            'sous_seuil' => $sousSeuil,
            'taux_disponibilite' => $referencesTotal > 0
                ? round(($referencesTotal - $ruptures) * 100 / $referencesTotal, 1)
                : 100.0,
            'taux_alerte' => $referencesTotal > 0
                ? round(($ruptures + $sousSeuil) * 100 / $referencesTotal, 1)
                : 0.0,
            'ecritures' => (int) ($flux->ecritures ?? 0),
            'valeur_entree' => (float) ($flux->valeur_entree ?? 0),
            'valeur_sortie' => $valeurSortie,
            'consommation_mensuelle' => round($valeurSortie / max(1, $joursFenetre) * 30, 0),
            'rotation_annuelle' => $rotationAnnuelle,
            'couverture_mois' => $valeurSortie > 0
                ? round($valeur / ($valeurSortie / max(1, $joursFenetre) * 30), 1)
                : null,
        ];
    }

    // ── Série mensuelle entrées / sorties ──────────────────────────────────

    /**
     * Regroupement mensuel en PHP plutôt qu'en SQL : la fonction de troncature
     * de date diffère entre SQLite et PostgreSQL, et le volume mensuel reste
     * modeste. Les mois sans mouvement sont émis à zéro pour un graphique
     * continu.
     */
    private function serieMensuelle(?int $magasinId, Carbon $debut, int $mois): array
    {
        $lignes = DB::table('stock_mouvements')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('created_at', '>=', $debut)
            ->selectRaw('created_at, sens, type, quantite, cout_unitaire')
            ->get();

        $gabarit = [];
        for ($i = 0; $i < $mois; $i++) {
            $curseur = $debut->copy()->addMonthsNoOverflow($i);
            $gabarit[$curseur->format('Y-m')] = [
                'mois' => $curseur->translatedFormat('M Y'),
                'entrees_quantite' => 0.0,
                'sorties_quantite' => 0.0,
                'entrees_valeur' => 0.0,
                'sorties_valeur' => 0.0,
                'transferts' => 0,
                'ajustements' => 0,
            ];
        }

        foreach ($lignes as $ligne) {
            $cle = Carbon::parse($ligne->created_at)->format('Y-m');
            if (! isset($gabarit[$cle])) {
                continue;
            }

            $quantite = (float) $ligne->quantite;
            $valeur = $quantite * (float) ($ligne->cout_unitaire ?? 0);

            if (in_array($ligne->type, [Mouvement::TYPE_TRANSFERT_ENTREE, Mouvement::TYPE_TRANSFERT_SORTIE], true)) {
                $gabarit[$cle]['transferts']++;

                continue;
            }

            if ($ligne->type === Mouvement::TYPE_AJUSTEMENT) {
                $gabarit[$cle]['ajustements']++;
            }

            if ((int) $ligne->sens > 0) {
                $gabarit[$cle]['entrees_quantite'] += $quantite;
                $gabarit[$cle]['entrees_valeur'] += $valeur;
            } else {
                $gabarit[$cle]['sorties_quantite'] += $quantite;
                $gabarit[$cle]['sorties_valeur'] += $valeur;
            }
        }

        return array_values(array_map(fn (array $mois) => array_map(
            fn ($valeur) => is_float($valeur) ? round($valeur, 2) : $valeur,
            $mois
        ), $gabarit));
    }

    // ── Structure de la valeur ─────────────────────────────────────────────

    private function valeurParMagasin(?int $magasinId): array
    {
        return $this->requeteNiveaux($magasinId)
            ->join('stock_magasins', 'stock_magasins.id', '=', 'stock_niveaux.magasin_id')
            ->where('stock_niveaux.quantite', '>', 0)
            ->groupBy('stock_magasins.libelle')
            ->selectRaw('stock_magasins.libelle AS libelle')
            ->selectRaw('COUNT(*) AS nb_references')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) DESC')
            ->get()
            ->map(fn ($l) => ['libelle' => $l->libelle, 'nb_references' => (int) $l->nb_references, 'valeur' => (float) $l->valeur])
            ->all();
    }

    private function valeurParNature(?int $magasinId): array
    {
        return $this->requeteNiveaux($magasinId)
            ->where('stock_niveaux.quantite', '>', 0)
            ->groupBy('catalogue_articles.nature')
            ->selectRaw('catalogue_articles.nature AS nature')
            ->selectRaw('COUNT(*) AS nb_references')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) DESC')
            ->get()
            ->map(fn ($l) => [
                'libelle' => Article::NATURE_LABELS[$l->nature] ?? $l->nature,
                'nb_references' => (int) $l->nb_references,
                'valeur' => (float) $l->valeur,
            ])
            ->all();
    }

    private function topCategories(?int $magasinId, int $limite = 8): array
    {
        return $this->requeteNiveaux($magasinId)
            ->join('catalogue_categories', 'catalogue_categories.id', '=', 'catalogue_articles.categorie_id')
            ->where('stock_niveaux.quantite', '>', 0)
            ->groupBy('catalogue_categories.libelle')
            ->selectRaw('catalogue_categories.libelle AS libelle')
            ->selectRaw('COUNT(*) AS nb_references')
            ->selectRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM('.Niveau::sqlValeur().'), 0) DESC')
            ->limit($limite)
            ->get()
            ->map(fn ($l) => ['libelle' => $l->libelle, 'nb_references' => (int) $l->nb_references, 'valeur' => (float) $l->valeur])
            ->all();
    }

    // ── Palmarès de consommation ───────────────────────────────────────────

    private function topArticlesSortis(?int $magasinId, Carbon $debut, int $limite = 10): array
    {
        return DB::table('stock_mouvements')
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_mouvements.article_id')
            ->when($magasinId, fn ($q) => $q->where('stock_mouvements.magasin_id', $magasinId))
            ->where('stock_mouvements.created_at', '>=', $debut)
            ->where('stock_mouvements.sens', '<', 0)
            ->where('stock_mouvements.type', Mouvement::TYPE_SORTIE)
            ->groupBy('catalogue_articles.code', 'catalogue_articles.nom', 'catalogue_articles.unite_stock')
            ->selectRaw('catalogue_articles.code AS code, catalogue_articles.nom AS nom, catalogue_articles.unite_stock AS unite')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite), 0) AS quantite')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0)), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM(stock_mouvements.quantite), 0) DESC')
            ->limit($limite)
            ->get()
            ->map(fn ($l) => [
                'libelle' => $l->code.' — '.$l->nom,
                'unite' => $l->unite,
                'quantite' => (float) $l->quantite,
                'valeur' => (float) $l->valeur,
            ])
            ->all();
    }

    private function topBeneficiaires(?int $magasinId, Carbon $debut, int $limite = 10): array
    {
        return DB::table('stock_mouvements')
            ->join('stock_sorties', 'stock_sorties.id', '=', 'stock_mouvements.sortie_id')
            ->when($magasinId, fn ($q) => $q->where('stock_mouvements.magasin_id', $magasinId))
            ->where('stock_mouvements.created_at', '>=', $debut)
            ->where('stock_mouvements.type', Mouvement::TYPE_SORTIE)
            ->groupBy('stock_sorties.beneficiaire_libelle', 'stock_sorties.beneficiaire_type')
            ->selectRaw('stock_sorties.beneficiaire_libelle AS libelle, stock_sorties.beneficiaire_type AS type')
            ->selectRaw('COUNT(DISTINCT stock_mouvements.sortie_id) AS nb_bons')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite), 0) AS quantite')
            ->selectRaw('COALESCE(SUM(stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0)), 0) AS valeur')
            ->orderByRaw('COALESCE(SUM(stock_mouvements.quantite * COALESCE(stock_mouvements.cout_unitaire, 0)), 0) DESC')
            ->limit($limite)
            ->get()
            ->map(fn ($l) => [
                'libelle' => $l->libelle ?? ucfirst((string) $l->type),
                'type' => ucfirst(str_replace('_', ' ', (string) $l->type)),
                'nb_bons' => (int) $l->nb_bons,
                'quantite' => (float) $l->quantite,
                'valeur' => (float) $l->valeur,
            ])
            ->all();
    }

    private function motifsSortie(?int $magasinId, Carbon $debut): array
    {
        $libelles = config('stock.motifs_sortie', []);

        return DB::table('stock_sorties')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('date_document', '>=', $debut->toDateString())
            ->groupBy('motif_type')
            ->selectRaw('motif_type, COUNT(*) AS total')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($l) => [
                'libelle' => $libelles[$l->motif_type] ?? $l->motif_type,
                'total' => (int) $l->total,
            ])
            ->all();
    }

    // ── Santé du stock et qualité de la saisie ─────────────────────────────

    private function santeAlertes(?int $magasinId): array
    {
        $agregats = $this->requeteNiveaux($magasinId)
            ->selectRaw(Niveau::sqlStatutAlerte().' AS statut, COUNT(*) AS total')
            ->groupByRaw(Niveau::sqlStatutAlerte())
            ->pluck('total', 'statut');

        return [
            ['libelle' => '✓ OK', 'total' => (int) ($agregats[Niveau::STATUT_OK] ?? 0)],
            ['libelle' => '⚠ Sous seuil', 'total' => (int) ($agregats[Niveau::STATUT_SOUS_SEUIL] ?? 0)],
            ['libelle' => '⛔ Rupture', 'total' => (int) ($agregats[Niveau::STATUT_RUPTURE] ?? 0)],
        ];
    }

    /**
     * Indicateurs de pilotage de la saisie : bons non validés et leur
     * ancienneté. Un bon non validé n'impacte pas les niveaux : c'est le
     * principal risque d'écart entre le stock physique et le stock théorique.
     */
    private function qualiteSaisie(?int $magasinId): array
    {
        $limite = now()->subDays((int) config('stock.jours_alerte_non_valides', 30));

        $entrees = Entree::query()->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId));
        $sorties = Sortie::query()->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId));
        $transferts = Transfert::query()->when($magasinId, fn ($q) => $q->where('magasin_source_id', $magasinId));

        $compter = fn ($builder, bool $anciens = false) => (clone $builder)
            ->nonValides()
            ->where('statut', '<>', 'ANNULE')
            ->when($anciens, fn ($q) => $q->where('created_at', '<', $limite))
            ->count();

        $delaiValidation = DB::table('stock_sorties')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->whereNotNull('valide_le')
            ->get(['created_at', 'valide_le'])
            ->map(fn ($l) => Carbon::parse($l->created_at)->diffInHours(Carbon::parse($l->valide_le)))
            ->avg();

        return [
            'entrees_non_validees' => $compter($entrees),
            'sorties_non_validees' => $compter($sorties),
            'transferts_non_valides' => $compter($transferts),
            'anciens' => $compter($entrees, true) + $compter($sorties, true) + $compter($transferts, true),
            'jours_alerte' => (int) config('stock.jours_alerte_non_valides', 30),
            'delai_validation_heures' => $delaiValidation === null ? null : round((float) $delaiValidation, 1),
            'contre_mouvements' => DB::table('stock_mouvements')
                ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
                ->whereNotNull('mouvement_origine_id')
                ->count(),
        ];
    }

    private function activiteUtilisateurs(?int $magasinId, Carbon $debut, int $limite = 8): array
    {
        return DB::table('stock_mouvements')
            ->leftJoin('users', 'users.id', '=', 'stock_mouvements.created_by')
            ->when($magasinId, fn ($q) => $q->where('stock_mouvements.magasin_id', $magasinId))
            ->where('stock_mouvements.created_at', '>=', $debut)
            ->groupBy('users.name')
            ->selectRaw('users.name AS nom, COUNT(*) AS ecritures')
            ->orderByDesc('ecritures')
            ->limit($limite)
            ->get()
            ->map(fn ($l) => ['libelle' => $l->nom ?? 'Système', 'total' => (int) $l->ecritures])
            ->all();
    }

    // ── Outils ─────────────────────────────────────────────────────────────

    private function requeteNiveaux(?int $magasinId)
    {
        return DB::table('stock_niveaux')
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id')
            ->when($magasinId, fn ($q) => $q->where('stock_niveaux.magasin_id', $magasinId));
    }
}
