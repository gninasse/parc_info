<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\StockQueryInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Http\Requests\InitialiserStockArticleRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Services\StockArticleService;

/**
 * Stock par article et magasin (F2).
 */
class StockArticleController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected StockArticleService $stockArticleService,
        protected AchatIntegrationInterface $achat,
        protected StockQueryInterface $stockQuery,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.articles.view');

        return view('stock::articles.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
            'statutsAlerte' => config('stock.statuts_alerte'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.articles.view');

        $query = StockArticleMagasin::query()
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->input('magasin_id')))
            ->selectRaw('article_id, SUM(quantite_actuelle) as quantite, SUM(valeur_stock_fifo) as valeur, MAX(derniere_entree_at) as derniere_entree, MAX(derniere_sortie_at) as derniere_sortie')
            ->groupBy('article_id');

        $lignes = $query->get();
        $articles = $this->achat->articlesParIds($lignes->pluck('article_id')->all());

        $rows = $lignes->map(function ($ligne) use ($articles) {
            $article = $articles[$ligne->article_id] ?? null;
            $seuil = (int) ($article['seuil_alerte'] ?? 0);
            $quantite = (int) $ligne->quantite;

            return [
                'article_id' => $ligne->article_id,
                'code_article' => $article['code_article'] ?? '-',
                'designation' => $article['designation'] ?? "Article #{$ligne->article_id}",
                'marque' => $article['marque_libelle'] ?? '-',
                'unite_mesure' => $article['unite_mesure'] ?? '-',
                'quantite' => $quantite,
                'valeur_fifo' => (float) $ligne->valeur,
                'seuil_alerte' => $seuil,
                'statut_alerte' => $quantite === 0 ? 'RUPTURE' : ($quantite <= $seuil ? 'ALERTE' : 'OK'),
                'derniere_entree_at' => $ligne->derniere_entree,
                'derniere_sortie_at' => $ligne->derniere_sortie,
            ];
        });

        if ($request->filled('statut_alerte')) {
            $rows = $rows->where('statut_alerte', $request->input('statut_alerte'));
        }

        if ($request->filled('search')) {
            $recherche = mb_strtolower($request->input('search'));
            $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower($row['code_article']), $recherche)
                || str_contains(mb_strtolower($row['designation']), $recherche));
        }

        $total = $rows->count();
        $rows = $rows->sortBy('designation')
            ->slice($request->integer('offset', 0), $request->integer('limit', 25))
            ->values();

        return $this->table($total, $rows);
    }

    /** Détail d'un article : stock par magasin + fiche (modal). */
    public function show(int $article): JsonResponse
    {
        $this->authorize('stock.articles.view');

        $fiche = $this->achat->article($article);

        if (! $fiche) {
            return $this->echec('Article introuvable.', 404);
        }

        return $this->donnees([
            'article' => $fiche,
            'statut_alerte' => $this->stockQuery->statutAlerte($article),
            'magasins' => $this->stockQuery->detailParMagasin($article),
        ]);
    }

    /** Lots FIFO disponibles d'un article (modal détail). */
    public function lots(Request $request, int $article): JsonResponse
    {
        $this->authorize('stock.articles.view');

        $lots = StockLot::with('magasin')
            ->where('article_id', $article)
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->input('magasin_id')))
            ->disponible()
            ->ordreFifo()
            ->get()
            ->map(fn (StockLot $lot) => [
                'id' => $lot->id,
                'magasin' => $lot->magasin->libelle,
                'date_entree' => $lot->date_entree->format('d/m/Y'),
                'quantite_initiale' => $lot->quantite_initiale,
                'quantite_restante' => $lot->quantite_restante,
                'cout_unitaire' => (float) $lot->cout_unitaire,
                'valeur_restante' => round($lot->quantite_restante * (float) $lot->cout_unitaire, 2),
            ]);

        return $this->donnees($lots);
    }

    /** Articles stockables non encore initialisés (select du modal). */
    public function articlesDisponibles(Request $request): JsonResponse
    {
        $this->authorize('stock.articles.admin');

        $articles = $this->achat->articlesStockables(['actif' => true]);

        if ($request->filled('magasin_id')) {
            $dejaInitialises = StockArticleMagasin::where('magasin_id', $request->input('magasin_id'))
                ->pluck('article_id')
                ->all();

            $articles = array_values(array_filter(
                $articles,
                fn (array $article) => ! in_array($article['id'], $dejaInitialises, true)
            ));
        }

        return $this->donnees($articles);
    }

    public function initialiser(InitialiserStockArticleRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $this->stockArticleService->initialiser($request->validated(), $request->user()->id);

            return $this->succes("L'article a été initialisé en stock.");
        });
    }
}
