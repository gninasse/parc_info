<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Contracts\OrganisationIntegrationInterface;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Http\Requests\StoreRegularisationRequest;
use Modules\Stock\Http\Requests\StoreSortieRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\SortieStockService;

/**
 * Sorties de stock (F4) — consommation FIFO avec affectation ParcInfo.
 */
class SortieController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(
        protected SortieStockService $sortieStockService,
        protected AchatIntegrationInterface $achat,
        protected GrhIntegrationInterface $grh,
        protected OrganisationIntegrationInterface $organisation,
    ) {}

    public function index(): View
    {
        $this->authorize('stock.sorties.view');

        return view('stock::sorties.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.sorties.view');

        $query = StockMouvement::with(['magasin', 'affectation'])
            ->sorties()
            ->latest();

        if ($request->filled('magasin_id')) {
            $query->where('magasin_id', $request->input('magasin_id'));
        }

        if ($request->filled('type_mouvement')) {
            $query->where('type_mouvement', $request->input('type_mouvement'));
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
            'article' => $articles[$mouvement->article_id]['designation'] ?? "Article #{$mouvement->article_id}",
            'magasin' => $mouvement->magasin->libelle,
            'quantite' => $mouvement->quantite,
            'cout_unitaire' => (float) $mouvement->cout_unitaire,
            'cible' => $this->libelleCible($mouvement),
            'motif' => $mouvement->motif,
            'created_at' => $mouvement->created_at->toDateTimeString(),
        ]);

        return $this->table($total, $rows);
    }

    public function show(StockMouvement $mouvement): JsonResponse
    {
        $this->authorize('stock.sorties.view');

        $article = $this->achat->article($mouvement->article_id);

        return $this->donnees([
            'id' => $mouvement->id,
            'numero_mouvement' => $mouvement->numero_mouvement,
            'type_label' => $mouvement->type_label,
            'article' => $article['designation'] ?? "Article #{$mouvement->article_id}",
            'code_article' => $article['code_article'] ?? '-',
            'magasin' => $mouvement->magasin->libelle,
            'quantite' => $mouvement->quantite,
            'cout_unitaire' => (float) $mouvement->cout_unitaire,
            'valeur' => round($mouvement->quantite * (float) $mouvement->cout_unitaire, 2),
            'cible' => $this->libelleCible($mouvement),
            'motif' => $mouvement->motif,
            'reference_document' => $mouvement->reference_document,
            'created_at' => $mouvement->created_at->format('d/m/Y H:i'),
            'createur' => $mouvement->creator?->name,
        ]);
    }

    /** Cibles disponibles pour un type donné (select dépendant du modal). */
    public function cibles(Request $request): JsonResponse
    {
        $this->authorize('stock.sorties.create');

        $typeCible = $request->input('type_cible');

        return $this->donnees(
            $typeCible === 'EMPLOYE'
                ? array_map(
                    fn (array $employe) => ['id' => $employe['id'], 'libelle' => "{$employe['matricule']} — {$employe['nom_complet']}"],
                    $this->grh->employesActifs()
                )
                : $this->organisation->cibles((string) $typeCible)
        );
    }

    /** Articles disposant d'un stock disponible (select du modal). */
    public function articles(Request $request): JsonResponse
    {
        $this->authorize('stock.sorties.create');

        $articles = $this->achat->articlesStockables(['actif' => true]);

        return $this->donnees($articles);
    }

    public function store(StoreSortieRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $mouvement = $this->sortieStockService->creer(
                $request->validated(),
                $request->user()->id
            );

            return $this->succes("La sortie {$mouvement->numero_mouvement} a été enregistrée.");
        });
    }

    public function regularisation(StoreRegularisationRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $mouvement = $this->sortieStockService->creerRegularisation(
                $request->validated(),
                $request->user()->id
            );

            return $this->succes("La régularisation {$mouvement->numero_mouvement} a été enregistrée.");
        });
    }

    protected function libelleCible(StockMouvement $mouvement): ?string
    {
        $affectation = $mouvement->affectation;

        if (! $affectation) {
            return null;
        }

        $libelle = $affectation->type_cible === 'EMPLOYE'
            ? $this->grh->libelleEmploye((int) $affectation->cible_id)
            : $this->organisation->libelleCible($affectation->type_cible, (int) $affectation->cible_id);

        return $libelle ? "{$affectation->type_cible} : {$libelle}" : $affectation->type_cible;
    }
}
