<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Http\Requests\Referentiels\StoreTypeConsommableRequest;
use Modules\ParcInfo\Http\Requests\Referentiels\UpdateTypeConsommableRequest;
use Modules\ParcInfo\Models\TypeConsommable;

class TypeConsommableController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.types-consommables.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:parc-info.referentiels.types-consommables.store', only: ['store']),
            new Middleware('permission:parc-info.referentiels.types-consommables.update', only: ['update']),
            new Middleware('permission:parc-info.referentiels.types-consommables.destroy', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parcinfo::referentiels.types-consommables.index');
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = TypeConsommable::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
                $q->orWhere('nom', 'like', "%{$search}%");
                $q->orWhere('categorie', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $rows = $query->offset($offset)->limit($limit)->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTypeConsommableRequest $request)
    {
        try {
            $item = TypeConsommable::create([
                'code' => $request->code,
                'nom' => $request->nom,
                'categorie' => $request->categorie,
                'sous_categorie' => $request->sous_categorie,
                'unite_stock' => $request->unite_stock,
                'seul_reapprovisionnement' => $request->seul_reapprovisionnement,
                'duree_conservation_jours' => $request->duree_conservation_jours,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de Consommable créé avec succès',
                'data' => $item,
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
            $item = TypeConsommable::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de Consommable non trouvé',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTypeConsommableRequest $request, $id)
    {
        try {
            $item = TypeConsommable::findOrFail($id);
            $item->update([
                'code' => $request->code,
                'nom' => $request->nom,
                'categorie' => $request->categorie,
                'sous_categorie' => $request->sous_categorie,
                'unite_stock' => $request->unite_stock,
                'seul_reapprovisionnement' => $request->seul_reapprovisionnement,
                'duree_conservation_jours' => $request->duree_conservation_jours,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de Consommable modifié avec succès',
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $item = TypeConsommable::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type de Consommable supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
