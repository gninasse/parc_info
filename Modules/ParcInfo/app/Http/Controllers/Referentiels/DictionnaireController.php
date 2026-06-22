<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Modules\ParcInfo\Models\Dictionnaire;
use Modules\ParcInfo\Models\DictionnaireValeur;

class DictionnaireController extends Controller implements HasMiddleware
{
    /**
     * List of system dictionary codes that cannot be deleted or renamed.
     */
    protected array $systemCodes = [
        'type_cpu',
        'type_ram',
        'type_disque',
        'type_os',
        'type_imprimante',
        'type_mobile',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.dictionnaires.index', only: ['index', 'getData']),
            new Middleware('permission:parc-info.referentiels.dictionnaires.store', only: ['store']),
            new Middleware('permission:parc-info.referentiels.dictionnaires.update', only: ['update']),
            new Middleware('permission:parc-info.referentiels.dictionnaires.destroy', only: ['destroy']),
            new Middleware('permission:parc-info.referentiels.dictionnaires.manage', only: ['indexValues', 'getDataValues', 'storeValue', 'showValue', 'updateValue', 'destroyValue']),
        ];
    }

    /**
     * Display the index view for dictionaries.
     */
    public function index(): View
    {
        return view('parcinfo::referentiels.dictionnaires.index');
    }

    /**
     * Get dictionaries list for Bootstrap Table.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = Dictionnaire::query()->withCount('valeurs');

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
        $rows = $query->offset($offset)->limit($limit)->get();

        // Add a read-only flag for system dictionaries
        foreach ($rows as $row) {
            $row->is_system = in_array($row->code, $this->systemCodes);
        }

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Store a newly created dictionary.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'libelle' => 'required|string|max:255|unique:parc_info_dictionnaires,libelle',
            'code' => 'required|string|max:255|unique:parc_info_dictionnaires,code|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string|max:500',
        ], [
            'code.regex' => 'Le code ne doit contenir que des lettres minuscules, chiffres et tirets bas.',
        ]);

        try {
            $item = Dictionnaire::create([
                'libelle' => $request->libelle,
                'code' => $request->code,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dictionnaire créé avec succès',
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
     * Update the specified dictionary in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $item = Dictionnaire::findOrFail($id);

        $request->validate([
            'libelle' => 'required|string|max:255|unique:parc_info_dictionnaires,libelle,'.$id,
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $item->update([
                'libelle' => $request->libelle,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Dictionnaire modifié avec succès',
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
     * Remove the specified dictionary from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = Dictionnaire::findOrFail($id);

        if (in_array($item->code, $this->systemCodes)) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un dictionnaire système.',
            ], 422);
        }

        if ($item->valeurs()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer ce dictionnaire car il contient des valeurs.',
            ], 422);
        }

        try {
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Dictionnaire supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display values list of a specific dictionary.
     */
    public function indexValues(string $code): View
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();

        return view('parcinfo::referentiels.dictionnaires.values', compact('dictionnaire'));
    }

    /**
     * Get values data for Bootstrap Table.
     */
    public function getDataValues(Request $request, string $code): JsonResponse
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();
        $query = DictionnaireValeur::where('dictionnaire_id', $dictionnaire->id);

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where('valeur', 'like', "%{$search}%");
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
     * Store a newly created value for the dictionary.
     */
    public function storeValue(Request $request, string $code): JsonResponse
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();

        $request->validate([
            'valeur' => 'required|string|max:255|unique:parc_info_dictionnaire_valeurs,valeur,NULL,id,dictionnaire_id,'.$dictionnaire->id,
            'description' => 'nullable|string|max:500',
        ], [
            'valeur.unique' => 'Cette valeur existe déjà dans ce dictionnaire.',
        ]);

        try {
            $item = DictionnaireValeur::create([
                'dictionnaire_id' => $dictionnaire->id,
                'valeur' => $request->valeur,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Valeur ajoutée avec succès',
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
     * Show details of a specific value.
     */
    public function showValue(string $code, int $id): JsonResponse
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();
        $item = DictionnaireValeur::where('dictionnaire_id', $dictionnaire->id)->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Update details of a dictionary value.
     */
    public function updateValue(Request $request, string $code, int $id): JsonResponse
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();
        $item = DictionnaireValeur::where('dictionnaire_id', $dictionnaire->id)->findOrFail($id);

        $request->validate([
            'valeur' => 'required|string|max:255|unique:parc_info_dictionnaire_valeurs,valeur,'.$id.',id,dictionnaire_id,'.$dictionnaire->id,
            'description' => 'nullable|string|max:500',
        ], [
            'valeur.unique' => 'Cette valeur existe déjà dans ce dictionnaire.',
        ]);

        try {
            $item->update([
                'valeur' => $request->valeur,
                'description' => $request->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Valeur modifiée avec succès',
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
     * Delete a dictionary value.
     */
    public function destroyValue(string $code, int $id): JsonResponse
    {
        $dictionnaire = Dictionnaire::where('code', $code)->firstOrFail();
        $item = DictionnaireValeur::where('dictionnaire_id', $dictionnaire->id)->findOrFail($id);

        try {
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Valeur supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
