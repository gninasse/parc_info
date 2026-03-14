<?php

namespace Modules\Organisation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Organisation\Models\Etage;
use Modules\Organisation\Models\Site;

class EtageController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:cores.organisation.etages.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:cores.organisation.etages.store', only: ['store']),
            new Middleware('permission:cores.organisation.etages.update', only: ['update']),
            new Middleware('permission:cores.organisation.etages.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        $sites = Site::actif()->get();

        return view('organisation::organisation.etages.index', compact('sites'));
    }

    public function getData(Request $request)
    {
        $query = Etage::query()->with(['batiment', 'batiment.site']);

        if ($request->has('site_id') && ! empty($request->site_id)) {
            $query->whereHas('batiment', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }

        if ($request->has('batiment_id') && ! empty($request->batiment_id)) {
            $query->where('batiment_id', $request->batiment_id);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                    ->orWhere('numero', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $etages = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $etages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'batiment_id' => 'required|exists:organisation_batiments,id',
            'numero' => 'required|integer',
            'libelle' => 'required',
        ]);

        // Vérifier unicité batiment_id + numero
        $exists = Etage::where('batiment_id', $request->batiment_id)
            ->where('numero', $request->numero)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Ce numéro d\'étage existe déjà pour ce bâtiment.'], 422);
        }

        try {
            $etage = Etage::create($request->all());

            return response()->json(['success' => true, 'message' => 'Étage créé avec succès', 'data' => $etage]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $etage = Etage::with('batiment.site')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $etage]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Étage non trouvé'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'batiment_id' => 'required|exists:organisation_batiments,id',
            'numero' => 'required|integer',
            'libelle' => 'required',
        ]);

        // Vérifier unicité batiment_id + numero (exclure l'enregistrement courant)
        $exists = Etage::where('batiment_id', $request->batiment_id)
            ->where('numero', $request->numero)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Ce numéro d\'étage existe déjà pour ce bâtiment.'], 422);
        }

        try {
            $etage = Etage::findOrFail($id);
            $etage->update($request->all());

            return response()->json(['success' => true, 'message' => 'Étage modifié avec succès', 'data' => $etage]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $etage = Etage::findOrFail($id);

            // Règle métier: pas de suppression si locaux actifs
            if ($etage->locaux()->actif()->count() > 0) {
                return response()->json(['success' => false, 'message' => 'Impossible de supprimer cet étage car il contient des locaux actifs.'], 422);
            }

            $etage->delete();

            return response()->json(['success' => true, 'message' => 'Étage supprimé avec succès']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur: '.$e->getMessage()], 500);
        }
    }

    /**
     * Helper AJAX: étages par bâtiment
     */
    public function getByBatiment($batimentId)
    {
        $etages = Etage::where('batiment_id', $batimentId)->actif()->get();

        return response()->json($etages);
    }

    public function toggleStatus($id)
    {
        try {
            $item = Etage::findOrFail($id);
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
