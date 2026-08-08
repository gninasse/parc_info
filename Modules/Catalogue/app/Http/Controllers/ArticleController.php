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
use Illuminate\Support\Facades\Schema;
use Modules\Catalogue\Http\Requests\StoreArticleRequest;
use Modules\Catalogue\Http\Requests\UpdateArticleRequest;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Marque;

class ArticleController extends Controller implements HasMiddleware
{
    /**
     * Références aval bloquant la suppression (C8). Tables consultées via
     * Schema::hasTable : l'écran reste fonctionnel avant l'installation des
     * modules Stock/Achat (leurs tables n'existent pas encore).
     */
    private const REFERENCES_AVAL = [
        'stock_mouvements' => 'mouvement(s) de stock',
        'achat_lignes_commande' => 'ligne(s) de commande',
        'parc_info_affectations_consommables' => 'affectation(s)',
    ];

    public static function middleware(): array
    {
        return [
            new Middleware('permission:catalogue.articles.index', only: [
                'index', 'getData', 'show',
                'getCategoriesCascade', 'getMarques', 'getCategoriesEquipements', 'getLogiciels',
            ]),
            new Middleware('permission:catalogue.articles.store', only: ['store']),
            new Middleware('permission:catalogue.articles.update', only: ['update']),
            new Middleware('permission:catalogue.articles.destroy', only: ['destroy']),
            new Middleware('permission:catalogue.articles.toggle-status', only: ['toggleStatus']),
        ];
    }

    public function index()
    {
        $kpis = array_replace(
            array_fill_keys(Article::NATURES, 0),
            Article::query()->selectRaw('nature, COUNT(*) as nb')->groupBy('nature')->pluck('nb', 'nature')->all()
        );

        $fournisseurs = Fournisseur::actif()->orderBy('raison_sociale')->get(['id', 'raison_sociale']);

        return view('catalogue::articles.index', compact('kpis', 'fournisseurs'));
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Article::query()->with(['categorie.parent', 'marque']);

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nom) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(modele) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(reference_constructeur) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('nature')) {
            $query->where('nature', $request->input('nature'));
        }

        if ($request->filled('categorie_id')) {
            // Une catégorie de niveau 1 inclut ses sous-catégories
            $categorie = Categorie::find((int) $request->input('categorie_id'));
            $ids = [(int) $request->input('categorie_id')];
            if ($categorie && $categorie->parent_id === null) {
                $ids = array_merge($ids, $categorie->enfants()->pluck('id')->all());
            }
            $query->whereIn('categorie_id', $ids);
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_principal_id', (int) $request->input('fournisseur_id'));
        }

        if ($request->filled('est_actif')) {
            $query->where('est_actif', (bool) $request->input('est_actif'));
        }

        $sort = $request->input('sort', 'code');
        $allowed = ['id', 'code', 'nom', 'nature', 'modele', 'unite_stock', 'prix_indicatif', 'taux_tva', 'seuil_defaut', 'est_actif', 'created_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'code';
        }
        $order = strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sort, $order);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Article $a) => [
                'id' => $a->id,
                'code' => $a->code,
                'nom' => $a->nom,
                'nature' => $a->nature,
                'nature_label' => $a->nature_label,
                'categorie_chemin' => $a->categorie_chemin,
                'marque_libelle' => $a->marque?->libelle,
                'modele' => $a->modele,
                'unite_stock' => $a->unite_stock,
                'prix_indicatif' => $a->prix_indicatif,
                'taux_tva' => $a->taux_tva,
                'seuil_defaut' => $a->seuil_defaut,
                'est_actif' => $a->est_actif,
                'created_at' => $a->created_at?->format('d/m/Y'),
            ]);

        // Compteurs globaux par nature (cartes KPI rafraîchies par le front)
        $kpis = array_replace(
            array_fill_keys(Article::NATURES, 0),
            Article::query()->selectRaw('nature, COUNT(*) as nb')->groupBy('nature')->pluck('nb', 'nature')->all()
        );

        return response()->json(['total' => $total, 'rows' => $rows, 'kpis' => $kpis]);
    }

    /**
     * Cascade catégorie → sous-catégories pour les filtres et la modale.
     */
    public function getCategoriesCascade(): JsonResponse
    {
        $data = Categorie::actif()
            ->whereNull('parent_id')
            ->with(['enfants' => fn ($q) => $q->where('est_actif', true)->orderBy('libelle')])
            ->orderBy('libelle')
            ->get()
            ->map(fn (Categorie $racine) => [
                'id' => $racine->id,
                'libelle' => $racine->libelle,
                'enfants' => $racine->enfants->map(fn (Categorie $e) => ['id' => $e->id, 'libelle' => $e->libelle])->values(),
            ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function getMarques(): JsonResponse
    {
        // parc_info_marques n'a pas de drapeau est_actif : liste complète triée
        return response()->json([
            'success' => true,
            'data' => Marque::orderBy('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function getCategoriesEquipements(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => CategorieEquipement::orderBy('libelle')->get(['id', 'code', 'libelle']),
        ]);
    }

    /**
     * Select2 riche : nom + « éditeur — type de licence » en sous-titre.
     */
    public function getLogiciels(): JsonResponse
    {
        $data = Logiciel::where('est_actif', true)
            ->with(['editeur', 'typeLicence'])
            ->orderBy('nom')
            ->get()
            ->map(fn (Logiciel $logiciel) => [
                'id' => $logiciel->id,
                'text' => $logiciel->nom,
                'editeur' => $logiciel->editeur?->nom,
                'type_licence' => $logiciel->typeLicence?->libelle,
            ]);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id): JsonResponse
    {
        $article = Article::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $article->only([
                'id', 'code', 'nom', 'nature', 'categorie_id', 'marque_id', 'modele', 'reference_constructeur',
                'unite_stock', 'prix_indicatif', 'taux_tva', 'seuil_defaut', 'fournisseur_principal_id',
                'categorie_equipement_id', 'logiciel_id', 'compatibilites', 'est_actif', 'notes',
            ]),
        ]);
    }

    public function store(StoreArticleRequest $request): JsonResponse
    {
        try {
            $article = Article::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => "L'article « {$article->nom} » a été créé avec succès ({$article->code}).",
                'data' => ['id' => $article->id, 'code' => $article->code],
            ]);
        } catch (Exception $e) {
            Log::error("Erreur à la création de l'article", ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function update(UpdateArticleRequest $request, $id): JsonResponse
    {
        $article = Article::findOrFail($id);

        try {
            // nature et code sont absents des règles de validation : même
            // fournis par un POST forgé, ils ne sont jamais mis à jour (C6).
            $article->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "L'article « {$article->nom} » a été modifié avec succès.",
            ]);
        } catch (Exception $e) {
            Log::error("Erreur à la modification de l'article", ['exception' => $e, 'article_id' => $article->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $article = Article::findOrFail($id);

        $references = [];
        foreach (self::REFERENCES_AVAL as $table => $libelle) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $nb = DB::table($table)->where('article_id', $article->id)->count();
            if ($nb > 0) {
                $references[] = "{$nb} {$libelle}";
            }
        }

        if ($references !== []) {
            return response()->json([
                'success' => false,
                'message' => "L'article {$article->code} — {$article->nom} est référencé par : "
                    .implode(', ', $references).'. Vous pouvez le désactiver.',
            ], 422);
        }

        try {
            $article->delete();

            return response()->json([
                'success' => true,
                'message' => "L'article « {$article->nom} » a été supprimé.",
            ]);
        } catch (Exception $e) {
            Log::error("Erreur à la suppression de l'article", ['exception' => $e, 'article_id' => $article->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        $article = Article::findOrFail($id);

        try {
            $article->update(['est_actif' => ! $article->est_actif]);

            $etat = $article->est_actif ? 'activé' : 'désactivé';

            return response()->json([
                'success' => true,
                'message' => "L'article « {$article->nom} » a été {$etat}.",
                'data' => ['est_actif' => $article->est_actif],
            ]);
        } catch (Exception $e) {
            Log::error("Erreur au changement de statut de l'article", ['exception' => $e, 'article_id' => $article->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }
}
