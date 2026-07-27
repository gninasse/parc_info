<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Http\Requests\StoreTransfertRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockTransfert;
use Modules\Stock\Services\TransfertService;

/**
 * Transferts inter-magasins (F5).
 */
class TransfertController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected TransfertService $transfertService,
        protected AchatIntegrationInterface $achat,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.transferts.view');

        return view('stock::transferts.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
            'statuts' => config('stock.statuts_transfert'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.transferts.view');

        $query = StockTransfert::with(['magasinSource', 'magasinDestination'])
            ->latest();

        if ($request->filled('magasin_source_id')) {
            $query->where('magasin_source_id', $request->input('magasin_source_id'));
        }

        if ($request->filled('magasin_destination_id')) {
            $query->where('magasin_destination_id', $request->input('magasin_destination_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('search')) {
            $query->where('numero_transfert', 'like', '%'.$request->input('search').'%');
        }

        $total = $query->count();

        $transferts = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get();

        $articles = $this->achat->articlesParIds($transferts->pluck('article_id')->unique()->all());

        $rows = $transferts->map(fn (StockTransfert $transfert) => [
            'id' => $transfert->id,
            'numero_transfert' => $transfert->numero_transfert,
            'article' => $articles[$transfert->article_id]['designation'] ?? "Article #{$transfert->article_id}",
            'source' => $transfert->magasinSource->libelle,
            'destination' => $transfert->magasinDestination->libelle,
            'quantite' => $transfert->quantite,
            'statut' => $transfert->statut,
            'statut_label' => $transfert->statut_label,
            'statut_color' => $transfert->statut_color,
            'en_attente' => $transfert->estEnAttente(),
            'created_by' => $transfert->created_by,
            'created_at' => $transfert->created_at->toDateTimeString(),
        ]);

        return $this->table($total, $rows);
    }

    public function show(StockTransfert $transfert): JsonResponse
    {
        $this->authorize('stock.transferts.view');

        $article = $this->achat->article($transfert->article_id);

        return $this->donnees([
            'id' => $transfert->id,
            'numero_transfert' => $transfert->numero_transfert,
            'article' => $article['designation'] ?? "Article #{$transfert->article_id}",
            'code_article' => $article['code_article'] ?? '-',
            'source' => $transfert->magasinSource->libelle,
            'destination' => $transfert->magasinDestination->libelle,
            'quantite' => $transfert->quantite,
            'statut_label' => $transfert->statut_label,
            'motif_creation' => $transfert->motif_creation,
            'motif_rejet' => $transfert->motif_rejet,
            'validateur' => $transfert->validateur?->name,
            'date_validation' => $transfert->date_validation?->format('d/m/Y H:i'),
            'mouvement_sortant' => $transfert->mouvementSortant?->numero_mouvement,
            'mouvement_entrant' => $transfert->mouvementEntrant?->numero_mouvement,
            'created_at' => $transfert->created_at->format('d/m/Y H:i'),
            'createur' => $transfert->creator?->name,
        ]);
    }

    /** Articles stockables (select du modal). */
    public function articles(): JsonResponse
    {
        $this->authorize('stock.transferts.create');

        return $this->donnees($this->achat->articlesStockables(['actif' => true]));
    }

    public function store(StoreTransfertRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $transfert = $this->transfertService->creer(
                $request->validated(),
                $request->user()->id
            );

            return $this->succes("Le transfert {$transfert->numero_transfert} a été créé et attend validation.");
        });
    }

    public function valider(Request $request, StockTransfert $transfert): JsonResponse
    {
        // RG-F5-04 adaptée : permission dédiée à la validation.
        $this->authorize('stock.transferts.valider');

        return $this->executer(function () use ($request, $transfert) {
            $this->transfertService->valider($transfert, $request->user()->id);

            return $this->succes("Le transfert {$transfert->numero_transfert} a été validé.");
        });
    }

    public function rejeter(Request $request, StockTransfert $transfert): JsonResponse
    {
        $this->authorize('stock.transferts.valider');

        return $this->executer(function () use ($request, $transfert) {
            $this->transfertService->rejeter(
                $transfert,
                $request->user()->id,
                (string) $request->input('motif')
            );

            return $this->succes("Le transfert {$transfert->numero_transfert} a été rejeté.");
        });
    }

    public function annuler(Request $request, StockTransfert $transfert): JsonResponse
    {
        return $this->executer(function () use ($request, $transfert) {
            $this->transfertService->annuler(
                $transfert,
                $request->user()->id,
                $request->user()->can('stock.transferts.admin')
            );

            return $this->succes("Le transfert {$transfert->numero_transfert} a été annulé.");
        });
    }
}
