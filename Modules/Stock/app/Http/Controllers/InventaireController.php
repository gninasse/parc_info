<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Services\InventaireService;

class InventaireController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected InventaireService $inventaireService) {}

    public function index()
    {
        $this->authorize('stock.inventaires.view');

        $magasins = Magasin::where('est_actif', true)->orderBy('nom')->get();

        return view('stock::inventaires.index', compact('magasins'));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.inventaires.view');

        $query = StockInventaire::with(['magasin', 'creator', 'validator']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('numero_inventaire', 'like', "%{$search}%");
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('magasin_id')) {
            $query->where('magasin_id', $request->input('magasin_id'));
        }

        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');

        $allowed = ['numero_inventaire', 'statut', 'nombre_articles', 'nombre_ecarts', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (StockInventaire $i) => [
                'id' => $i->id,
                'numero_inventaire' => $i->numero_inventaire,
                'magasin' => $i->magasin?->nom ?? '-',
                'statut' => $i->statut,
                'nombre_articles' => $i->nombre_articles,
                'nombre_ecarts' => $i->nombre_ecarts,
                'created_by' => $i->creator?->name ?? '-',
                'created_at' => $i->created_at?->toDateTimeString(),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show($id): JsonResponse
    {
        $this->authorize('stock.inventaires.view');

        $inventaire = StockInventaire::with(['magasin', 'creator', 'validator', 'lignes.article'])->findOrFail($id);

        $lignesMapped = $inventaire->lignes->map(fn ($l) => [
            'id' => $l->id,
            'article_designation' => $l->article?->designation ?? '-',
            'article_code' => $l->article?->code_article ?? '-',
            'quantite_theorique' => $l->quantite_theorique,
            'quantite_reelle' => $l->quantite_reelle,
            'ecart' => $l->ecart,
            'cout_unitaire_reference' => number_format((float) $l->cout_unitaire_reference, 2, ',', ' ').' F CFA',
            'valorisation_ecart' => number_format((float) $l->ecart * (float) $l->cout_unitaire_reference, 2, ',', ' ').' F CFA',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $inventaire->id,
                'numero_inventaire' => $inventaire->numero_inventaire,
                'magasin_nom' => $inventaire->magasin?->nom ?? '-',
                'statut' => $inventaire->statut,
                'date_inventaire' => $inventaire->date_inventaire?->toDateString(),
                'created_by_nom' => $inventaire->creator?->name ?? '-',
                'created_at' => $inventaire->created_at?->toDateTimeString(),
                'valide_par_nom' => $inventaire->validator?->name ?? '-',
                'date_cloture' => $inventaire->date_cloture?->toDateTimeString(),
                'lignes' => $lignesMapped,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('stock.inventaires.create');

        $request->validate([
            'magasin_id' => ['required', 'exists:stock_magasins,id'],
        ]);

        try {
            $inventaire = $this->inventaireService->creerInventaire(
                (int) $request->input('magasin_id'),
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "La campagne d'inventaire {$inventaire->numero_inventaire} a été initialisée.",
                'data' => $inventaire,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function saisieForm($id)
    {
        $this->authorize('stock.inventaires.create');

        $inventaire = StockInventaire::with(['magasin', 'lignes.article'])->findOrFail($id);

        if ($inventaire->statut !== 'BROUILLON') {
            return redirect()->route('stock.inventaires.index')->with('error', "Cet inventaire n'est plus modifiable.");
        }

        return view('stock::inventaires.saisie', compact('inventaire'));
    }

    public function enregistrerSaisie(Request $request, $id): JsonResponse
    {
        $this->authorize('stock.inventaires.create');

        $request->validate([
            'saisies' => ['required', 'array'],
            'saisies.*' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $this->inventaireService->enregistrerSaisie((int) $id, $request->input('saisies'));

            return response()->json([
                'success' => true,
                'message' => 'Les quantités réelles saisies ont été enregistrées avec succès.',
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
        $this->authorize('stock.inventaires.admin');

        try {
            $this->inventaireService->validerInventaire((int) $id, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'L\'inventaire a été clôturé et validé. Les stocks ont été ajustés automatiquement.',
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
        $this->authorize('stock.inventaires.create');

        try {
            $this->inventaireService->annulerInventaire((int) $id);

            return response()->json([
                'success' => true,
                'message' => 'L\'inventaire a été annulé avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
