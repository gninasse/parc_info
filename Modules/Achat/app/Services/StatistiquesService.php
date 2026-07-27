<?php

namespace Modules\Achat\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\ParcInfo\Models\Fournisseur;

/**
 * Calculs de restitution partagés par le tableau de bord et les états.
 *
 * ENF-TEC-05 — Correction AN-05 : aucune fonction propre à un moteur de base
 * de données (TO_CHAR) n'est utilisée. Les regroupements par mois sont
 * effectués en PHP, ce qui rend les écrans testables sur SQLite et portables.
 *
 * EF-RAP-02 — Correction AN-07 : la période couverte est bien celle des
 * derniers mois glissants. L'implémentation précédente triait par mois
 * croissant avant de limiter à 6, et retournait donc les six mois les plus
 * anciens de l'historique.
 */
class StatistiquesService
{
    /** Dépense engagée par mois sur les N derniers mois glissants. */
    public function depensesMensuelles(?int $nombreMois = null): Collection
    {
        $nombreMois ??= config('achat.rapports.mois_glissants', 12);
        $debut = Carbon::now()->startOfMonth()->subMonths($nombreMois - 1);

        $totauxParMois = BonCommande::engages()
            ->whereDate('date_commande', '>=', $debut->toDateString())
            ->get(['date_commande', 'montant_ttc'])
            ->groupBy(fn (BonCommande $bc) => $bc->date_commande->format('Y-m'))
            ->map(fn (Collection $bons) => (float) $bons->sum('montant_ttc'));

        // Les mois sans dépense apparaissent à zéro : la courbe reste continue.
        return collect(range(0, $nombreMois - 1))
            ->map(function (int $decalage) use ($debut, $totauxParMois) {
                $mois = $debut->copy()->addMonths($decalage);
                $cle = $mois->format('Y-m');

                return [
                    'cle' => $cle,
                    'label' => ucfirst($mois->translatedFormat('M Y')),
                    'total' => $totauxParMois->get($cle, 0.0),
                ];
            });
    }

    /** Répartition de la dépense par fournisseur, les N premiers. */
    public function depensesParFournisseur(?int $limite = null): Collection
    {
        $limite ??= config('achat.rapports.top_fournisseurs', 5);

        return BonCommande::engages()
            ->with('fournisseur')
            ->get(['fournisseur_id', 'montant_ttc'])
            ->groupBy('fournisseur_id')
            ->map(fn (Collection $bons) => [
                'label' => $bons->first()->fournisseur?->nom ?? 'Fournisseur supprimé',
                'total' => (float) $bons->sum('montant_ttc'),
                'nombre' => $bons->count(),
            ])
            ->sortByDesc('total')
            ->take($limite)
            ->values();
    }

    /** Répartition du catalogue par nature d'article. */
    public function articlesParType(): Collection
    {
        $libelles = config('achat.types_articles', []);

        return Article::get(['type_article'])
            ->groupBy('type_article')
            ->map(fn (Collection $articles, string $type) => [
                'label' => $libelles[$type] ?? $type,
                'count' => $articles->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }

    /**
     * EF-RAP-10 — Part des lignes engagées entièrement livrées.
     * Vaut 100 % en l'absence de ligne engagée.
     */
    public function tauxCompletion(): float
    {
        $lignes = LigneCommande::whereHas('bonCommande', fn ($bc) => $bc->engages())
            ->get(['quantite', 'quantite_livree']);

        if ($lignes->isEmpty()) {
            return 100.0;
        }

        $livrees = $lignes->filter(fn ($ligne) => $ligne->quantite_livree >= $ligne->quantite)->count();

        return round(($livrees / $lignes->count()) * 100, 1);
    }

    /** Indicateurs de volumétrie du tableau de bord. */
    public function indicateurs(): array
    {
        $parStatutBc = BonCommande::get(['statut'])->countBy('statut');

        return [
            'total_articles' => Article::count(),
            'alertes_stock' => $this->alertesStock(),
            'total_bc' => $parStatutBc->sum(),
            'bc_brouillon' => $parStatutBc->get('brouillon', 0),
            'bc_valide' => $parStatutBc->get('valide', 0),
            'bc_en_livraison' => $parStatutBc->get('partiel', 0) + $parStatutBc->get('livre', 0),
            'montant_engage' => (float) BonCommande::engages()->sum('montant_ttc'),
            'taux_completion' => $this->tauxCompletion(),
            'fournisseurs_actifs' => Fournisseur::where('est_actif', true)->count(),
        ];
    }

    /**
     * EF-STK-05 — Consommables au niveau du seuil ou en dessous, d'après
     * les quantités du module Stock (référentiel unique).
     */
    protected function alertesStock(): int
    {
        $consommables = Article::consommables()->get(['id', 'seuil_alerte']);
        $quantites = app(\Modules\Achat\Contracts\StockQueryInterface::class)
            ->quantitesParArticles($consommables->pluck('id')->all());

        return $consommables
            ->filter(fn (Article $article) => ($quantites[$article->id] ?? 0) <= (int) $article->seuil_alerte)
            ->count();
    }

    /** EF-BC-19 — Bons dont le reliquat est ouvert depuis trop longtemps. */
    public function reliquatsAnciens(?int $jours = null): Collection
    {
        $jours ??= config('achat.reliquat_alerte_jours', 60);
        $limite = Carbon::now()->subDays($jours);

        return BonCommande::whereIn('statut', ['valide', 'partiel'])
            ->whereDate('date_commande', '<=', $limite->toDateString())
            ->with('fournisseur')
            ->get()
            ->filter(fn (BonCommande $bc) => $bc->reste_a_livrer > 0)
            ->values();
    }
}
