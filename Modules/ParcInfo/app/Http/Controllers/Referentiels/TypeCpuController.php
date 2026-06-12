<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Http\Requests\Referentiels\StoreTypeCpuRequest;
use Modules\ParcInfo\Http\Requests\Referentiels\UpdateTypeCpuRequest;
use Modules\ParcInfo\Models\TypeCpu;

class TypeCpuController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.types-cpus.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:parc-info.referentiels.types-cpus.store', only: ['store']),
            new Middleware('permission:parc-info.referentiels.types-cpus.update', only: ['update']),
            new Middleware('permission:parc-info.referentiels.types-cpus.destroy', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parcinfo::referentiels.types-cpus.index');
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = TypeCpu::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('libelle', 'like', "%{$search}%");
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
    public function store(StoreTypeCpuRequest $request)
    {
        try {
            $item = TypeCpu::create([
                'libelle' => $request->libelle,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de CPU créé avec succès',
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
            $item = TypeCpu::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Type de CPU non trouvé',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTypeCpuRequest $request, $id)
    {
        try {
            $item = TypeCpu::findOrFail($id);
            $item->update([
                'libelle' => $request->libelle,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Type de CPU modifié avec succès',
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
            $item = TypeCpu::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Type de CPU supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
