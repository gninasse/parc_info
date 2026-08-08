<?php

namespace Modules\Catalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Http\Requests\StoreCategorieRequest;
use Modules\Catalogue\Http\Requests\UpdateCategorieRequest;
use Modules\Catalogue\Models\Categorie;

class CategorieController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:catalogue.categories.index', only: ['index', 'getData', 'show', 'getParents']),
            new Middleware('permission:catalogue.categories.store', only: ['store']),
            new Middleware('permission:catalogue.categories.update', only: ['update']),
            new Middleware('permission:catalogue.categories.destroy', only: ['destroy']),
            new Middleware('permission:catalogue.categories.toggle-status', only: ['toggleStatus']),
        ];
    }

    public function index()
    {
        return view('catalogue::categories.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Categorie::query()->with('parent')->withCount('articles as nb_articles');

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(libelle) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('est_actif')) {
            $query->where('est_actif', (bool) $request->input('est_actif'));
        }

        $sort = $request->input('sort');
        $allowed = ['id', 'code', 'libelle', 'nb_articles', 'est_actif', 'created_at'];
        if (in_array($sort, $allowed, true)) {
            $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
            $query->orderBy($sort, $order);
        } else {
            // Tri arborescent portable : chaque parent suivi de ses enfants
            $query->orderByRaw('COALESCE(parent_id, id), (CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END), libelle');
        }

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Categorie $c) => [
                'id' => $c->id,
                'code' => $c->code,
                'libelle' => $c->libelle,
                'parent_libelle' => $c->parent?->libelle,
                'niveau' => $c->parent_id === null ? 1 : 2,
                'nb_articles' => $c->nb_articles,
                'est_actif' => $c->est_actif,
                'est_systeme' => $c->est_systeme,
                'created_at' => $c->created_at?->format('d/m/Y'),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /**
     * Niveaux 1 actifs — alimente le select parent de la modale (cascade C2).
     */
    public function getParents(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Categorie::actif()
                ->whereNull('parent_id')
                ->orderBy('libelle')
                ->get(['id', 'libelle']),
        ]);
    }

    public function show($id): JsonResponse
    {
        $categorie = Categorie::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $categorie->only(['id', 'code', 'libelle', 'parent_id', 'est_actif', 'est_systeme']),
        ]);
    }

    public function store(StoreCategorieRequest $request): JsonResponse
    {
        try {
            $categorie = Categorie::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => "La catégorie « {$categorie->libelle} » a été créée avec succès.",
                'data' => ['id' => $categorie->id, 'code' => $categorie->code],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création de la catégorie', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function update(UpdateCategorieRequest $request, $id): JsonResponse
    {
        $categorie = Categorie::findOrFail($id);

        try {
            $categorie->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "La catégorie « {$categorie->libelle} » a été modifiée avec succès.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification de la catégorie', ['exception' => $e, 'categorie_id' => $categorie->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $categorie = Categorie::withCount(['articles as nb_articles', 'enfants as nb_enfants'])->findOrFail($id);

        $motifs = [];
        if ($categorie->est_systeme) {
            // C7 : les catégories « Non classé » seedées sont protégées par le
            // drapeau est_systeme (posé en migration) plutôt que par une liste
            // de codes en dur.
            $motifs[] = 'catégorie système « Non classé », insupprimable';
        }
        if ($categorie->nb_enfants > 0) {
            $motifs[] = "{$categorie->nb_enfants} sous-catégorie(s) existe(nt)";
        }
        if ($categorie->nb_articles > 0) {
            $motifs[] = "{$categorie->nb_articles} article(s) rattaché(s)";
        }

        if ($motifs !== []) {
            return response()->json([
                'success' => false,
                'message' => 'Suppression impossible : '.implode(' ; ', $motifs).'.',
            ], 422);
        }

        try {
            $categorie->delete();

            return response()->json([
                'success' => true,
                'message' => "La catégorie « {$categorie->libelle} » a été supprimée.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la suppression de la catégorie', ['exception' => $e, 'categorie_id' => $categorie->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /**
     * Désactiver un parent ayant des enfants actifs exige une confirmation :
     * 1er appel → requires_confirmation, 2e appel avec cascade=true →
     * désactivation en cascade (le front porte le Swal à double option).
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $categorie = Categorie::findOrFail($id);

        try {
            if ($categorie->est_actif) {
                $enfantsActifs = $categorie->enfants()->where('est_actif', true)->count();

                if ($enfantsActifs > 0 && ! $request->boolean('cascade')) {
                    return response()->json([
                        'success' => false,
                        'requires_confirmation' => true,
                        'message' => "{$enfantsActifs} sous-catégorie(s) active(s) seront également désactivées.",
                        'data' => ['enfants_actifs' => $enfantsActifs],
                    ]);
                }

                DB::transaction(function () use ($categorie) {
                    $categorie->update(['est_actif' => false]);
                    $categorie->enfants()->where('est_actif', true)
                        ->get()
                        ->each(fn (Categorie $enfant) => $enfant->update(['est_actif' => false]));
                });

                $message = "La catégorie « {$categorie->libelle} » a été désactivée".
                    ($request->boolean('cascade') ? ' ainsi que ses sous-catégories.' : '.');
            } else {
                $categorie->update(['est_actif' => true]);
                $message = "La catégorie « {$categorie->libelle} » a été activée.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['est_actif' => $categorie->fresh()->est_actif],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur au changement de statut de la catégorie', ['exception' => $e, 'categorie_id' => $categorie->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }
}
