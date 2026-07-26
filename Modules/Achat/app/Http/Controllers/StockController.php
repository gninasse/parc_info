<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Models\Article;

/**
 * Suivi du stock des consommables (E-14).
 *
 * EF-STK-05 — Écran de consultation. Les quantités présentées proviennent de
 * la projection portée par l'article ; le référentiel faisant foi est le
 * module Stock, qui porte les mouvements et les lots de valorisation.
 */
class StockController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

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

        // EF-STK-03 / EF-STK-07 : filtrage par niveau qualifié
        if ($request->filled('niveau')) {
            match ($request->input('niveau')) {
                'rupture' => $query->where('stock_actuel', '<=', 0),
                'alerte' => $query->sousSeuil()->where('stock_actuel', '>', 0),
                'normal' => $query->whereColumn('stock_actuel', '>', 'seuil_alerte'),
                default => null,
            };
        }

        if ($request->filled('search')) {
            $recherche = '%'.$request->input('search').'%';

            $query->where(function ($sousRequete) use ($recherche) {
                $sousRequete->where('code_article', 'like', $recherche)
                    ->orWhere('designation', 'like', $recherche);
            });
        }

        $total = $query->count();

        $rows = $query->orderBy('designation')
            ->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(function (Article $article) {
                $niveau = $article->niveau_stock;

                return [
                    'id' => $article->id,
                    'code_article' => $article->code_article,
                    'designation' => $article->designation,
                    'marque' => $article->marque?->libelle ?? '-',
                    'unite_mesure' => $article->unite_mesure,
                    'stock_actuel' => $article->stock_actuel,
                    'seuil_alerte' => $article->seuil_alerte,
                    'niveau' => $niveau,
                    'niveau_label' => config("achat.seuils_stock.{$niveau}.label", $niveau),
                    'niveau_color' => config("achat.seuils_stock.{$niveau}.color", 'secondary'),
                ];
            });

        return $this->table($total, $rows);
    }
}
