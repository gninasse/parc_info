<?php

namespace Modules\ParcInfo\Http\Controllers\Referentiels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\ChampConfig;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CategorieController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.referentiels.categories.index', only: ['index', 'getData', 'show', 'getDataFields']),
            new Middleware('permission:parc-info.referentiels.categories.store', only: ['store', 'storeField']),
            new Middleware('permission:parc-info.referentiels.categories.update', only: ['update', 'updateField']),
            new Middleware('permission:parc-info.referentiels.categories.destroy', only: ['destroy', 'destroyField']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('parcinfo::referentiels.categories.index');
    }

    /**
     * Get data for Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = CategorieEquipement::query()->withCount('champs', 'equipements');

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

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'libelle' => 'required|string|max:255|unique:parc_info_categories_equipements,libelle',
            'code' => 'required|string|max:255|unique:parc_info_categories_equipements,code|regex:/^[a-z0-9\-]+$/',
            'icone' => 'nullable|string|max:255',
        ], [
            'code.regex' => 'Le code ne doit contenir que des lettres minuscules, chiffres et tirets.',
        ]);

        try {
            $item = DB::transaction(function () use ($request) {
                $category = CategorieEquipement::create([
                    'libelle' => $request->libelle,
                    'code' => $request->code,
                    'icone' => $request->icone ?: 'bi-cpu',
                ]);

                // Create Spatie Permissions dynamically
                $plural = Str::plural($category->code);
                $actions = [
                    'index' => 'Voir la liste des '.strtolower($category->libelle),
                    'store' => 'Créer un/une '.strtolower($category->libelle),
                    'update' => 'Modifier un/une '.strtolower($category->libelle),
                    'destroy' => 'Supprimer un/une '.strtolower($category->libelle),
                ];

                foreach ($actions as $action => $label) {
                    $permName = "parcinfo.{$plural}.{$action}";
                    $permission = Permission::firstOrCreate(
                        ['name' => $permName],
                        [
                            'label' => $label,
                            'description' => $label,
                            'module' => 'parcinfo',
                            'guard_name' => 'web',
                            'category' => $action === 'index' ? 'view' : ($action === 'destroy' ? 'delete' : ($action === 'store' ? 'create' : 'edit')),
                        ]
                    );

                    // Assign to Admin and super-admin roles if they exist
                    $adminRole = Role::where('name', 'Admin')->first();
                    if ($adminRole) {
                        $adminRole->givePermissionTo($permission);
                    }
                    $superAdminRole = Role::where('name', 'super-admin')->first();
                    if ($superAdminRole) {
                        $superAdminRole->givePermissionTo($permission);
                    }
                }

                return $category;
            });

            return response()->json([
                'success' => true,
                'message' => 'Catégorie d\'équipement créée avec succès',
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
        $category = CategorieEquipement::findOrFail($id);

        if (request()->wantsJson() || request()->ajax() || request()->has('json')) {
            return response()->json([
                'success' => true,
                'data' => $category,
            ]);
        }

        // Fetch all system dictionaries for dynamic source dropdown selection
        $dictionaries = DB::table('parc_info_dictionnaires')->orderBy('libelle')->get(['code', 'libelle']);

        return view('parcinfo::referentiels.categories.show', compact('category', 'dictionaries'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = CategorieEquipement::findOrFail($id);

        $request->validate([
            'libelle' => 'required|string|max:255|unique:parc_info_categories_equipements,libelle,'.$id,
            'icone' => 'nullable|string|max:255',
        ]);

        try {
            $category->update([
                'libelle' => $request->libelle,
                'icone' => $request->icone ?: 'bi-cpu',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Catégorie d\'équipement modifiée avec succès',
                'data' => $category,
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
        $category = CategorieEquipement::findOrFail($id);

        if ($category->equipements()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette catégorie car elle contient des équipements actifs.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($category) {
                // Delete fields configurations
                $category->champs()->delete();

                // Delete permissions
                $plural = Str::plural($category->code);
                Permission::where('name', 'like', "parcinfo.{$plural}.%")->delete();

                $category->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Catégorie d\'équipement supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get dynamic fields configured for category.
     */
    public function getDataFields(Request $request, $id)
    {
        $category = CategorieEquipement::findOrFail($id);
        $query = ChampConfig::where('categorie_id', $category->id);

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('libelle', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'ordre_affichage');
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
     * Add a dynamic field to category.
     */
    public function storeField(Request $request, $id)
    {
        $category = CategorieEquipement::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:255|regex:/^[a-z0-9_]+$/|unique:parc_info_champs_config,code,NULL,id,categorie_id,'.$category->id,
            'libelle' => 'required|string|max:255',
            'type_champ' => 'required|in:text,number,select,boolean,date',
            'source_options' => 'nullable|string',
            'regles_validation' => 'nullable|string|max:255',
            'nom_panel' => 'required|string|max:255',
            'ordre_affichage' => 'required|integer|min:1',
            'afficher_dans_modal' => 'boolean',
            'afficher_dans_show' => 'boolean',
            'afficher_dans_liste' => 'boolean',
            'ordre_colonne_liste' => 'nullable|integer|min:1',
        ], [
            'code.regex' => 'Le code du champ ne doit contenir que des lettres minuscules, chiffres et tirets bas.',
            'code.unique' => 'Ce code est déjà utilisé pour un champ de cette catégorie.',
        ]);

        try {
            $field = ChampConfig::create([
                'categorie_id' => $category->id,
                'code' => $request->code,
                'libelle' => $request->libelle,
                'type_champ' => $request->type_champ,
                'source_options' => $request->source_options,
                'regles_validation' => $request->regles_validation,
                'nom_panel' => $request->nom_panel,
                'ordre_affichage' => $request->ordre_affichage,
                'afficher_dans_modal' => $request->boolean('afficher_dans_modal'),
                'afficher_dans_show' => $request->boolean('afficher_dans_show'),
                'afficher_dans_liste' => $request->boolean('afficher_dans_liste'),
                'ordre_colonne_liste' => $request->filled('ordre_colonne_liste') ? (int) $request->ordre_colonne_liste : 99,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Champ de configuration ajouté avec succès',
                'data' => $field,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show field config.
     */
    public function showField($id, $fieldId)
    {
        try {
            $field = ChampConfig::where('categorie_id', $id)->findOrFail($fieldId);

            return response()->json([
                'success' => true,
                'data' => $field,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Champ de configuration non trouvé',
            ], 404);
        }
    }

    /**
     * Update dynamic field details.
     */
    public function updateField(Request $request, $id, $fieldId)
    {
        $field = ChampConfig::where('categorie_id', $id)->findOrFail($fieldId);

        $request->validate([
            'libelle' => 'required|string|max:255',
            'type_champ' => 'required|in:text,number,select,boolean,date',
            'source_options' => 'nullable|string',
            'regles_validation' => 'nullable|string|max:255',
            'nom_panel' => 'required|string|max:255',
            'ordre_affichage' => 'required|integer|min:1',
            'afficher_dans_modal' => 'boolean',
            'afficher_dans_show' => 'boolean',
            'afficher_dans_liste' => 'boolean',
            'ordre_colonne_liste' => 'nullable|integer|min:1',
        ]);

        try {
            $field->update([
                'libelle' => $request->libelle,
                'type_champ' => $request->type_champ,
                'source_options' => $request->source_options,
                'regles_validation' => $request->regles_validation,
                'nom_panel' => $request->nom_panel,
                'ordre_affichage' => $request->ordre_affichage,
                'afficher_dans_modal' => $request->boolean('afficher_dans_modal'),
                'afficher_dans_show' => $request->boolean('afficher_dans_show'),
                'afficher_dans_liste' => $request->boolean('afficher_dans_liste'),
                'ordre_colonne_liste' => $request->filled('ordre_colonne_liste') ? (int) $request->ordre_colonne_liste : 99,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Champ de configuration modifié avec succès',
                'data' => $field,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete dynamic field configuration.
     */
    public function destroyField($id, $fieldId)
    {
        $field = ChampConfig::where('categorie_id', $id)->findOrFail($fieldId);

        try {
            $field->delete();

            return response()->json([
                'success' => true,
                'message' => 'Champ de configuration supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression : '.$e->getMessage(),
            ], 500);
        }
    }
}
