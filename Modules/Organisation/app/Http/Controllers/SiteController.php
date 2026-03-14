<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Site;

class SiteController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.sites.index', only: ['index', 'getData', 'show', 'getArborescence']),
            new Middleware('permission:cores.organisation.sites.store', only: ['store']),
            new Middleware('permission:cores.organisation.sites.update', only: ['update']),
            new Middleware('permission:cores.organisation.sites.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        return view('organisation::organisation.sites.index');
    }

    public function getData(Request $request)
    {
        $query = Site::query();

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
        $sites = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $sites,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:organisation_sites,code',
            'libelle' => 'required',
            'adresse' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $site = Site::create($request->all());

            return response()->json(['success' => true, 'message' => 'Site créé avec succès', 'data' => $site]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $site = Site::findOrFail($id);

            return response()->json(['success' => true, 'data' => $site]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Site non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code' => 'required|unique:organisation_sites,code,'.$id,
            'libelle' => 'required',
            'adresse' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        try {
            $site = Site::findOrFail($id);
            $site->update($request->all());

            return response()->json(['success' => true, 'message' => 'Site modifié avec succès', 'data' => $site]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $site = Site::findOrFail($id);

            // Règle métier: pas de suppression si directions actives
            if ($site->directions()->actif()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer ce site car il contient des directions actives.'], 422);
            }

            $site->delete();

            return response()->json(['success' => true, 'message' => 'Site supprimé (désactivé) avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function getArborescence()
    {
        try {
            $sites = Site::with(['directions.services.unites'])
                ->actif()
                ->get();

            return response()->json(['success' => true, 'data' => $sites]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    /**
     * Toggle status (actif/inactif).
     */
    public function toggleStatus($id)
    {
        try {
            $item = Site::findOrFail($id);
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
