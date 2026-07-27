<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Http\Requests\StoreConsommableRequest;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeConsommable;

class ConsommableController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parcinfo.consommables.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:parcinfo.consommables.store', only: ['store', 'storeType']),
            new Middleware('permission:parcinfo.consommables.update', only: ['update', 'toggleStatus']),
            new Middleware('permission:parcinfo.consommables.destroy', only: ['destroy']),
        ];
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

        $total = (clone $query)->count();

        $rows = $query->limit((int) $request->get('limit', 25))
            ->offset((int) $request->get('offset', 0))
            ->get()
            ->map(fn ($c) => $this->formatRow($c));

        return response()->json([
            'total' => $total,
            'rows' => $rows,
            'stats' => [
                'total' => Consommable::count(),
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
        $consommable->delete();

        return response()->json(['success' => true, 'message' => 'Consommable supprimé.']);
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

    private function formatRow(Consommable $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->code,
            'nom' => $c->nom,
            'type' => $c->typeConsommable->nom,
            'marque' => $c->marque?->libelle ?: 'Générique',
            'unite' => $c->typeConsommable->unite_stock,
            'est_actif' => $c->est_actif,
            'status_label' => $c->est_actif
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>',
        ];
    }
}
