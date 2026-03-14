<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;

class DirectionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.directions.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.directions.store', only: ['store']),
            new Middleware('permission:cores.organisation.directions.update', only: ['update']),
            new Middleware('permission:cores.organisation.directions.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();
        // Assuming we want active users for responsible selection
        $users = User::where('is_active', true)->get();
        // dd();

        return view('organisation::organisation.directions.index', compact('sites', 'users'));
    }

    public function getData(Request $request)
    {
        $query = Direction::query()->with(['site', 'responsable']);

        if ($request->has('site_id') && ! empty($request->site_id)) {
            $query->where('site_id', $request->site_id);
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
        $directions = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $directions,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:organisation_sites,id',
            'code' => 'required|unique:organisation_directions,code',
            'libelle' => 'required',
            'responsable_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        try {
            $direction = Direction::create($request->all());

            return response()->json(['success' => true, 'message' => 'Direction créée avec succès', 'data' => $direction]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $direction = Direction::with('site')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $direction]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Direction non trouvée'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'site_id' => 'required|exists:organisation_sites,id',
            'code' => 'required|unique:organisation_directions,code,'.$id,
            'libelle' => 'required',
            'responsable_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        try {
            $direction = Direction::findOrFail($id);
            $direction->update($request->all());

            return response()->json(['success' => true, 'message' => 'Direction modifiée avec succès', 'data' => $direction]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $direction = Direction::findOrFail($id);

            // Règle métier: pas de suppression si services actifs
            if ($direction->services()->actif()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer cette direction car elle contient des services actifs.'], 422);
            }

            $direction->delete();

            return response()->json(['success' => true, 'message' => 'Direction supprimée (désactivée) avec succès']);
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
            $item = Direction::findOrFail($id);
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
