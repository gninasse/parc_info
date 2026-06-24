<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Requests\StoreArticleRequest;
use Modules\Achat\Http\Requests\UpdateArticleRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Services\ArticleService;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;

class ArticleController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected ArticleService $articleService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('achat.articles.view');

        if ($request->ajax() || $request->wantsJson()) {
            $filtres = [
                'type_article' => $request->input('type_article'),
                'marque_id' => $request->input('marque_id'),
                'categorie_equipement_id' => $request->input('categorie_equipement_id'),
                'actif' => $request->input('actif'),
                'recherche' => $request->input('search'), // Bootstrap Table utilise 'search'
            ];

            $query = $this->articleService->lister($filtres);

            // Pagination Bootstrap Table
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $total = $query->count();

            $rows = $query->limit($limit)
                ->offset($offset)
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'code_article' => $article->code_article,
                        'designation' => $article->designation,
                        'type_article' => $article->type_article,
                        'type_label' => config("achat.types_articles.{$article->type_article}", $article->type_article),
                        'reference_constructeur' => $article->reference_constructeur ?? '-',
                        'marque' => $article->marque->libelle,
                        'categorie' => $article->categorie ? $article->categorie->libelle : '-',
                        'prix_indicatif' => $article->prix_indicatif,
                        'stock_actuel' => $article->stock_actuel,
                        'seuil_alerte' => $article->seuil_alerte,
                        'actif' => $article->actif,
                        'created_at' => $article->created_at->toDateTimeString(),
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        }

        $marques = Marque::orderBy('libelle')->get();
        $categories = CategorieEquipement::orderBy('libelle')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();

        return view('achat::articles.index', compact('marques', 'categories', 'fournisseurs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // Gérer l'upload de l'image si présente
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('articles', 'public');
                $data['image'] = $path;
            }

            $article = $this->articleService->creer($data);

            return response()->json([
                'success' => true,
                'message' => "L'article '{$article->designation}' a été créé avec succès.",
                'article' => $article,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.view');

        $article->load(['marque', 'categorie', 'fournisseurPrefere']);

        return response()->json([
            'success' => true,
            'article' => $article,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        try {
            $data = $request->validated();

            // Gérer l'upload de l'image
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('articles', 'public');
                $data['image'] = $path;
            }

            $this->articleService->modifier($article, $data);

            return response()->json([
                'success' => true,
                'message' => "L'article '{$article->designation}' a été mis à jour avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.delete');

        try {
            $this->articleService->supprimer($article);

            return response()->json([
                'success' => true,
                'message' => "L'article a été supprimé avec succès.",
            ]);
        } catch (Exception $e) {
            // L'exception peut être levée s'il est déjà référencé (il est alors seulement désactivé)
            return response()->json([
                'success' => true, // On renvoie true car la désactivation a fonctionné
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Toggle the active state of an article.
     */
    public function toggleActif(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.edit');

        $article->update(['actif' => ! $article->actif]);

        $etat = $article->actif ? 'activé' : 'désactivé';

        return response()->json([
            'success' => true,
            'message' => "L'article a été {$etat} avec succès.",
        ]);
    }

    /**
     * Duplicate an article.
     */
    public function dupliquer(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.create');

        try {
            $clone = $this->articleService->dupliquer($article);

            return response()->json([
                'success' => true,
                'message' => "L'article a été dupliqué sous le code '{$clone->code_article}'.",
                'article' => $clone,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
