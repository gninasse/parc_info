<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockMouvement;

class DashboardController extends Controller
{
    public function index()
    {
        $totalMagasins = Magasin::where('est_actif', true)->count();

        $totalValue = (float) StockArticleMagasin::sum('valeur_stock_fifo');

        $totalMovements = StockMouvement::count();

        // Articles en alerte (quantite_actuelle <= seuil_alerte)
        $alertQuery = StockArticleMagasin::whereHas('article', function ($q) {
            $q->whereColumn('stock_articles_magasin.quantite_actuelle', '<=', 'achat_articles.seuil_alerte');
        });
        $alertCount = $alertQuery->count();

        // Mouvements récents
        $recentMovements = StockMouvement::with(['article', 'magasin', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Stocks critiques
        $criticalStocks = StockArticleMagasin::with(['article', 'magasin'])
            ->whereHas('article', function ($q) {
                $q->whereColumn('stock_articles_magasin.quantite_actuelle', '<=', 'achat_articles.seuil_alerte');
            })
            ->orderBy('quantite_actuelle', 'asc')
            ->limit(5)
            ->get();

        return view('stock::index', compact(
            'totalMagasins', 'totalValue', 'totalMovements', 'alertCount', 'recentMovements', 'criticalStocks'
        ));
    }
}
