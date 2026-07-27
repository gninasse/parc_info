<?php

namespace Modules\Stock\Services;

use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\StockQueryInterface;
use Modules\Stock\Models\StockArticleMagasin;

/**
 * Façade de lecture du stock (EF-STK-05) — consommée par les modules Achat
 * et ParcInfo via leurs propres contrats d'intégration.
 */
class StockQueryService implements StockQueryInterface
{
    public function __construct(protected AchatIntegrationInterface $achat) {}

    public function quantiteDisponible(int $articleId, ?int $magasinId = null): int
    {
        return (int) StockArticleMagasin::where('article_id', $articleId)
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->sum('quantite_actuelle');
    }

    public function quantitesParArticles(array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        return StockArticleMagasin::whereIn('article_id', $articleIds)
            ->selectRaw('article_id, SUM(quantite_actuelle) as total')
            ->groupBy('article_id')
            ->pluck('total', 'article_id')
            ->map(fn ($total) => (int) $total)
            ->all();
    }

    public function valorisationParArticles(array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        return StockArticleMagasin::whereIn('article_id', $articleIds)
            ->selectRaw('article_id, SUM(valeur_stock_fifo) as valeur')
            ->groupBy('article_id')
            ->pluck('valeur', 'article_id')
            ->map(fn ($valeur) => (float) $valeur)
            ->all();
    }

    public function detailParMagasin(int $articleId): array
    {
        return StockArticleMagasin::with('magasin')
            ->where('article_id', $articleId)
            ->get()
            ->map(fn (StockArticleMagasin $stock) => [
                'magasin_id' => $stock->magasin_id,
                'magasin_code' => $stock->magasin->code,
                'magasin_libelle' => $stock->magasin->libelle,
                'quantite' => $stock->quantite_actuelle,
                'valeur_fifo' => (float) $stock->valeur_stock_fifo,
                'derniere_entree_at' => $stock->derniere_entree_at?->toDateTimeString(),
                'derniere_sortie_at' => $stock->derniere_sortie_at?->toDateTimeString(),
            ])
            ->all();
    }

    public function statutAlerte(int $articleId): string
    {
        $quantite = $this->quantiteDisponible($articleId);

        if ($quantite === 0) {
            return 'RUPTURE';
        }

        return $quantite <= $this->achat->seuilAlerte($articleId) ? 'ALERTE' : 'OK';
    }
}
