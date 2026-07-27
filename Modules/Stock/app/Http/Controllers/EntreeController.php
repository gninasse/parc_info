<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Http\Requests\StoreEntreeRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\EntreeStockService;

/**
 * Entrées de stock (F3) — manuelles ; les entrées BL arrivent par
 * l'intégration Achat et sont en lecture seule ici (RG-F3-05).
 */
class EntreeController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected EntreeStockService $entreeStockService,
        protected AchatIntegrationInterface $achat,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.entrees.view');

        return view('stock::entrees.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.entrees.view');

        $query = StockMouvement::with('magasin')
            ->entrees()
            ->latest();

        if ($request->filled('magasin_id')) {
            $query->where('magasin_id', $request->input('magasin_id'));
        }

        if ($request->filled('type_mouvement')) {
            $query->where('type_mouvement', $request->input('type_mouvement'));
        }

        if ($request->filled('type_origine')) {
            $query->where('type_origine', $request->input('type_origine'));
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->input('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->input('date_fin'));
        }

        if ($request->filled('search')) {
            $query->where('numero_mouvement', 'like', '%'.$request->input('search').'%');
        }

        $total = $query->count();

        $mouvements = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get();

        $articles = $this->achat->articlesParIds($mouvements->pluck('article_id')->unique()->all());

        $rows = $mouvements->map(fn (StockMouvement $mouvement) => [
            'id' => $mouvement->id,
            'numero_mouvement' => $mouvement->numero_mouvement,
            'type_mouvement' => $mouvement->type_mouvement,
            'type_label' => $mouvement->type_label,
            'type_color' => $mouvement->type_color,
            'type_origine' => $mouvement->type_origine,
            'article' => $articles[$mouvement->article_id]['designation'] ?? "Article #{$mouvement->article_id}",
            'magasin' => $mouvement->magasin->libelle,
            'quantite' => $mouvement->quantite,
            'cout_unitaire' => (float) $mouvement->cout_unitaire,
            'reference_document' => $mouvement->reference_document,
            'supprimable' => ! $mouvement->provientDunBordereau()
                && $mouvement->created_at->diffInHours(now()) < 24,
            'created_at' => $mouvement->created_at->toDateTimeString(),
        ]);

        return $this->table($total, $rows);
    }

    public function show(StockMouvement $mouvement): JsonResponse
    {
        $this->authorize('stock.entrees.view');

        $article = $this->achat->article($mouvement->article_id);

        return $this->donnees([
            'id' => $mouvement->id,
            'numero_mouvement' => $mouvement->numero_mouvement,
            'type_mouvement' => $mouvement->type_mouvement,
            'type_label' => $mouvement->type_label,
            'type_origine' => $mouvement->type_origine,
            'article' => $article['designation'] ?? "Article #{$mouvement->article_id}",
            'code_article' => $article['code_article'] ?? '-',
            'magasin' => $mouvement->magasin->libelle,
            'quantite' => $mouvement->quantite,
            'cout_unitaire' => (float) $mouvement->cout_unitaire,
            'valeur' => round($mouvement->quantite * (float) $mouvement->cout_unitaire, 2),
            'reference_document' => $mouvement->reference_document,
            'motif' => $mouvement->motif,
            'created_at' => $mouvement->created_at->format('d/m/Y H:i'),
            'createur' => $mouvement->creator?->name,
        ]);
    }

    /** Articles stockables pour le select du modal. */
    public function articles(): JsonResponse
    {
        $this->authorize('stock.entrees.create');

        return $this->donnees($this->achat->articlesStockables(['actif' => true]));
    }

    public function store(StoreEntreeRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $mouvement = $this->entreeStockService->enregistrerEntree(
                $request->validated(),
                $request->user()->id
            );

            return $this->succes("L'entrée {$mouvement->numero_mouvement} a été enregistrée.");
        });
    }

    public function destroy(Request $request, StockMouvement $mouvement): JsonResponse
    {
        // RG-F3-06 — suppression réservée à l'administrateur des entrées.
        $this->authorize('stock.entrees.admin');

        return $this->executer(function () use ($request, $mouvement) {
            $this->entreeStockService->supprimer($mouvement, $request->user()->id);

            return $this->succes("L'entrée {$mouvement->numero_mouvement} a été supprimée.");
        });
    }
}
