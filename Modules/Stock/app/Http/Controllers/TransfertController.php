<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Achat\Models\Article;
use Modules\Stock\Http\Requests\StoreTransfertRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Services\TransfertService;

class TransfertController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected TransfertService $transfertService) {}

    public function index()
    {
        $this->authorize('stock.transferts.view');

        // Charger les magasins actifs pour les listes de sélection
        $magasins = Magasin::where('est_actif', true)->orderBy('nom')->get();

        // Charger les articles du catalogue
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        return view('stock::transferts.index', compact('magasins', 'articles'));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.transferts.view');

        $query = StockTransfert::with(['source', 'destination', 'article', 'creator', 'validator']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('numero_transfert', 'like', "%{$search}%")
                    ->orWhereHas('article', function ($a) use ($search) {
                        $a->where('designation', 'like', "%{$search}%")
                            ->orWhere('code_article', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('magasin_source_id')) {
            $query->where('magasin_source_id', $request->input('magasin_source_id'));
        }

        if ($request->filled('magasin_destination_id')) {
            $query->where('magasin_destination_id', $request->input('magasin_destination_id'));
        }

        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');

        $allowed = ['numero_transfert', 'statut', 'quantite', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (StockTransfert $t) => [
                'id' => $t->id,
                'numero_transfert' => $t->numero_transfert,
                'magasin_source' => $t->source?->nom ?? '-',
                'magasin_destination' => $t->destination?->nom ?? '-',
                'article' => $t->article?->designation ?? '-',
                'quantite' => $t->quantite,
                'statut' => $t->statut,
                'created_by' => $t->creator?->name ?? '-',
                'created_at' => $t->created_at?->toDateTimeString(),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show($id): JsonResponse
    {
        $this->authorize('stock.transferts.view');

        $transfert = StockTransfert::with(['source', 'destination', 'article', 'creator', 'validator'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $transfert->id,
                'numero_transfert' => $transfert->numero_transfert,
                'magasin_source_nom' => $transfert->source?->nom ?? '-',
                'magasin_destination_nom' => $transfert->destination?->nom ?? '-',
                'article_designation' => $transfert->article?->designation ?? '-',
                'quantite' => $transfert->quantite,
                'statut' => $transfert->statut,
                'motif_creation' => $transfert->motif_creation,
                'motif_rejet' => $transfert->motif_rejet,
                'created_by_nom' => $transfert->creator?->name ?? '-',
                'created_at' => $transfert->created_at?->toDateTimeString(),
                'valide_par_nom' => $transfert->validator?->name ?? '-',
                'date_validation' => $transfert->date_validation?->toDateTimeString(),
            ],
        ]);
    }

    public function store(StoreTransfertRequest $request): JsonResponse
    {
        try {
            $transfert = $this->transfertService->creerTransfert(
                (int) $request->input('magasin_source_id'),
                (int) $request->input('magasin_destination_id'),
                (int) $request->input('article_id'),
                (int) $request->input('quantite'),
                $request->input('motif_creation'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Le bon de transfert {$transfert->numero_transfert} a été créé avec succès en attente de validation.",
                'data' => $transfert,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function valider($id): JsonResponse
    {
        $this->authorize('stock.transferts.admin');

        try {
            $this->transfertService->validerTransfert((int) $id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Le transfert a été validé et le stock mis à jour avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function rejeter(Request $request, $id): JsonResponse
    {
        $this->authorize('stock.transferts.admin');

        $request->validate([
            'motif_rejet' => ['required', 'string', 'max:500'],
        ]);

        try {
            $this->transfertService->rejeterTransfert((int) $id, $request->input('motif_rejet'), Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Le transfert a été rejeté avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function annuler($id): JsonResponse
    {
        $this->authorize('stock.transferts.create');

        try {
            $this->transfertService->annulerTransfert((int) $id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Le transfert a été annulé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
