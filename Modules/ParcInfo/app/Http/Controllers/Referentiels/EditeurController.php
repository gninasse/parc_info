<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Http\Requests\Referentiels\StoreEditeurRequest;
use Modules\ParcInfo\Http\Requests\Referentiels\UpdateEditeurRequest;
use Modules\ParcInfo\Models\Editeur;

class EditeurController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.editeurs.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:parc-info.referentiels.editeurs.store', only: ['store']),
            new Middleware('permission:parc-info.referentiels.editeurs.update', only: ['update']),
            new Middleware('permission:parc-info.referentiels.editeurs.destroy', only: ['destroy']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parcinfo::referentiels.editeurs.index');
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = Editeur::query();

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%");
                $q->orWhere('nom', 'like', "%{$search}%");
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
    public function store(StoreEditeurRequest $request)
    {
        try {
            $item = Editeur::create([
                'code' => $request->code,
                'nom' => $request->nom,
                'logo_url' => $request->logo_url,
                'site_web' => $request->site_web,
                'email_support' => $request->email_support,
                'telephone_support' => $request->telephone_support,
                'est_actif' => $request->boolean('est_actif', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Éditeur créé avec succès',
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
            $item = Editeur::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Éditeur non trouvé',
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEditeurRequest $request, $id)
    {
        try {
            $item = Editeur::findOrFail($id);
            $item->update([
                'code' => $request->code,
                'nom' => $request->nom,
                'logo_url' => $request->logo_url,
                'site_web' => $request->site_web,
                'email_support' => $request->email_support,
                'telephone_support' => $request->telephone_support,
                'est_actif' => $request->boolean('est_actif', true),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Éditeur modifié avec succès',
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
            $item = Editeur::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Éditeur supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
