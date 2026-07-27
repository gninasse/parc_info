<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockSnapshot;
use Modules\Stock\Services\RecalculFifoService;
use Modules\Stock\Services\SnapshotService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Valorisation FIFO et snapshots (F7).
 */
class ValorisationController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected SnapshotService $snapshotService,
        protected RecalculFifoService $recalculFifoService,
        protected AchatIntegrationInterface $achat,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.valorisation.view');

        return view('stock::valorisation.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
        ]);
    }

    /** Valorisation actuelle par magasin et article. */
    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.valorisation.view');

        $stocks = StockArticleMagasin::with('magasin')
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->input('magasin_id')))
            ->orderBy('magasin_id')
            ->get();

        $articles = $this->achat->articlesParIds($stocks->pluck('article_id')->unique()->all());

        $rows = $stocks->map(fn (StockArticleMagasin $stock) => [
            'magasin' => $stock->magasin->libelle,
            'code_article' => $articles[$stock->article_id]['code_article'] ?? '-',
            'article' => $articles[$stock->article_id]['designation'] ?? "Article #{$stock->article_id}",
            'quantite' => $stock->quantite_actuelle,
            'valeur_fifo' => (float) $stock->valeur_stock_fifo,
            'cout_moyen' => $stock->quantite_actuelle > 0
                ? round((float) $stock->valeur_stock_fifo / $stock->quantite_actuelle, 2)
                : 0,
        ]);

        return $this->table($rows->count(), $rows->values());
    }

    public function snapshots(Request $request): JsonResponse
    {
        $this->authorize('stock.valorisation.view');

        $query = StockSnapshot::latest('date_snapshot');

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (StockSnapshot $snapshot) => [
                'id' => $snapshot->id,
                'reference' => $snapshot->reference,
                'type' => $snapshot->type,
                'date_snapshot' => $snapshot->date_snapshot->toDateTimeString(),
                'valeur_totale_globale' => (float) $snapshot->valeur_totale_globale,
                'createur' => $snapshot->creator?->name,
            ]);

        return $this->table($total, $rows);
    }

    public function creerSnapshot(Request $request): JsonResponse
    {
        $this->authorize('stock.valorisation.admin');

        return $this->executer(function () use ($request) {
            $snapshot = $this->snapshotService->creer('MANUEL', $request->user()->id);

            return $this->succes("Le snapshot {$snapshot->reference} a été créé.");
        });
    }

    public function showSnapshot(StockSnapshot $snapshot): JsonResponse
    {
        $this->authorize('stock.valorisation.view');

        $snapshot->load('lignes.magasin');
        $articles = $this->achat->articlesParIds($snapshot->lignes->pluck('article_id')->unique()->all());

        return $this->donnees([
            'reference' => $snapshot->reference,
            'type' => $snapshot->type,
            'date_snapshot' => $snapshot->date_snapshot->format('d/m/Y H:i'),
            'valeur_totale_globale' => (float) $snapshot->valeur_totale_globale,
            'lignes' => $snapshot->lignes->map(fn ($ligne) => [
                'magasin' => $ligne->magasin->libelle,
                'code_article' => $articles[$ligne->article_id]['code_article'] ?? '-',
                'article' => $articles[$ligne->article_id]['designation'] ?? "Article #{$ligne->article_id}",
                'quantite' => $ligne->quantite,
                'valeur_fifo' => (float) $ligne->valeur_fifo,
                'cout_unitaire_moyen' => (float) $ligne->cout_unitaire_moyen,
            ])->values(),
        ]);
    }

    public function recalculer(): JsonResponse
    {
        $this->authorize('stock.valorisation.admin');

        return $this->executer(function () {
            $resultat = $this->recalculFifoService->recalculerTout();

            return $this->succes(
                "Recalcul terminé : {$resultat['corrigees']} projection(s) réalignée(s) sur {$resultat['total']}."
            );
        });
    }

    public function pdf(Request $request): Response
    {
        $this->authorize('stock.valorisation.view');

        $stocks = StockArticleMagasin::with('magasin')
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->input('magasin_id')))
            ->orderBy('magasin_id')
            ->get();

        $articles = $this->achat->articlesParIds($stocks->pluck('article_id')->unique()->all());

        $lignes = $stocks->map(fn (StockArticleMagasin $stock) => [
            'magasin' => $stock->magasin->libelle,
            'code_article' => $articles[$stock->article_id]['code_article'] ?? '-',
            'article' => $articles[$stock->article_id]['designation'] ?? "Article #{$stock->article_id}",
            'quantite' => $stock->quantite_actuelle,
            'valeur_fifo' => (float) $stock->valeur_stock_fifo,
        ]);

        return Pdf::loadView('stock::valorisation.pdf', [
            'lignes' => $lignes,
            'valeurTotale' => $lignes->sum('valeur_fifo'),
            'genereLe' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape')->download('valorisation-stock.pdf');
    }
}
