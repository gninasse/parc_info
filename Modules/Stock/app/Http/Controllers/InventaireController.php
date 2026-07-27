<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Services\InventaireService;

/**
 * Inventaires physiques (F6).
 */
class InventaireController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected InventaireService $inventaireService,
        protected AchatIntegrationInterface $achat,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.inventaires.view');

        return view('stock::inventaires.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
            'statuts' => config('stock.statuts_inventaire'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.inventaires.view');

        $query = StockInventaire::with('magasin')->latest();

        if ($request->filled('magasin_id')) {
            $query->where('magasin_id', $request->input('magasin_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('search')) {
            $query->where('numero_inventaire', 'like', '%'.$request->input('search').'%');
        }

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (StockInventaire $inventaire) => [
                'id' => $inventaire->id,
                'numero_inventaire' => $inventaire->numero_inventaire,
                'magasin' => $inventaire->magasin->libelle,
                'date_inventaire' => $inventaire->date_inventaire->toDateString(),
                'statut' => $inventaire->statut,
                'statut_label' => $inventaire->statut_label,
                'statut_color' => $inventaire->statut_color,
                'en_cours' => $inventaire->estEnCours(),
                'nombre_articles' => $inventaire->nombre_articles,
                'nombre_ecarts' => $inventaire->nombre_ecarts,
                'created_at' => $inventaire->created_at->toDateTimeString(),
            ]);

        return $this->table($total, $rows);
    }

    /** Fiche + lignes de comptage (modal de saisie). */
    public function show(StockInventaire $inventaire): JsonResponse
    {
        $this->authorize('stock.inventaires.view');

        $articles = $this->achat->articlesParIds(
            $inventaire->lignes->pluck('article_id')->all()
        );

        return $this->donnees([
            'id' => $inventaire->id,
            'numero_inventaire' => $inventaire->numero_inventaire,
            'magasin' => $inventaire->magasin->libelle,
            'statut' => $inventaire->statut,
            'statut_label' => $inventaire->statut_label,
            'en_cours' => $inventaire->estEnCours(),
            'lignes' => $inventaire->lignes->map(fn ($ligne) => [
                'id' => $ligne->id,
                'code_article' => $articles[$ligne->article_id]['code_article'] ?? '-',
                'article' => $articles[$ligne->article_id]['designation'] ?? "Article #{$ligne->article_id}",
                'quantite_theorique' => $ligne->quantite_theorique,
                'quantite_reelle' => $ligne->quantite_reelle,
                'ecart' => $ligne->ecart,
            ])->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('stock.inventaires.create');

        $request->validate([
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            'date_inventaire' => ['nullable', 'date'],
        ]);

        return $this->executer(function () use ($request) {
            $inventaire = $this->inventaireService->creer(
                (int) $request->input('magasin_id'),
                $request->input('date_inventaire'),
                $request->user()->id
            );

            return $this->succes("L'inventaire {$inventaire->numero_inventaire} a été ouvert.");
        });
    }

    /** RG-F6-03 — Sauvegarde partielle des comptages. */
    public function saisirLignes(Request $request, StockInventaire $inventaire): JsonResponse
    {
        $this->authorize('stock.inventaires.create');

        $request->validate(['comptages' => ['required', 'array']]);

        return $this->executer(function () use ($request, $inventaire) {
            $this->inventaireService->saisirComptages($inventaire, $request->input('comptages'));

            return $this->succes('Les comptages ont été enregistrés.');
        });
    }

    public function valider(Request $request, StockInventaire $inventaire): JsonResponse
    {
        $this->authorize('stock.inventaires.admin');

        return $this->executer(function () use ($request, $inventaire) {
            $this->inventaireService->valider($inventaire, $request->user()->id);

            return $this->succes(
                "L'inventaire {$inventaire->numero_inventaire} est clôturé "
                ."({$inventaire->nombre_ecarts} écart(s) régularisé(s))."
            );
        });
    }

    public function annuler(Request $request, StockInventaire $inventaire): JsonResponse
    {
        // RG-F6-09
        $this->authorize('stock.inventaires.admin');

        return $this->executer(function () use ($request, $inventaire) {
            $this->inventaireService->annuler($inventaire, $request->user()->id);

            return $this->succes("L'inventaire {$inventaire->numero_inventaire} a été annulé.");
        });
    }
}
