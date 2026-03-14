<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Batiment;
use Modules\Organisation\Models\Site;

class BatimentController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.batiments.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.batiments.store', only: ['store']),
            new Middleware('permission:cores.organisation.batiments.update', only: ['update']),
            new Middleware('permission:cores.organisation.batiments.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();

        return view('organisation::organisation.batiments.index', compact('sites'));
    }

    public function getData(Request $request)
    {
        $query = Batiment::query()->with(['site']);

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
        $batiments = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $batiments,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id' => 'required|exists:organisation_sites,id',
            'code' => 'required|unique:organisation_batiments,code',
            'libelle' => 'required',
            'description' => 'nullable|string',
            'nombre_etages' => 'nullable|integer|min:0',
        ]);

        try {
            $batiment = Batiment::create($request->all());

            return response()->json(['success' => true, 'message' => 'Bâtiment créé avec succès', 'data' => $batiment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $batiment = Batiment::with('site')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $batiment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Bâtiment non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'site_id' => 'required|exists:organisation_sites,id',
            'code' => 'required|unique:organisation_batiments,code,'.$id,
            'libelle' => 'required',
            'description' => 'nullable|string',
            'nombre_etages' => 'nullable|integer|min:0',
        ]);

        try {
            $batiment = Batiment::findOrFail($id);
            $batiment->update($request->all());

            return response()->json(['success' => true, 'message' => 'Bâtiment modifié avec succès', 'data' => $batiment]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $batiment = Batiment::findOrFail($id);

            // Règle métier: pas de suppression si étages actifs
            if ($batiment->etages()->actif()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer ce bâtiment car il contient des étages actifs.'], 422);
            }

            $batiment->delete();

            return response()->json(['success' => true, 'message' => 'Bâtiment supprimé (désactivé) avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    /**
     * Helper AJAX: bâtiments par site
     */
    public function getBySite($siteId)
    {
        $batiments = Batiment::where('site_id', $siteId)->actif()->get();

        return response()->json($batiments);
    }

    public function toggleStatus($id)
    {
        try {
            $item = Batiment::findOrFail($id);
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
