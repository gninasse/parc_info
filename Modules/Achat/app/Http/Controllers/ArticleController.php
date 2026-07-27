<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Http\Requests\StoreArticleRequest;
use Modules\Achat\Http\Requests\UpdateArticleRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Services\ArticleService;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;

class ArticleController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    public function __construct(protected ArticleService $articleService) {}

    /** Écran du catalogue (E-02). */
    public function index(): View
    {
        $this->authorize('achat.articles.view');

        return view('achat::articles.index', [
            'marques' => Marque::orderBy('libelle')->get(),
            'categories' => CategorieEquipement::orderBy('libelle')->get(),
            'fournisseurs' => Fournisseur::where('est_actif', true)->orderBy('nom')->get(),
            'typesArticles' => config('achat.types_articles'),
        ]);
    }

    /**
     * Alimentation de Bootstrap Table.
     *
     * PATTERNS §4 — Route dédiée, distincte de index(). La version précédente
     * détectait $request->ajax() dans index(), ce que la convention interdit.
     */
    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.articles.view');

        $query = $this->articleService->lister([
            'type_article' => $request->input('type_article'),
            'marque_id' => $request->input('marque_id'),
            'categorie_equipement_id' => $request->input('categorie_equipement_id'),
            'actif' => $request->input('actif'),
            'recherche' => $request->input('search'),
        ]);

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'code_article' => $article->code_article,
                'designation' => $article->designation,
                'type_article' => $article->type_article,
                'type_label' => $article->type_label,
                'reference_constructeur' => $article->reference_constructeur ?: '-',
                'marque' => $article->marque?->libelle ?? '-',
                'categorie' => $article->categorie?->libelle ?? '-',
                'prix_indicatif' => (float) $article->prix_indicatif,
                'taux_tva' => (float) $article->taux_tva,
                'actif' => $article->actif,
                'created_at' => $article->created_at?->toDateTimeString(),
            ]);

        return $this->table($total, $rows);
    }

    /** Pré-remplissage du formulaire de modification (PATTERNS §5). */
    public function show(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.view');

        $article->load(['marque', 'categorie', 'fournisseurPrefere']);

        return $this->donnees($article);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $donnees = $request->validated();

            if ($request->hasFile('image')) {
                $donnees['image'] = $request->file('image')->store('articles', 'public');
            }

            $article = $this->articleService->creer($donnees);

            return $this->succes(
                "L'article « {$article->designation} » a été créé sous le code {$article->code_article}.",
                ['data' => $article]
            );
        });
    }

    public function update(UpdateArticleRequest $request, Article $article): JsonResponse
    {
        return $this->executer(function () use ($request, $article) {
            $donnees = $request->validated();

            if ($request->hasFile('image')) {
                if ($article->image) {
                    Storage::disk('public')->delete($article->image);
                }

                $donnees['image'] = $request->file('image')->store('articles', 'public');
            }

            $this->articleService->modifier($article, $donnees);

            return $this->succes("L'article « {$article->designation} » a été mis à jour.");
        });
    }

    /**
     * RG-ART-10 — Suppression, ou désactivation si l'article est engagé.
     *
     * Correction AN-11 : la désactivation est signalée comme telle et non
     * comme une suppression réussie.
     */
    public function destroy(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.delete');

        return $this->executer(function () use ($article) {
            $supprime = $this->articleService->supprimer($article);

            return $this->succes(
                $supprime
                    ? "L'article « {$article->designation} » a été supprimé."
                    : "L'article « {$article->designation} » est référencé dans des bons de commande : "
                        .'il a été désactivé et ne sera plus proposé à la commande.',
                ['supprime' => $supprime]
            );
        });
    }

    public function toggleActif(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.edit');

        return $this->executer(function () use ($article) {
            $article->update(['actif' => ! $article->actif]);

            return $this->succes(
                "L'article « {$article->designation} » a été "
                .($article->actif ? 'activé' : 'désactivé').'.'
            );
        });
    }

    public function dupliquer(Article $article): JsonResponse
    {
        $this->authorize('achat.articles.create');

        return $this->executer(function () use ($article) {
            $copie = $this->articleService->dupliquer($article);

            return $this->succes(
                "L'article a été dupliqué sous le code {$copie->code_article}.",
                ['data' => $copie]
            );
        });
    }
}
