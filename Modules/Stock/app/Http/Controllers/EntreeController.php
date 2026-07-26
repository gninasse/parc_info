<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Achat\Models\Article;
use Modules\Stock\Http\Requests\StoreEntreeRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\EntreeStockService;

class EntreeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected EntreeStockService $entreeService) {}

    public function index()
    {
        $this->authorize('stock.entrees.view');

        $magasins = Magasin::where('est_actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        return view('stock::entrees.index', compact('magasins', 'articles'));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.entrees.view');

        $query = StockMouvement::with(['article', 'magasin', 'creator'])
            ->where('type_mouvement', 'ENTREE');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_document', 'like', "%{$search}%")
                    ->orWhere('motif', 'like', "%{$search}%")
                    ->orWhereHas('article', function ($a) use ($search) {
                        $a->where('designation', 'like', "%{$search}%")
                            ->orWhere('code_article', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('magasin_id')) {
            $query->where('magasin_id', $request->input('magasin_id'));
        }

        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');

        $allowed = ['quantite', 'cout_unitaire', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (StockMouvement $m) => [
                'id' => $m->id,
                'magasin' => $m->magasin?->nom ?? '-',
                'article' => $m->article?->designation ?? '-',
                'quantite' => $m->quantite,
                'cout_unitaire' => number_format((float) $m->cout_unitaire, 2, ',', ' ').' F CFA',
                'cout_total' => number_format((float) $m->quantite * (float) $m->cout_unitaire, 2, ',', ' ').' F CFA',
                'type_origine' => $m->type_origine,
                'reference_document' => $m->reference_document ?? '-',
                'motif' => $m->motif ?? '-',
                'created_by' => $m->creator?->name ?? '-',
                'created_at' => $m->created_at?->toDateTimeString(),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function store(StoreEntreeRequest $request): JsonResponse
    {
        try {
            $mouvement = $this->entreeService->creerEntreeManuelle(
                (int) $request->input('magasin_id'),
                (int) $request->input('article_id'),
                (int) $request->input('quantite'),
                (float) $request->input('cout_unitaire'),
                $request->input('reference_document'),
                $request->input('motif'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Le bon d\'entrée de stock a été créé avec succès.',
                'data' => $mouvement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($id): JsonResponse
    {
        $this->authorize('stock.entrees.admin');

        try {
            $this->entreeService->supprimerEntreeManuelle((int) $id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Le bon d\'entrée de stock manuel a été supprimé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
