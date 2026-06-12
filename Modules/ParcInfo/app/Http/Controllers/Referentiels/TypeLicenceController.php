<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Http\Requests\Referentiels\StoreTypeLicenceRequest;
use Modules\ParcInfo\Http\Requests\Referentiels\UpdateTypeLicenceRequest;
use Modules\ParcInfo\Models\TypeLicence;

class TypeLicenceController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.types-licences.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:parc-info.referentiels.types-licences.store', only: ['store']),
            new Middleware('permission:parc-info.referentiels.types-licences.update', only: ['update']),
            new Middleware('permission:parc-info.referentiels.types-licences.destroy', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parcinfo::referentiels.types-licences.index');
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = TypeLicence::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
                $q->orWhere('libelle', 'like', "%{$search}%");
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
    public function store(StoreTypeLicenceRequest $request)
    {
        try {
            $item = TypeLicence::create([
                'code' => $request->code,
                'libelle' => $request->libelle,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de Licence créé avec succès',
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
            $item = TypeLicence::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de Licence non trouvé',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTypeLicenceRequest $request, $id)
    {
        try {
            $item = TypeLicence::findOrFail($id);
            $item->update([
                'code' => $request->code,
                'libelle' => $request->libelle,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de Licence modifié avec succès',
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
            $item = TypeLicence::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type de Licence supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
