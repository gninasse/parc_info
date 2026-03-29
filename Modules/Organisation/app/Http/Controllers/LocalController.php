<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;

class LocalController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.locaux.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.locaux.store', only: ['store']),
            new Middleware('permission:cores.organisation.locaux.update', only: ['update']),
            new Middleware('permission:cores.organisation.locaux.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();

        return view('organisation::organisation.locaux.index', compact('sites'));
    }

    public function getData(Request $request)
    {
        $query = Local::query()->with(['etage', 'etage.batiment', 'etage.batiment.site']);

        if ($request->has('site_id') && ! empty($request->site_id)) {
            $query->whereHas('etage.batiment', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        if ($request->has('batiment_id') && ! empty($request->batiment_id)) {
            $query->whereHas('etage', function ($q) use ($request) {
                $q->where('batiment_id', $request->batiment_id);
            });
        }

        if ($request->has('etage_id') && ! empty($request->etage_id)) {
            $query->where('etage_id', $request->etage_id);
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
        $locaux = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $locaux,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'etage_id' => 'required|exists:organisation_etages,id',
            'code' => 'required|unique:organisation_locaux,code',
            'libelle' => 'required',
            'type_local' => 'required|in:bureau,salle_soins,salle_attente,magasin,couloir,autre',
            'superficie_m2' => 'nullable|numeric|min:0',
        ]);

        try {
            $local = Local::create($request->all());

            return response()->json(['success' => true, 'message' => 'Local créé avec succès', 'data' => $local]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $local = Local::with('etage.batiment.site')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $local]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Local non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'etage_id' => 'required|exists:organisation_etages,id',
            'code' => 'required|unique:organisation_locaux,code,'.$id,
            'libelle' => 'required',
            'type_local' => 'required|in:bureau,salle_soins,salle_attente,magasin,couloir,autre',
            'superficie_m2' => 'nullable|numeric|min:0',
        ]);

        try {
            $local = Local::findOrFail($id);
            $local->update($request->all());

            return response()->json(['success' => true, 'message' => 'Local modifié avec succès', 'data' => $local]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $local = Local::findOrFail($id);

            $local->delete();

            return response()->json(['success' => true, 'message' => 'Local supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    /**
     * Helper AJAX: locaux par étage
     */
    public function getByEtage($etageId)
    {
        $locaux = Local::where('etage_id', $etageId)->actif()->get();

        return response()->json($locaux);
    }

    public function toggleStatus($id)
    {
        try {
            $item = Local::findOrFail($id);
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
