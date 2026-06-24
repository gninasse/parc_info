<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Achat\Models\Article;

class StockController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('achat.stocks.view');

        if ($request->ajax() || $request->wantsJson()) {
            $query = Article::where('type_article', 'consommable')
                ->with('marque');

            // Filtres
            if ($request->filled('statut_stock')) {
                if ($request->input('statut_stock') === 'alerte') {
                    $query->whereRaw('stock_actuel <= seuil_alerte');
                } elseif ($request->input('statut_stock') === 'ok') {
                    $query->whereRaw('stock_actuel > seuil_alerte');
                }
            }

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('code_article', 'like', "%{$search}%")
                        ->orWhere('designation', 'like', "%{$search}%");
                });
            }

            // Pagination
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $total = $query->count();

            $rows = $query->limit($limit)
                ->offset($offset)
                ->get()
                ->map(function ($art) {
                    $sousSeuil = $art->stock_actuel <= $art->seuil_alerte;

                    return [
                        'id' => $art->id,
                        'code_article' => $art->code_article,
                        'designation' => $art->designation,
                        'marque' => $art->marque->libelle,
                        'stock_actuel' => $art->stock_actuel,
                        'seuil_alerte' => $art->seuil_alerte,
                        'sous_seuil' => $sousSeuil,
                        'status_badge' => $sousSeuil
                            ? '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>Alerte stock</span>'
                            : '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Stock Correct</span>',
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        }

        return view('achat::stocks.index');
    }
}
