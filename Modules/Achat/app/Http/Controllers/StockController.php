<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Contracts\StockQueryInterface;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Models\Article;

/**
 * Suivi du stock des consommables (E-14).
 *
 * EF-STK-05 — Écran de consultation. Les quantités et la valorisation
 * proviennent du module Stock (lots FIFO), seul référentiel faisant foi,
 * lues via StockQueryInterface.
 */
class StockController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(protected StockQueryInterface $stockQuery) {}

    public function index(): View
    {
        $this->authorize('achat.stocks.view');

        return view('achat::stocks.index', [
            'niveaux' => config('achat.seuils_stock'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.stocks.view');

        $query = Article::consommables()->with('marque');

        if ($request->filled('search')) {
            $recherche = '%'.$request->input('search').'%';

            $query->where(function ($sousRequete) use ($recherche) {
                $sousRequete->where('code_article', 'like', $recherche)
                    ->orWhere('designation', 'like', $recherche);
            });
        }

        $articles = $query->orderBy('designation')->get();
        $quantites = $this->stockQuery->quantitesParArticles($articles->pluck('id')->all());
        $valeurs = $this->stockQuery->valorisationParArticles($articles->pluck('id')->all());

        $rows = $articles->map(function (Article $article) use ($quantites, $valeurs) {
            $quantite = (int) ($quantites[$article->id] ?? 0);
            $niveau = $this->niveau($quantite, (int) $article->seuil_alerte);

            return [
                'id' => $article->id,
                'code_article' => $article->code_article,
                'designation' => $article->designation,
                'marque' => $article->marque?->libelle ?? '-',
                'unite_mesure' => $article->unite_mesure,
                'stock_actuel' => $quantite,
                'seuil_alerte' => $article->seuil_alerte,
                'valeur_fifo' => (float) ($valeurs[$article->id] ?? 0),
                'niveau' => $niveau,
                'niveau_label' => config("achat.seuils_stock.{$niveau}.label", $niveau),
                'niveau_color' => config("achat.seuils_stock.{$niveau}.color", 'secondary'),
            ];
        });

        // EF-STK-03 / EF-STK-07 : filtrage par niveau qualifié.
        if ($request->filled('niveau')) {
            $rows = $rows->where('niveau', $request->input('niveau'));
        }

        $total = $rows->count();
        $rows = $rows->slice($request->integer('offset', 0), $request->integer('limit', 25))->values();

        return $this->table($total, $rows);
    }

    /** Niveau qualifié selon les ratios de config('achat.seuils_stock'). */
    protected function niveau(int $quantite, int $seuil): string
    {
        foreach (config('achat.seuils_stock', []) as $code => $definition) {
            $ratio = $definition['max_ratio'] ?? null;

            if ($ratio === null) {
                return $code;
            }

            if ($seuil > 0 ? $quantite <= $seuil * $ratio : $quantite <= 0) {
                return $code;
            }
        }

        return 'normal';
    }
}
