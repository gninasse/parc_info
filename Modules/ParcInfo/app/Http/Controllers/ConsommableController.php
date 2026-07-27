<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ParcInfo\Http\Requests\ConsommerConsommableRequest;
use Modules\ParcInfo\Http\Requests\StoreConsommableRequest;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Contracts\StockIntegrationInterface;
use Modules\ParcInfo\Models\MouvementConsommable;
use Modules\ParcInfo\Models\TypeConsommable;
use Modules\ParcInfo\Services\GestionStockService;

class ConsommableController extends Controller
{
    private $stockService;

    public function __construct(
        GestionStockService $stockService,
        protected StockIntegrationInterface $stock,
    ) {
        $this->stockService = $stockService;
    }

    public function index()
    {
        $types = TypeConsommable::orderBy('nom')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $marques = Marque::orderBy('libelle')->get();

        return view('parcinfo::informatique.consommables.index', compact('types', 'fournisseurs', 'marques'));
    }

    public function getData(Request $request)
    {
        $query = Consommable::with(['typeConsommable', 'fournisseur', 'marque']);

        if ($request->filled('type_consommable_id')) {
            $query->where('type_consommable_id', $request->type_consommable_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'ilike', "%{$s}%")
                    ->orWhere('code', 'ilike', "%{$s}%");
            });
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // EF-STK-05 — quantités et valorisation lues auprès du module Stock ;
        // le filtre de statut s'applique donc après rapprochement, en PHP.
        $consommables = $query->get();
        $articleIds = $consommables->pluck('article_id')->filter()->values()->all();
        $quantites = $this->stock->quantitesParArticles($articleIds);
        $valeursStock = $this->stock->valorisationParArticles($articleIds);

        $rows = $consommables->map(fn ($c) => $this->formatRow($c, $quantites, $valeursStock));

        if ($request->filled('statut')) {
            $statutRecherche = strtoupper($request->statut);
            $rows = $rows->filter(fn ($row) => $row['statut_stock'] === $statutRecherche)->values();
        }

        $total = $rows->count();
        $rows = $rows->slice((int) $request->get('offset', 0), (int) $request->get('limit', 25))->values();

        return response()->json([
            'total' => $total,
            'rows' => $rows,
            'stats' => [
                'total' => Consommable::count(),
                'en_rupture' => collect($articleIds)
                    ->filter(fn ($id) => ($quantites[$id] ?? 0) === 0)
                    ->count(),
                'valeur_totale' => array_sum($valeursStock),
                'mouvements_mois' => MouvementConsommable::whereMonth('date_mouvement', now()->month)->count(),
            ],
        ]);
    }

    public function store(StoreConsommableRequest $request)
    {
        $consommable = Consommable::create($request->validated());

        activity('consommable')
            ->performedOn($consommable)
            ->causedBy(auth()->user())
            ->log('Ajout au catalogue');

        return response()->json([
            'success' => true,
            'message' => 'Consommable ajouté au catalogue',
            'consommable_id' => $consommable->id,
            'redirect' => route('parc-info.consommables.show', $consommable->id),
        ]);
    }

    public function show($id)
    {
        $consommable = Consommable::with([
            'typeConsommable',
            'fournisseur',
            'mouvementsStock.utilisateur',
            'mouvementsStock.equipement',
            'mouvementsStock.employe',
            'mouvementsStock.service',
            'mouvementsStock.unite',
            'affectations.equipement',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($consommable);
        }

        $types = TypeConsommable::orderBy('nom')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $marques = Marque::orderBy('libelle')->get();
        $services = \Modules\Organisation\Models\Service::orderBy('libelle')->get();
        $unites = \Modules\Organisation\Models\Unite::orderBy('libelle')->get();
        $directions = \Modules\Organisation\Models\Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $sites = \Modules\Organisation\Models\Site::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.consommables.show', compact('consommable', 'types', 'fournisseurs', 'marques', 'services', 'unites', 'directions', 'sites'));
    }

    public function update(StoreConsommableRequest $request, $id)
    {
        $consommable = Consommable::findOrFail($id);
        $consommable->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Fiche article mise à jour.',
            'data' => $consommable->load(['typeConsommable', 'fournisseur', 'marque']),
        ]);
    }

    public function toggleStatus($id)
    {
        $consommable = Consommable::findOrFail($id);
        $consommable->est_actif = ! $consommable->est_actif;
        $consommable->save();

        return response()->json(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function destroy($id)
    {
        $consommable = Consommable::findOrFail($id);
        if ($consommable->mouvementsStock()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un article ayant un historique de mouvements.'], 422);
        }
        $consommable->delete();

        return response()->json(['success' => true, 'message' => 'Consommable supprimé.']);
    }

    public function consommer(ConsommerConsommableRequest $request, $id)
    {
        $consommable = Consommable::findOrFail($id);

        if ($consommable->quantite_stock_actuel < $request->quantite) {
            return response()->json(['success' => false, 'message' => 'Stock insuffisant'], 422);
        }

        DB::transaction(function () use ($consommable, $request) {
            MouvementConsommable::create([
                'consommable_id' => $consommable->id,
                'type_mouvement' => 'Consommation',
                'quantite' => $request->quantite,
                'date_mouvement' => now(),
                'utilisateur_id' => auth()->id(),
                'equipement_id' => $request->equipement_id,
                'employe_id' => $request->employe_id,
                'service_id' => $request->service_id,
                'unite_id' => $request->unite_id,
                'raison' => $request->raison,
                'notes' => $request->notes,
            ]);

            if ($request->filled('equipement_id')) {
                \Modules\ParcInfo\Models\AffectationConsommable::create([
                    'consommable_id' => $consommable->id,
                    'equipement_id' => $request->equipement_id,
                    'quantite_fournie' => $request->quantite,
                    'date_affectation' => now(),
                    'notes' => $request->notes,
                ]);
            }

            $consommable->decrement('quantite_stock_actuel', $request->quantite);
        });

        return response()->json(['success' => true, 'message' => 'Sortie de stock enregistrée']);
    }

    public function approvisionner(Request $request, $id)
    {
        $consommable = Consommable::findOrFail($id);

        $request->validate([
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'nullable|numeric|min:0',
            'reference_commande' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($consommable, $request) {
            MouvementConsommable::create([
                'consommable_id' => $consommable->id,
                'type_mouvement' => 'Achat',
                'quantite' => $request->quantite,
                'prix_unitaire' => $request->prix_unitaire ?? $consommable->cout_unitaire,
                'date_mouvement' => now(),
                'utilisateur_id' => auth()->id(),
                'reference_commande' => $request->reference_commande,
            ]);

            $consommable->increment('quantite_stock_actuel', $request->quantite);
            $consommable->update(['date_dernier_approvisionnement' => now()]);
        });

        return response()->json(['success' => true, 'message' => 'Stock mis à jour']);
    }

    public function storeType(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:parc_info_types_consommables,nom',
            'categorie' => 'required|string',
            'unite_stock' => 'required|string',
        ]);

        $code = 'TYPE-CONS-'.strtoupper(substr($validated['nom'], 0, 3)).rand(100, 999);
        $validated['code'] = $code;

        $type = TypeConsommable::create($validated);

        return response()->json(['success' => true, 'data' => $type, 'message' => 'Type de consommable ajouté.']);
    }

    /**
     * EF-STK-05 — la quantité et la valeur affichées proviennent du module
     * Stock ; la fiche ParcInfo reste un catalogue.
     */
    private function formatRow(Consommable $c, array $quantites = [], array $valeurs = []): array
    {
        $quantite = $c->article_id !== null ? (int) ($quantites[$c->article_id] ?? 0) : null;
        $valeur = $c->article_id !== null ? (float) ($valeurs[$c->article_id] ?? 0) : null;

        $statut = match (true) {
            $quantite === null => 'NON SUIVI',
            $quantite === 0 => 'RUPTURE',
            $quantite <= (int) $c->quantite_stock_min => 'ALERTE',
            default => 'NORMAL',
        };

        return [
            'id' => $c->id,
            'code' => $c->code,
            'nom' => $c->nom,
            'type' => $c->typeConsommable->nom,
            'marque' => $c->marque?->libelle ?: 'Générique',
            'stock_actuel' => $quantite ?? '—',
            'unite' => $c->typeConsommable->unite_stock,
            'seuil' => "{$c->quantite_stock_min} / {$c->quantite_stock_max}",
            'statut_stock' => $statut,
            'valeur' => $valeur !== null ? number_format($valeur, 0, ',', ' ').' FCFA' : '—',
            'est_actif' => $c->est_actif,
            'status_label' => $c->est_actif
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>',
        ];
    }
}
