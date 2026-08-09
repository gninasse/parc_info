<?php

namespace Modules\Catalogue\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\Activity;

/**
 * API inter-modules du catalogue (SFD §2.5).
 *
 * Contrats consommés par les autres modules (Stock, Achat, ParcInfo) : les
 * formats de réponse sont verrouillés par les tests snapshot
 * (ApiSnapshotTest) — toute rupture doit être délibérée.
 */
class ApiController extends Controller implements HasMiddleware
{
    private const LIMIT_DEFAUT = 50;

    private const LIMIT_MAX = 200;

    /** §2.4 — fenêtre du journal des prix : au-delà, l'information est morte. */
    private const FENETRE_JOURNAL_MOIS = 12;

    /** Entrées de prix rendues au plus. */
    private const LIMITE_JOURNAL = 50;

    /**
     * Traces lues avant filtrage. Le journal d'un article mêle tous ses
     * changements ; il en faut donc plus que 50 pour espérer y trouver 50
     * modifications de PRIX, sans pour autant tout charger.
     */
    private const LIMITE_JOURNAL_BRUT = 500;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:catalogue.api.view'),
        ];
    }

    public function articles(Request $request): JsonResponse
    {
        $query = Article::query()->with('categorie.parent');

        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));
            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(nom) LIKE ?', ["%{$q}%"]);
            });
        }

        if ($request->filled('nature')) {
            $query->where('nature', $request->input('nature'));
        }

        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', (int) $request->input('categorie_id'));
        }

        $query->where('est_actif', $request->boolean('est_actif', true));

        $total = $query->count();

        /*
         * Tri de pertinence exigé par le contrat §2.1 : quand un fournisseur
         * est connu (une commande en cours chez lui), ses articles remontent
         * en tête. C'est ce qui rend la modale M-01 utilisable — sinon
         * l'acheteur cherche ses références habituelles au milieu de tout le
         * catalogue. Exprimé en CASE WHEN plutôt qu'en fonction propriétaire :
         * portable SQLite/PostgreSQL.
         */
        if ($request->filled('fournisseur_prefere_id')) {
            $query->orderByRaw(
                'CASE WHEN fournisseur_principal_id = ? THEN 0 ELSE 1 END',
                [(int) $request->input('fournisseur_prefere_id')]
            );
        }

        $articles = $query
            ->orderBy('code')
            ->limit($this->limit($request))
            ->get()
            ->map(fn (Article $article) => $this->articleCompact($article));

        return response()->json(['data' => $articles, 'total' => $total]);
    }

    public function article($id): JsonResponse
    {
        $article = Article::with('categorie.parent')->find($id);

        if ($article === null) {
            return response()->json(['message' => 'Article introuvable.'], 404);
        }

        return response()->json(['data' => $this->articleCompact($article)]);
    }

    /**
     * Arbre à 2 niveaux des catégories actives.
     */
    public function categories(): JsonResponse
    {
        $arbre = Categorie::actif()
            ->whereNull('parent_id')
            ->with(['enfants' => fn ($q) => $q->where('est_actif', true)->orderBy('libelle')])
            ->orderBy('libelle')
            ->get()
            ->map(fn (Categorie $racine) => [
                'id' => $racine->id,
                'code' => $racine->code,
                'libelle' => $racine->libelle,
                'enfants' => $racine->enfants->map(fn (Categorie $enfant) => [
                    'id' => $enfant->id,
                    'code' => $enfant->code,
                    'libelle' => $enfant->libelle,
                ])->values(),
            ]);

        return response()->json(['data' => $arbre]);
    }

    public function fournisseurs(Request $request): JsonResponse
    {
        $query = Fournisseur::query()->where('est_actif', $request->boolean('est_actif', true));

        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));
            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(code) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(raison_sociale) LIKE ?', ["%{$q}%"]);
            });
        }

        $total = $query->count();

        $fournisseurs = $query
            ->orderBy('raison_sociale')
            ->limit($this->limit($request))
            ->get()
            ->map(fn (Fournisseur $fournisseur) => [
                'id' => $fournisseur->id,
                'code' => $fournisseur->code,
                'raison_sociale' => $fournisseur->raison_sociale,
                'telephone' => $fournisseur->telephone,
                'email' => $fournisseur->email,
            ]);

        return response()->json(['data' => $fournisseurs, 'total' => $total]);
    }

    /**
     * §2.4 — l'historique des modifications du PRIX INDICATIF d'un article.
     *
     * Il alimente deux choses côté Achat : la décomposition du prix affichée
     * à la saisie (PO-01) et le signal « référence modifiée il y a moins de
     * 30 jours » (A14). L'enjeu est simple à énoncer : un prix indicatif
     * relevé juste avant une commande fait disparaître l'écart de prix, et
     * cet endroit est le seul où cette manœuvre se voit.
     *
     * Deux précautions :
     *
     *   - on ne rend QUE les modifications de prix. Le journal d'un article
     *     contient tout le reste (changements de catégorie, de fournisseur,
     *     désactivations) : le servir en bloc exposerait, à qui a
     *     `catalogue.api.view`, un historique qu'il n'a pas demandé ;
     *   - la fenêtre est bornée à 12 mois et 50 entrées. Un article dont le
     *     prix bouge chaque semaine depuis cinq ans ne doit pas rendre une
     *     réponse de plusieurs mégaoctets à chaque saisie de ligne.
     */
    public function journalPrix(Request $request, $id): JsonResponse
    {
        $article = Article::query()->findOrFail($id);

        $lignes = Activity::query()
            ->where('subject_type', Article::class)
            ->where('subject_id', $article->id)
            ->where('event', 'updated')
            ->where('created_at', '>=', now()->subMonths(self::FENETRE_JOURNAL_MOIS))
            ->with('causer:id,name')
            ->orderByDesc('id')
            // On lit large puis on filtre en PHP : le prix est enfoui dans
            // une colonne JSON dont l'interrogation SQL diffère d'un SGBD à
            // l'autre (`->>` sur PostgreSQL, `json_extract` sur SQLite), et
            // la portabilité prime ici sur une optimisation invisible à
            // cette échelle.
            ->limit(self::LIMITE_JOURNAL_BRUT)
            ->get()
            ->map(function (Activity $activite) {
                $proprietes = $activite->properties ?? collect();
                $avant = $proprietes->get('old') ?? [];
                $apres = $proprietes->get('attributes') ?? [];

                // Seules les traces qui ont VRAIMENT touché le prix.
                if (! array_key_exists('prix_indicatif', $apres) || ! array_key_exists('prix_indicatif', $avant)) {
                    return null;
                }

                if ((string) $apres['prix_indicatif'] === (string) $avant['prix_indicatif']) {
                    return null;
                }

                return [
                    'date' => $activite->created_at?->toDateString(),
                    'ancien' => $this->montantOuNull($avant['prix_indicatif']),
                    'nouveau' => $this->montantOuNull($apres['prix_indicatif']),
                    'par' => $activite->causer?->name,
                ];
            })
            ->filter()
            ->take(self::LIMITE_JOURNAL)
            ->values();

        return response()->json([
            'article_id' => $article->id,
            'code' => $article->code,
            'prix_indicatif' => $article->prix_indicatif,
            'fenetre_mois' => self::FENETRE_JOURNAL_MOIS,
            'data' => $lignes,
        ]);
    }

    /** Même convention de montant que le reste de l'API : chaîne à 2 décimales. */
    private function montantOuNull(mixed $valeur): ?string
    {
        return $valeur === null ? null : number_format((float) $valeur, 2, '.', '');
    }

    /**
     * Fiche compacte d'article — décimaux en chaînes (casts decimal:2).
     */
    private function articleCompact(Article $article): array
    {
        return [
            'id' => $article->id,
            'code' => $article->code,
            'nom' => $article->nom,
            'nature' => $article->nature,
            'unite_stock' => $article->unite_stock,
            'seuil_defaut' => $article->seuil_defaut,
            'prix_indicatif' => $article->prix_indicatif,
            // Le contrat §2.1 garantit un taux TOUJOURS renseigné : Achat le
            // fige sur la ligne de commande à l'ajout et ne peut pas se
            // contenter d'un null. La valeur par défaut du schéma (18.00) est
            // reprise ici pour les fiches antérieures à la colonne.
            'taux_tva' => $article->taux_tva ?? '18.00',
            // P0-B (PRQ-03) : l'imputation, pour l'état par compte d'Achat.
            'compte_comptable' => $article->compte_comptable,
            'categorie' => $article->categorie_chemin,
            'fournisseur_principal_id' => $article->fournisseur_principal_id,
            'categorie_equipement_id' => $article->categorie_equipement_id,
            'logiciel_id' => $article->logiciel_id,
        ];
    }

    private function limit(Request $request): int
    {
        return min(max((int) $request->input('limit', self::LIMIT_DEFAUT), 1), self::LIMIT_MAX);
    }
}
