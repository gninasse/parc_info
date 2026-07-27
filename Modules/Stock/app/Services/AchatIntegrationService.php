<?php

namespace Modules\Stock\Services;

use Modules\Achat\Models\Article;
use Modules\Stock\Contracts\AchatIntegrationInterface;

/**
 * Seul point de contact du module Stock avec les modèles Achat.
 */
class AchatIntegrationService implements AchatIntegrationInterface
{
    public function articlesStockables(array $filtres = []): array
    {
        $typesStockables = config('achat.types_avec_stock', ['consommable']);

        $query = Article::with('marque')
            ->whereIn('type_article', $typesStockables)
            ->orderBy('designation');

        if (array_key_exists('actif', $filtres)) {
            $query->where('actif', (bool) $filtres['actif']);
        }

        if (filled($filtres['recherche'] ?? null)) {
            $recherche = $filtres['recherche'];
            $query->where(function ($q) use ($recherche) {
                $q->where('designation', 'like', "%{$recherche}%")
                    ->orWhere('code_article', 'like', "%{$recherche}%");
            });
        }

        return $query->get()->map(fn (Article $article) => $this->versTableau($article))->all();
    }

    public function article(int $articleId): ?array
    {
        $article = Article::with('marque')->find($articleId);

        return $article ? $this->versTableau($article) : null;
    }

    public function articlesParIds(array $articleIds): array
    {
        if ($articleIds === []) {
            return [];
        }

        return Article::with('marque')
            ->whereIn('id', $articleIds)
            ->get()
            ->mapWithKeys(fn (Article $article) => [$article->id => $this->versTableau($article)])
            ->all();
    }

    public function estStockable(int $articleId): bool
    {
        $typesStockables = config('achat.types_avec_stock', ['consommable']);

        return Article::where('id', $articleId)
            ->whereIn('type_article', $typesStockables)
            ->exists();
    }

    public function seuilAlerte(int $articleId): int
    {
        return (int) (Article::where('id', $articleId)->value('seuil_alerte') ?? 0);
    }

    protected function versTableau(Article $article): array
    {
        return [
            'id' => $article->id,
            'code_article' => $article->code_article,
            'designation' => $article->designation,
            'type_article' => $article->type_article,
            'unite_mesure' => $article->unite_mesure,
            'seuil_alerte' => $article->seuil_alerte !== null ? (int) $article->seuil_alerte : null,
            'marque_libelle' => $article->marque?->libelle,
        ];
    }
}
