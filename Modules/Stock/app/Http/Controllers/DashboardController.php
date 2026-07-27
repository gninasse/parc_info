<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockMouvement;

/**
 * Tableau de bord du module Stock. Les indicateurs avancés et alertes (F8)
 * seront enrichis en P5.
 */
class DashboardController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function index(): View
    {
        $this->authorize('stock.dashboard.view');

        return view('stock::index');
    }

    public function getData(): JsonResponse
    {
        $this->authorize('stock.dashboard.view');

        return $this->donnees([
            'magasins_actifs' => Magasin::actif()->count(),
            'articles_en_stock' => StockArticleMagasin::where('quantite_actuelle', '>', 0)
                ->distinct('article_id')->count('article_id'),
            'valeur_totale' => (float) StockArticleMagasin::sum('valeur_stock_fifo'),
            'mouvements_du_jour' => StockMouvement::whereDate('created_at', now()->toDateString())->count(),
            'derniers_mouvements' => StockMouvement::with('magasin')
                ->latest()->limit(10)->get()
                ->map(fn (StockMouvement $mouvement) => [
                    'numero' => $mouvement->numero_mouvement,
                    'type_label' => $mouvement->type_label,
                    'type_color' => $mouvement->type_color,
                    'magasin' => $mouvement->magasin->libelle,
                    'quantite' => $mouvement->quantite,
                    'date' => $mouvement->created_at->format('d/m/Y H:i'),
                ]),
        ]);
    }
}
