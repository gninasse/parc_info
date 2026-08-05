<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Services\StatistiqueService;

/**
 * Tableau de bord statistique du module Stock (permission stock.rapports.view).
 * L'écran est rendu vide puis hydraté par un unique appel JSON : les agrégats
 * sont coûteux et ne doivent pas bloquer le premier rendu.
 */
class StatistiqueController extends Controller implements HasMiddleware
{
    public function __construct(private readonly StatistiqueService $statistiques) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.rapports.view', only: ['index', 'getData']),
        ];
    }

    public function index(Request $request)
    {
        return view('stock::statistiques.index', [
            'magasins' => Magasin::query()->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'magasinPreselectionne' => (int) $request->input('magasin_id') ?: null,
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'magasin_id' => ['nullable', 'integer', 'exists:stock_magasins,id'],
            'mois' => ['nullable', 'integer', 'min:3', 'max:36'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->statistiques->tout($valide),
        ]);
    }
}
