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

class ServiceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.services.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.services.store', only: ['store']),
            new Middleware('permission:cores.organisation.services.update', only: ['update']),
            new Middleware('permission:cores.organisation.services.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();
        // Pas de directions chargées initialement, chargées par AJAX selon le site
        $users = User::where('is_active', true)->get();

        return view('organisation::organisation.services.index', compact('sites', 'users'));
    }

    public function getData(Request $request)
    {
        $query = Service::query()->with(['direction', 'site', 'chefService']);

        if ($request->has('site_id') && ! empty($request->site_id)) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('direction_id') && ! empty($request->direction_id)) {
            $query->where('direction_id', $request->direction_id);
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
        $services = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $services,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'direction_id' => 'required|exists:organisation_directions,id',
            'code' => 'required|unique:organisation_services,code',
            'libelle' => 'required',
            'type_service' => 'required|in:administratif,clinique,medico_technique',
            'chef_service_id' => 'nullable|exists:users,id',
        ]);

        try {
            // Note: site_id est rempli automatiquement par l'Observer/Boot du modèle Service
            $service = Service::create($request->all());

            return response()->json(['success' => true, 'message' => 'Service créé avec succès', 'data' => $service]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $service = Service::with('direction')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $service]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Service non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'direction_id' => 'required|exists:organisation_directions,id',
            'code' => 'required|unique:organisation_services,code,'.$id,
            'libelle' => 'required',
            'type_service' => 'required|in:administratif,clinique,medico_technique',
            'chef_service_id' => 'nullable|exists:users,id',
        ]);

        try {
            $service = Service::findOrFail($id);
            $service->update($request->all());

            return response()->json(['success' => true, 'message' => 'Service modifié avec succès', 'data' => $service]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $service = Service::findOrFail($id);

            // Règle métier: pas de suppression si unités actives
            if ($service->unites()->actif()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer ce service car il contient des unités actives.'], 422);
            }

            $service->delete();

            return response()->json(['success' => true, 'message' => 'Service supprimé (désactivé) avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    // Helper pour charger les directions d'un site par AJAX
    public function getDirectionsBySite($siteId)
    {
        $directions = Direction::where('site_id', $siteId)->actif()->get();

        return response()->json($directions);
    }

    /**
     * Toggle status (actif/inactif).
     */
    public function toggleStatus($id)
    {
        try {
            $item = Service::findOrFail($id);
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
