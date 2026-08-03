<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Http\Requests\StoreEntreeRequest;
use Modules\Stock\Http\Requests\UpdateEntreeRequest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;

class EntreeController extends Controller implements HasMiddleware
{
    /** I16 — texte du cadenas UX (wizard §3.3), renvoyé avec chaque 409 de verrouillage. */
    public const MESSAGE_VERROUILLAGE = 'Lignes et quantités verrouillées pendant le référencement — « Revenir au brouillon » pour les modifier.';

    public static function middleware(): array
    {
        // SFD §5 : store couvre création, référencement, validation, PDF
        return [
            new Middleware('permission:stock.entrees.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:stock.entrees.store', only: [
                'create', 'store', 'referencement', 'wizard', 'wizardUpdate', 'wizardImport',
                'retourBrouillon', 'valider', 'pdf',
            ]),
            new Middleware('permission:stock.entrees.update', only: ['edit', 'update']),
            new Middleware('permission:stock.entrees.destroy', only: ['destroy']),
            new Middleware('permission:stock.entrees.store|stock.entrees.update', only: ['getEquipementsDisponibles']),
        ];
    }

    public function index()
    {
        $magasins = Magasin::query()->orderBy('libelle')->get(['id', 'libelle']);
        $fournisseurs = Fournisseur::query()->orderBy('raison_sociale')->get(['id', 'raison_sociale']);

        return view('stock::entrees.index', compact('magasins', 'fournisseurs'));
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Entree::query()
            ->with(['magasin:id,code,libelle', 'fournisseur:id,raison_sociale', 'createur:id,name'])
            ->withCount('lignes as nb_lignes');

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('magasin_id')) {
            $query->duMagasin((int) $request->input('magasin_id'));
        }

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', (int) $request->input('fournisseur_id'));
        }

        if ($request->filled('du')) {
            $query->where('date_document', '>=', $request->input('du'));
        }

        if ($request->filled('au')) {
            $query->where('date_document', '<=', $request->input('au'));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(numero) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(reference_externe) LIKE ?', ["%{$search}%"]);
            });
        }

        $sort = $request->input('sort', 'id');
        $allowed = ['id', 'numero', 'date_document', 'statut', 'nb_lignes'];
        $query->orderBy(in_array($sort, $allowed, true) ? $sort : 'id', strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc');

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(function (Entree $entree) {
                [$saisis, $attendus] = $this->progressionTampon($entree);

                return [
                    'id' => $entree->id,
                    'numero_affiche' => $entree->numero_affiche,
                    'date_document' => $entree->date_document?->format('d/m/Y'),
                    'magasin' => $entree->magasin?->libelle,
                    'nature' => $entree->nature,
                    'fournisseur' => $entree->fournisseur?->raison_sociale,
                    'reference_externe' => $entree->reference_externe,
                    'nb_lignes' => $entree->nb_lignes,
                    'statut' => $entree->statut,
                    'progression' => $entree->statut === Entree::STATUT_REFERENCEMENT && $attendus > 0
                        ? "{$saisis}/{$attendus} réf."
                        : null,
                    'nb_tampons_saisis' => $saisis,
                    'can_edit' => $entree->canEdit(),
                    'can_delete' => $entree->canDelete(),
                    'cree_par' => $entree->createur?->name,
                ];
            });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function create()
    {
        return view('stock::entrees.form', [
            'entree' => null,
            'magasins' => Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'libelle']),
            'fournisseurs' => Fournisseur::query()->where('est_actif', true)->orderBy('raison_sociale')->get(['id', 'raison_sociale']),
        ]);
    }

    public function store(StoreEntreeRequest $request): JsonResponse
    {
        try {
            $entree = DB::transaction(function () use ($request) {
                $entree = Entree::create(array_merge(
                    collect($request->validated())->except('lignes')->all(),
                    ['created_by' => auth()->id()]
                ));

                $this->synchroniserLignes($entree, $request->validated()['lignes'] ?? []);

                return $entree;
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$entree->id} enregistré.",
                'data' => ['id' => $entree->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du brouillon d\'entrée', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /** Fiche (VALIDÉ) ou renvoi vers l'écran correspondant au statut. */
    public function show(Request $request, $id)
    {
        $entree = Entree::query()
            ->with(['magasin', 'fournisseur', 'lignes.article', 'lignes.equipement', 'lignes.tampons', 'createur:id,name', 'valideur:id,name'])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $entree]);
        }

        return match ($entree->statut) {
            Entree::STATUT_BROUILLON => redirect()->route('stock.entrees.edit', $entree->id),
            Entree::STATUT_REFERENCEMENT => redirect()->route('stock.entrees.wizard', $entree->id),
            default => view('stock::entrees.show', ['entree' => $entree]),
        };
    }

    public function edit($id)
    {
        $entree = Entree::query()
            ->with(['lignes.article', 'lignes.equipement'])
            ->findOrFail($id);

        if (! $entree->canEdit()) {
            return $entree->statut === Entree::STATUT_REFERENCEMENT
                ? redirect()->route('stock.entrees.wizard', $entree->id)
                : redirect()->route('stock.entrees.show', $entree->id);
        }

        return view('stock::entrees.form', [
            'entree' => $entree,
            'magasins' => Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'libelle']),
            'fournisseurs' => Fournisseur::query()->where('est_actif', true)->orderBy('raison_sociale')->get(['id', 'raison_sociale']),
        ]);
    }

    public function update(UpdateEntreeRequest $request, $id): JsonResponse
    {
        $entree = Entree::query()->findOrFail($id);

        // I6/I16 : modifications refusées hors brouillon (409)
        if (! $entree->canEdit()) {
            return $this->refusVerrouillage($entree);
        }

        try {
            DB::transaction(function () use ($request, $entree) {
                $entree->update(collect($request->validated())->except('lignes')->all());
                $this->synchroniserLignes($entree, $request->validated()['lignes'] ?? []);
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$entree->id} enregistré.",
                'data' => ['id' => $entree->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du brouillon d\'entrée', ['exception' => $e, 'entree_id' => $entree->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $entree = Entree::query()->withCount('lignes as nb_lignes')->findOrFail($id);

        if (! $entree->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.',
            ], 409);
        }

        try {
            $entree->delete(); // cascade lignes + tampon

            return response()->json([
                'success' => true,
                'message' => "Le brouillon #{$entree->id} a été supprimé.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la suppression du brouillon d\'entrée', ['exception' => $e, 'entree_id' => $entree->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /**
     * Sélecteur d'unités (UX §0.5) : équipements « en stock » non rattachés
     * à un magasin, recherche unique série / inventaire / modèle.
     */
    public function getEquipementsDisponibles(Request $request): JsonResponse
    {
        // Les retours ramènent aussi des unités « en service » (?nature=retour)
        $query = Equipement::query()
            ->where(function ($q) use ($request) {
                $q->where('statut', 'LIKE', 'en_stock%');

                if ($request->input('nature') === 'retour') {
                    $q->orWhere('statut', 'en_service');
                }
            })
            ->whereNotIn('id', EquipementMagasin::query()->select('equipement_id'));

        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));
            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(numero_serie) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(code_inventaire) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(modele) LIKE ?', ["%{$q}%"]);
            });
        }

        $total = $query->count();
        $rows = $query
            ->orderBy('code_inventaire')
            ->limit(min((int) $request->input('limit', 30), 100))
            ->get(['id', 'code_inventaire', 'numero_serie', 'modele', 'etat', 'statut'])
            ->map(fn (Equipement $equipement) => [
                'id' => $equipement->id,
                'code_inventaire' => $equipement->code_inventaire,
                'numero_serie' => $equipement->numero_serie,
                'modele' => $equipement->modele,
                'etat' => $equipement->etat,
                'statut' => $equipement->statut,
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    // ── Actions implémentées aux commits B–E ───────────────────────────────

    /** BROUILLON → RÉFÉRENCEMENT : le tampon naît, les quantités se verrouillent (I16). */
    public function referencement($id): JsonResponse
    {
        $entree = Entree::query()->findOrFail($id);

        try {
            $rangees = app(\Modules\Stock\Services\TamponService::class)->passerEnReferencement($entree);

            return response()->json([
                'success' => true,
                'message' => "{$rangees} numéro(s) de série à saisir.",
                'data' => ['wizard_url' => route('stock.entrees.wizard', $entree->id)],
            ]);
        } catch (TransitionInterditeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    /** Écran de référencement plein écran (UX §3.3). */
    public function wizard($id)
    {
        $entree = Entree::query()->with(['magasin:id,code,libelle'])->findOrFail($id);

        if ($entree->statut !== Entree::STATUT_REFERENCEMENT) {
            return match ($entree->statut) {
                Entree::STATUT_BROUILLON => redirect()->route('stock.entrees.edit', $entree->id),
                default => redirect()->route('stock.entrees.show', $entree->id),
            };
        }

        $tamponService = app(\Modules\Stock\Services\TamponService::class);
        [$saisis, $total] = $tamponService->progression($entree);

        return view('stock::entrees.wizard', [
            'entree' => $entree,
            'lignesModeles' => $tamponService->lignesModeles($entree),
            'saisis' => $saisis,
            'total' => $total,
        ]);
    }

    /**
     * Autosave du wizard (dérogation S5) : un PUT unitaire par blur/scan,
     * unicité contrôlée serveur à CHAQUE écriture.
     */
    public function wizardUpdate(Request $request, $id): JsonResponse
    {
        $entree = Entree::query()->findOrFail($id);

        if ($entree->statut !== Entree::STATUT_REFERENCEMENT) {
            return $this->refusVerrouillage($entree);
        }

        $valide = $request->validate([
            'tampon_id' => ['required', 'integer'],
            'numero_serie' => ['nullable', 'string', 'max:255'],
        ]);

        $tampon = \Modules\Stock\Models\TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->findOrFail((int) $valide['tampon_id']);

        $tamponService = app(\Modules\Stock\Services\TamponService::class);

        try {
            $tamponService->saisirNumero($entree, $tampon, $valide['numero_serie'] ?? null);
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        [$saisis, $total] = $tamponService->progression($entree);
        $ligne = $tampon->ligneEntree;

        return response()->json([
            'success' => true,
            'statut_ligne' => [
                'tampon_id' => $tampon->id,
                'numero_serie' => $tampon->fresh()->numero_serie,
                'saisis' => $saisis,
                'total' => $total,
                'ligne_saisis' => $ligne->tampons()->whereNotNull('numero_serie')->count(),
                'ligne_total' => (int) $ligne->quantite,
            ],
        ]);
    }

    /** Import CSV / collage (MD-IMPORT) : mode analyser (rapport) ou appliquer. */
    public function wizardImport(Request $request, $id): JsonResponse
    {
        $entree = Entree::query()->findOrFail($id);

        if ($entree->statut !== Entree::STATUT_REFERENCEMENT) {
            return $this->refusVerrouillage($entree);
        }

        $valide = $request->validate([
            'mode' => ['required', 'in:analyser,appliquer'],
            'contenu' => ['nullable', 'string', 'required_without:fichier'],
            'fichier' => ['nullable', 'file', 'mimes:csv,txt', 'max:1024', 'required_without:contenu'],
        ], [
            'contenu.required_without' => 'Collez des numéros ou joignez un fichier CSV.',
        ]);

        $tamponService = app(\Modules\Stock\Services\TamponService::class);

        $contenu = $request->hasFile('fichier')
            ? (string) file_get_contents($request->file('fichier')->getRealPath())
            : (string) ($valide['contenu'] ?? '');

        $numeros = $tamponService->parserContenu($contenu);

        $rapport = $valide['mode'] === 'appliquer'
            ? $tamponService->appliquerImport($entree, $numeros)
            : $tamponService->analyserImport($entree, $numeros);

        [$saisis, $total] = $tamponService->progression($entree);

        return response()->json([
            'success' => true,
            'mode' => $valide['mode'],
            'rapport' => $rapport,
            'progression' => ['saisis' => $saisis, 'total' => $total],
        ]);
    }

    /** RÉFÉRENCEMENT → BROUILLON : purge du tampon, action journalisée (I16). */
    public function retourBrouillon($id): JsonResponse
    {
        $entree = Entree::query()->findOrFail($id);

        try {
            $perdues = app(\Modules\Stock\Services\TamponService::class)->retourBrouillon($entree);

            return response()->json([
                'success' => true,
                'message' => $perdues > 0
                    ? "Retour au brouillon : {$perdues} référence(s) saisie(s) perdue(s). Les articles et quantités du bon sont conservés."
                    : 'Retour au brouillon. Les articles et quantités du bon sont conservés.',
                'data' => ['edit_url' => route('stock.entrees.edit', $entree->id)],
            ]);
        } catch (TransitionInterditeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    /**
     * Validation (commit E) : ?recap=1 renvoie le récapitulatif chiffré de
     * SW-VALIDER-ENT sans écrire ; sinon exécute (idempotent par jeton — I9).
     */
    public function valider(Request $request, $id): JsonResponse
    {
        $entree = Entree::query()->with(['magasin', 'fournisseur'])->findOrFail($id);
        $service = app(\Modules\Stock\Services\ValiderEntreeService::class);

        if ($request->boolean('recap')) {
            return response()->json(['success' => true, 'recap' => $service->recapDocument($entree)]);
        }

        $valide = $request->validate(['jeton' => ['required', 'string', 'max:64']]);

        try {
            $recap = $service->valider($entree, $valide['jeton'], auth()->id());
        } catch (TransitionInterditeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => "Bon d'entrée {$recap['numero']} validé.",
            'recap' => $recap,
            'data' => ['show_url' => route('stock.entrees.show', $entree->id)],
        ]);
    }

    public function pdf($id)
    {
        abort(501, 'PDF : commit F.');
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** 409 uniforme de verrouillage (I16) avec le texte du cadenas UX. */
    protected function refusVerrouillage(Entree $entree): JsonResponse
    {
        $message = $entree->verrouille()
            ? self::MESSAGE_VERROUILLAGE
            : 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.';

        return response()->json(['success' => false, 'message' => $message], 409);
    }

    /** Remplacement complet des lignes du brouillon (enregistrement au clic). */
    private function synchroniserLignes(Entree $entree, array $lignes): void
    {
        $entree->lignes()->delete();

        foreach ($lignes as $ligne) {
            LigneEntree::create([
                'entree_id' => $entree->id,
                'article_id' => $ligne['article_id'] ?? null,
                'equipement_id' => $ligne['equipement_id'] ?? null,
                'quantite' => $ligne['quantite'],
                'cout_unitaire' => $ligne['cout_unitaire'] ?? null,
            ]);
        }
    }

    /** Progression du référencement : [saisis, attendus] sur les lignes modèle × N. */
    protected function progressionTampon(Entree $entree): array
    {
        $lignesModeles = $entree->lignes()
            ->whereNotNull('article_id')
            ->whereHas('article', fn ($q) => $q->where('nature', Article::NATURE_EQUIPEMENT))
            ->withCount([
                'tampons as tampons_saisis' => fn ($q) => $q->whereNotNull('numero_serie'),
            ])
            ->get();

        return [
            (int) $lignesModeles->sum('tampons_saisis'),
            (int) $lignesModeles->sum('quantite'),
        ];
    }
}
