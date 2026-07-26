<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Achat\Models\Article;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Licence;
use Modules\Stock\Http\Requests\StoreSortieRequest;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\SortieStockService;

class SortieController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected SortieStockService $sortieService) {}

    public function index()
    {
        $this->authorize('stock.sorties.view');

        $magasins = Magasin::where('est_actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        $employes = Employe::where('est_actif', true)->orderBy('nom')->get();
        $services = Service::all();
        $directions = Direction::all();
        $unites = Unite::all();

        // Charger les équipements physiques actuellement en stock (disponibles pour affectation)
        $equipements = Equipement::whereIn('statut', ['en_stock', 'en_stock_magasin', 'en_stock_dsi'])->get();

        // Charger les licences disponibles
        $licences = Licence::with('logiciel')->where('actif', true)->get();

        return view('stock::sorties.index', compact(
            'magasins', 'articles', 'employes', 'services', 'directions', 'unites', 'equipements', 'licences'
        ));
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('stock.sorties.view');

        $query = StockMouvement::with(['article', 'magasin', 'affectation', 'creator'])
            ->where('type_mouvement', 'SORTIE');

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
            ->map(function (StockMouvement $m) {
                $targetName = '-';
                if ($m->affectation) {
                    switch ($m->affectation->type_cible) {
                        case 'EMPLOYE':
                            $emp = Employe::find($m->affectation->cible_id);
                            $targetName = $emp ? "{$emp->nom} {$emp->prenom}" : "Employé #{$m->affectation->cible_id}";
                            break;
                        case 'SERVICE':
                            $srv = Service::find($m->affectation->cible_id);
                            $targetName = $srv ? $srv->libelle : "Service #{$m->affectation->cible_id}";
                            break;
                        case 'DIRECTION':
                            $dir = Direction::find($m->affectation->cible_id);
                            $targetName = $dir ? $dir->libelle : "Direction #{$m->affectation->cible_id}";
                            break;
                        case 'UNITE':
                            $uni = Unite::find($m->affectation->cible_id);
                            $targetName = $uni ? $uni->libelle : "Unité #{$m->affectation->cible_id}";
                            break;
                    }
                }

                return [
                    'id' => $m->id,
                    'magasin' => $m->magasin?->nom ?? '-',
                    'article' => $m->article?->designation ?? '-',
                    'quantite' => $m->quantite,
                    'cout_unitaire' => number_format((float) $m->cout_unitaire, 2, ',', ' ').' F CFA',
                    'cout_total' => number_format((float) $m->quantite * (float) $m->cout_unitaire, 2, ',', ' ').' F CFA',
                    'type_affectation' => $m->affectation?->type_affectation_parcinfo ?? '-',
                    'cible' => $targetName,
                    'reference_document' => $m->reference_document ?? '-',
                    'created_at' => $m->created_at?->toDateTimeString(),
                    'created_by' => $m->creator?->name ?? '-',
                ];
            });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function store(StoreSortieRequest $request): JsonResponse
    {
        try {
            $mouvement = $this->sortieService->creerSortie(
                (int) $request->input('magasin_id'),
                (int) $request->input('article_id'),
                (int) $request->input('quantite'),
                $request->input('type_affectation_parcinfo'),
                $request->input('type_cible'),
                (int) $request->input('cible_id'),
                [
                    'equipement_id' => $request->input('equipement_id'),
                    'equipement_destination_id' => $request->input('equipement_id'), // alias pour consommable
                    'licence_id' => $request->input('licence_id'),
                    'motif' => $request->input('motif'),
                    'reference_document' => $request->input('reference_document'),
                ],
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Le bon de sortie de stock a été créé et l\'affectation ParcInfo enregistrée avec succès.',
                'data' => $mouvement,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
