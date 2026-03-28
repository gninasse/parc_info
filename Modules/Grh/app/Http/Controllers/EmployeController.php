<?php

namespace Modules\Grh\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Grh\Http\Requests\StoreEmployeRequest;
use Modules\Grh\Http\Requests\UpdateEmployeRequest;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;

class EmployeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $directions = Direction::where('actif', true)->get();
        $services = Service::where('actif', true)->get();
        $unites = Unite::where('actif', true)->get();

        return view('grh::employes.index', compact('directions', 'services', 'unites'));
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = Employe::with(['direction', 'service', 'unite']);

        // Recherche
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                    ->orWhere('prenom', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->filled('direction_id')) {
            $query->where('direction_id', $request->direction_id);
        }
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }
        if ($request->filled('unite_id')) {
            $query->where('unite_id', $request->unite_id);
        }
        if ($request->filled('est_actif')) {
            $query->where('est_actif', $request->est_actif == '1');
        }

        // Tri
        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $employes = $query->offset($offset)->limit($limit)->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'matricule' => $emp->matricule,
                'full_name' => $emp->full_name,
                'poste' => $emp->poste,
                'niveau' => ucfirst($emp->niveau_rattachement),
                'rattachement' => $emp->organisation,
                'est_actif' => $emp->est_actif,
                'created_at' => $emp->created_at->format('d/m/Y'),
            ];
        });

        return response()->json([
            'total' => $total,
            'rows' => $employes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeRequest $request)
    {
        try {
            $employe = Employe::create($request->validated());

            if ($request->has('contacts')) {
                foreach ($request->contacts as $contactData) {
                    if (! empty($contactData['valeur'])) {
                        $employe->contacts()->create($contactData);
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Employé créé avec succès',
                'data' => $employe,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        try {
            $employe = Employe::with(['contacts', 'direction', 'service', 'unite'])->findOrFail($id);

            $directions = Direction::where('actif', true)->get();
            $services = Service::where('actif', true)->get();
            $unites = Unite::where('actif', true)->get();

            return view('grh::employes.show', compact('employe', 'directions', 'services', 'unites'));
        } catch (\Exception $e) {
            abort(404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeRequest $request, $id)
    {
        try {
            $employe = Employe::findOrFail($id);
            $employe->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Dossier employé mis à jour avec succès',
                'data' => $employe,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle status.
     */
    public function toggleStatus($id)
    {
        try {
            $employe = Employe::findOrFail($id);
            $employe->est_actif = ! $employe->est_actif;
            $employe->save();

            $status = $employe->est_actif ? 'activé' : 'désactivé';

            return response()->json([
                'success' => true,
                'message' => "Employé $status avec succès",
                'est_actif' => $employe->est_actif,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur : '.$e->getMessage(),
            ], 500);
        }
    }
}
