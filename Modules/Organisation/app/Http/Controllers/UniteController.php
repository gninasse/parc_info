<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;

class UniteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.unites.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.unites.store', only: ['store']),
            new Middleware('permission:cores.organisation.unites.update', only: ['update']),
            new Middleware('permission:cores.organisation.unites.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();

        return view('organisation::organisation.unites.index', compact('sites'));
    }

    public function getData(Request $request)
    {
        $query = Unite::query()->with(['service', 'site', 'major']);

        if ($request->has('site_id') && ! empty($request->site_id)) {
            $query->where('site_id', $request->site_id);
        }

        // Pour filtrer par direction, on doit passer par les services
        if ($request->has('direction_id') && ! empty($request->direction_id)) {
            $query->whereHas('service', function ($q) use ($request) {
                $q->where('direction_id', $request->direction_id);
            });
        }

        if ($request->has('service_id') && ! empty($request->service_id)) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $unites = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $unites,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:organisation_services,id',
            'code' => 'required|unique:organisation_unites,code',
            'libelle' => 'required',
            'major_id' => 'nullable|exists:users,id',
        ]);

        try {
            // Note: site_id est rempli automatiquement par l'Observer/Boot du modèle Unite
            $unite = Unite::create($request->all());

            return response()->json(['success' => true, 'message' => 'Unité créée avec succès', 'data' => $unite]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $unite = Unite::with('service.direction.site')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $unite]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Unité non trouvée'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'service_id' => 'required|exists:organisation_services,id',
            'code' => 'required|unique:organisation_unites,code,'.$id,
            'libelle' => 'required',
            'major_id' => 'nullable|exists:users,id',
        ]);

        try {
            $unite = Unite::findOrFail($id);
            $unite->update($request->all());

            return response()->json(['success' => true, 'message' => 'Unité modifiée avec succès', 'data' => $unite]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $unite = Unite::findOrFail($id);

            // Règle métier: vérification des employés (TODO table employes)
            // if ($unite->employes()->count() > 0) { return ... }

            $unite->delete();

            return response()->json(['success' => true, 'message' => 'Unité supprimée avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    // LISTE DES MAJORS
    public function getMajors()
    {
        // Retourne tous les utilisateurs actifs pour l'instant
        $users = User::where('is_active', true)->get();

        return response()->json($users);
    }

    // Helper cascades
    public function getServicesByDirection($directionId)
    {
        $services = Service::where('direction_id', $directionId)->actif()->get();

        return response()->json($services);
    }

    /**
     * Toggle status (actif/inactif).
     */
    public function toggleStatus($id)
    {
        try {
            $item = Unite::findOrFail($id);
            $item->actif = ! $item->actif;
            $item->save();

            return response()->json([
                'success' => true,
                'message' => $item->actif ? 'Élément activé avec succès' : 'Élément désactivé avec succès',
                'actif' => $item->actif,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du changement de statut',
            ], 500);
        }
    }
}
