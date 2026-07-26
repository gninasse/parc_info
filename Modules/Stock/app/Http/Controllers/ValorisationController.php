<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Services\ValorisationService;

class ValorisationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected ValorisationService $valorisationService) {}

    public function index()
    {
        $this->authorize('stock.valorisation.view');

        return view('stock::valorisation.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.valorisation.view');

        $query = StockSnapshot::with('creator');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('reference', 'like', "%{$search}%");
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');

        $allowed = ['reference', 'type', 'valeur_totale_globale', 'created_at'];
        if (! in_array($sortField, $allowed)) {
            $sortField = 'created_at';
        }
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (StockSnapshot $s) => [
                'id' => $s->id,
                'reference' => $s->reference,
                'type' => $s->type,
                'date_snapshot' => $s->date_snapshot?->toDateTimeString(),
                'valeur_totale_globale' => number_format((float) $s->valeur_totale_globale, 2, ',', ' ').' F CFA',
                'created_by' => $s->creator?->name ?? 'Système (Cron)',
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function show($id): JsonResponse
    {
        $this->authorize('stock.valorisation.view');

        $snapshot = StockSnapshot::with(['creator', 'lignes.article', 'lignes.magasin'])->findOrFail($id);

        $lignesMapped = $snapshot->lignes->map(fn ($l) => [
            'id' => $l->id,
            'magasin' => $l->magasin?->nom ?? '-',
            'article_designation' => $l->article?->designation ?? '-',
            'article_code' => $l->article?->code_article ?? '-',
            'quantite' => $l->quantite,
            'cout_unitaire_moyen' => number_format((float) $l->cout_unitaire_moyen, 2, ',', ' ').' F CFA',
            'valeur_total' => number_format((float) $l->valeur_fifo, 2, ',', ' ').' F CFA',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $snapshot->id,
                'reference' => $snapshot->reference,
                'type' => $snapshot->type,
                'date_snapshot' => $snapshot->date_snapshot?->toDateTimeString(),
                'valeur_totale_globale' => number_format((float) $snapshot->valeur_totale_globale, 2, ',', ' ').' F CFA',
                'created_by' => $snapshot->creator?->name ?? 'Système (Cron)',
                'lignes' => $lignesMapped,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('stock.valorisation.create');

        try {
            $snapshot = $this->valorisationService->genererSnapshot('PONCTUEL', Auth::id());

            return response()->json([
                'success' => true,
                'message' => "Le snapshot de valorisation ponctuelle {$snapshot->reference} a été généré avec succès.",
                'data' => $snapshot,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
