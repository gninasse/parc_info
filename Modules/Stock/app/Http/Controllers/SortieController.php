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
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Unite;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Http\Requests\StoreSortieRequest;
use Modules\Stock\Http\Requests\UpdateSortieRequest;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\LigneTransfert;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Services\PointageService;
use Modules\Stock\Services\ValiderSortieService;

class SortieController extends Controller implements HasMiddleware
{
    /** I16 — texte du cadenas (pointage), renvoyé avec chaque 409 de verrouillage. */
    public const MESSAGE_VERROUILLAGE = 'Lignes et quantités verrouillées pendant le pointage — « Revenir au brouillon » pour les modifier.';

    public static function middleware(): array
    {
        // SFD §5 : store couvre création, pointage, validation, PDF
        return [
            new Middleware('permission:stock.sorties.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:stock.sorties.store', only: [
                'create', 'store', 'pointage', 'pointageShow', 'pointageUpdate', 'scanExpress',
                'retourBrouillon', 'valider', 'pdf',
            ]),
            new Middleware('permission:stock.sorties.update', only: ['edit', 'update']),
            new Middleware('permission:stock.sorties.destroy', only: ['destroy']),
            new Middleware('permission:stock.sorties.store|stock.sorties.update', only: [
                'getEquipementsDuMagasin', 'getDisponibilite',
            ]),
            new Middleware(
                'permission:stock.sorties.store|stock.sorties.update|stock.entrees.store|stock.entrees.update',
                only: ['getBeneficiaires']
            ),
        ];
    }

    public function index()
    {
        return view('stock::sorties.index', [
            'magasins' => Magasin::query()->orderBy('libelle')->get(['id', 'libelle']),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Sortie::query()
            ->with(['magasin:id,code,libelle', 'createur:id,name'])
            ->withCount('lignes as nb_lignes');

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('magasin_id')) {
            $query->duMagasin((int) $request->input('magasin_id'));
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
                    ->orWhereRaw('LOWER(beneficiaire_libelle) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(remis_a_nom) LIKE ?', ["%{$search}%"]);
            });
        }

        $sort = $request->input('sort', 'id');
        $allowed = ['id', 'numero', 'date_document', 'statut', 'nb_lignes'];
        $query->orderBy(in_array($sort, $allowed, true) ? $sort : 'id', strtolower($request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc');

        $pointageService = app(PointageService::class);

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(function (Sortie $sortie) use ($pointageService) {
                [$pointees, $attendues] = $sortie->statut === Sortie::STATUT_POINTAGE
                    ? $pointageService->progression($sortie)
                    : [0, 0];

                return [
                    'id' => $sortie->id,
                    'numero_affiche' => $sortie->numero_affiche,
                    'date_document' => $sortie->date_document?->format('d/m/Y'),
                    'magasin' => $sortie->magasin?->libelle,
                    'beneficiaire_type' => $sortie->beneficiaire_type,
                    'beneficiaire' => $sortie->beneficiaire_libelle,
                    'remis_a' => $sortie->remis_a_nom,
                    'nb_lignes' => $sortie->nb_lignes,
                    'statut' => $sortie->statut,
                    'progression' => $attendues > 0 ? "{$pointees}/{$attendues} unités" : null,
                    'nb_pointees' => $pointees,
                    'can_edit' => $sortie->canEdit(),
                    'can_delete' => $sortie->canDelete(),
                    'cree_par' => $sortie->createur?->name,
                ];
            });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function create()
    {
        return $this->vueFormulaire(null);
    }

    public function store(StoreSortieRequest $request): JsonResponse
    {
        try {
            $sortie = DB::transaction(function () use ($request) {
                $sortie = Sortie::create(array_merge(
                    collect($request->validated())->except('lignes')->all(),
                    ['created_by' => auth()->id()]
                ));

                $this->synchroniserLignes($sortie, $request->validated()['lignes'] ?? []);

                return $sortie;
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$sortie->id} enregistré.",
                'data' => ['id' => $sortie->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du brouillon de sortie', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $sortie = Sortie::query()
            ->with(['magasin', 'lignes.article', 'lignes.emplacementLocal', 'remisAEmploye', 'createur:id,name', 'valideur:id,name'])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $sortie]);
        }

        return match ($sortie->statut) {
            Sortie::STATUT_BROUILLON => redirect()->route('stock.sorties.edit', $sortie->id),
            Sortie::STATUT_POINTAGE => redirect()->route('stock.sorties.pointage.show', $sortie->id),
            default => view('stock::sorties.show', [
                'sortie' => $sortie,
                'unites' => $this->unitesDuBon($sortie),
            ]),
        };
    }

    public function edit($id)
    {
        $sortie = Sortie::query()->with(['lignes.article'])->findOrFail($id);

        if (! $sortie->canEdit()) {
            return $sortie->statut === Sortie::STATUT_POINTAGE
                ? redirect()->route('stock.sorties.pointage.show', $sortie->id)
                : redirect()->route('stock.sorties.show', $sortie->id);
        }

        return $this->vueFormulaire($sortie);
    }

    public function update(UpdateSortieRequest $request, $id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        if (! $sortie->canEdit()) {
            return $this->refusVerrouillage($sortie);
        }

        try {
            DB::transaction(function () use ($request, $sortie) {
                $sortie->update(collect($request->validated())->except('lignes')->all());
                $this->synchroniserLignes($sortie, $request->validated()['lignes'] ?? []);
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$sortie->id} enregistré.",
                'data' => ['id' => $sortie->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du brouillon de sortie', ['exception' => $e, 'sortie_id' => $sortie->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        if (! $sortie->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.',
            ], 409);
        }

        $sortie->delete();

        return response()->json(['success' => true, 'message' => "Le brouillon #{$sortie->id} a été supprimé."]);
    }

    // ── Pointage (PointageService — partagé avec les transferts) ──────────

    public function pointage($id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        try {
            app(PointageService::class)->passerEnPointage($sortie);

            return response()->json([
                'success' => true,
                'data' => ['pointage_url' => route('stock.sorties.pointage.show', $sortie->id)],
            ]);
        } catch (TransitionInterditeException|StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    public function pointageShow($id)
    {
        $sortie = Sortie::query()->with('magasin:id,code,libelle')->findOrFail($id);

        if ($sortie->statut !== Sortie::STATUT_POINTAGE) {
            return match ($sortie->statut) {
                Sortie::STATUT_BROUILLON => redirect()->route('stock.sorties.edit', $sortie->id),
                default => redirect()->route('stock.sorties.show', $sortie->id),
            };
        }

        $pointageService = app(PointageService::class);
        [$pointees, $attendues] = $pointageService->progression($sortie);

        return view('stock::shared.pointage_ecran', [
            'titre' => 'Pointage — '.$sortie->numero_affiche,
            'filAriane' => 'Sorties',
            'typeDocument' => 'sortie',
            'document' => $sortie,
            'lignesModeles' => $pointageService->lignesModeles($sortie),
            'pointees' => $pointees,
            'attendues' => $attendues,
            'routes' => [
                'update' => route('stock.sorties.pointage.update', $sortie->id),
                'retour' => route('stock.sorties.retour-brouillon', $sortie->id),
                'valider' => route('stock.sorties.valider', $sortie->id),
                'unites' => route('stock.equipements.du-magasin'),
                'index' => route('stock.sorties.index'),
            ],
            'magasinSourceId' => $sortie->magasinSourceId(),
            'verrouillageMessage' => self::MESSAGE_VERROUILLAGE,
        ]);
    }

    /** {action: pointer|depointer, ligne_id|tampon_id, equipement_id} */
    public function pointageUpdate(Request $request, $id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        return $this->traiterPointageUpdate($sortie, $request, app(PointageService::class));
    }

    public function scanExpress(Request $request, $id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        return $this->traiterScanExpress($sortie, $request, app(PointageService::class));
    }

    public function retourBrouillon($id): JsonResponse
    {
        $sortie = Sortie::query()->findOrFail($id);

        try {
            $depointees = app(PointageService::class)->retourBrouillon($sortie);

            return response()->json([
                'success' => true,
                'message' => $depointees > 0
                    ? "Retour au brouillon : {$depointees} unité(s) dépointée(s). Les articles et quantités du bon sont conservés."
                    : 'Retour au brouillon. Les articles et quantités du bon sont conservés.',
                'data' => ['edit_url' => route('stock.sorties.edit', $sortie->id)],
            ]);
        } catch (TransitionInterditeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    public function valider(Request $request, $id): JsonResponse
    {
        $sortie = Sortie::query()->with(['magasin'])->findOrFail($id);
        $service = app(ValiderSortieService::class);

        if ($request->boolean('recap')) {
            return response()->json(['success' => true, 'recap' => $service->recapDocument($sortie)]);
        }

        $valide = $request->validate(['jeton' => ['required', 'string', 'max:64']]);

        try {
            $recap = $service->valider($sortie, $valide['jeton'], auth()->id());
        } catch (TransitionInterditeException|StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => "Bon de sortie {$recap['numero']} validé.",
            'recap' => $recap,
            'data' => ['show_url' => route('stock.sorties.show', $sortie->id)],
        ]);
    }

    public function pdf($id)
    {
        $sortie = Sortie::query()
            ->with(['magasin', 'lignes.article', 'remisAEmploye', 'createur:id,name', 'valideur:id,name'])
            ->findOrFail($id);

        if (! $sortie->estValide()) {
            return response()->json(['success' => false, 'message' => 'Le bon PDF n\'existe qu\'après validation.'], 409);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('stock::pdf.sortie', [
            'sortie' => $sortie,
            'unites' => $this->unitesDuBon($sortie),
        ])->setPaper('a4')->stream("bon-sortie-{$sortie->numero}.pdf");
    }

    // ── Cascades ───────────────────────────────────────────────────────────

    /** Unités « en stock » DU magasin (pointage — D14) : ?magasin_id=&modele_id=&q=. */
    public function getEquipementsDuMagasin(Request $request): JsonResponse
    {
        $request->validate(['magasin_id' => ['required', 'integer']]);

        $query = Equipement::query()
            ->where('statut', 'LIKE', 'en_stock%')
            ->whereIn('id', EquipementMagasin::query()
                ->where('magasin_id', (int) $request->input('magasin_id'))
                ->select('equipement_id'));

        if ($request->filled('modele_id')) {
            $article = Article::query()->find((int) $request->input('modele_id'));

            if ($article?->categorie_equipement_id !== null) {
                $query->where('categorie_id', $article->categorie_equipement_id);
            }
        }

        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));
            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(numero_serie) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(code_inventaire) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(modele) LIKE ?', ["%{$q}%"]);
            });
        }

        $total = $query->count();
        $rows = $query->orderBy('code_inventaire')
            ->limit(min((int) $request->input('limit', 30), 100))
            ->get(['id', 'code_inventaire', 'numero_serie', 'modele', 'etat', 'statut']);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** Disponible temps réel + réservations informatives (UX §4.2). */
    public function getDisponibilite(Request $request): JsonResponse
    {
        $request->validate([
            'magasin_id' => ['required', 'integer'],
            'article_id' => ['required', 'integer'],
        ]);

        $magasinId = (int) $request->input('magasin_id');
        $articleId = (int) $request->input('article_id');

        $disponible = (float) (Niveau::query()
            ->where('magasin_id', $magasinId)
            ->where('article_id', $articleId)
            ->value('quantite') ?? 0);

        // Réservations : lignes des sorties/transferts non validés (information non bloquante)
        $reservee = (float) LigneSortie::query()
            ->where('article_id', $articleId)
            ->whereHas('sortie', fn ($q) => $q->nonValides()->where('magasin_id', $magasinId))
            ->sum('quantite')
            + (float) LigneTransfert::query()
                ->where('article_id', $articleId)
                ->whereHas('transfert', fn ($q) => $q->nonValides()->where('magasin_source_id', $magasinId))
                ->sum('quantite');

        return response()->json(['disponible' => $disponible, 'reservee' => $reservee]);
    }

    /** Sélecteurs modaux de bénéficiaires (S11) : ?type=&q=. */
    public function getBeneficiaires(Request $request): JsonResponse
    {
        $request->validate(['type' => ['required', 'in:direction,service,unite,poste,local,employe']]);
        $q = mb_strtolower((string) $request->input('q', ''));

        $filtre = fn ($query, array $colonnes) => $query->when($q !== '', function ($sous) use ($q, $colonnes) {
            $sous->where(function ($w) use ($q, $colonnes) {
                foreach ($colonnes as $colonne) {
                    $w->orWhereRaw("LOWER({$colonne}) LIKE ?", ["%{$q}%"]);
                }
            });
        })->limit(30)->get();

        $rows = match ($request->input('type')) {
            'direction' => $filtre(Direction::query(), ['code', 'libelle'])
                ->map(fn ($d) => ['id' => $d->id, 'code' => $d->code, 'libelle' => $d->libelle]),
            'service' => $filtre(Service::query()->with('direction:id,libelle'), ['code', 'libelle'])
                ->map(fn ($s) => ['id' => $s->id, 'code' => $s->code, 'libelle' => $s->libelle, 'contexte' => $s->direction?->libelle]),
            'unite' => $filtre(Unite::query()->with('service:id,libelle'), ['code', 'libelle'])
                ->map(fn ($u) => ['id' => $u->id, 'code' => $u->code, 'libelle' => $u->libelle, 'contexte' => $u->service?->libelle]),
            'poste' => $filtre(PosteTravail::query(), ['code', 'libelle'])
                ->map(fn ($p) => ['id' => $p->id, 'code' => $p->code, 'libelle' => $p->libelle]),
            'local' => $filtre(Local::query(), ['code', 'libelle'])
                ->map(fn ($l) => ['id' => $l->id, 'code' => $l->code, 'libelle' => $l->libelle]),
            'employe' => $filtre(Employe::query()->where('est_actif', true), ['matricule', 'nom', 'prenom'])
                ->map(fn ($e) => ['id' => $e->id, 'code' => $e->matricule, 'libelle' => trim($e->prenom.' '.$e->nom), 'contexte' => $e->poste]),
        };

        return response()->json(['data' => $rows->values()]);
    }

    // ── Partagé avec TransfertController ───────────────────────────────────

    /** Actions du PUT de pointage — factorisées pour les transferts. */
    protected function traiterPointageUpdate($document, Request $request, PointageService $pointageService): JsonResponse
    {
        if ($document->statut !== 'POINTAGE') {
            return $this->refusVerrouillage($document);
        }

        $valide = $request->validate([
            'action' => ['required', 'in:pointer,depointer'],
            'ligne_id' => ['required_if:action,pointer', 'integer'],
            'equipement_id' => ['required_if:action,pointer', 'integer'],
            'tampon_id' => ['required_if:action,depointer', 'integer'],
        ]);

        try {
            if ($valide['action'] === 'pointer') {
                $ligne = $document->lignes()->with('article')->findOrFail((int) $valide['ligne_id']);
                $pointageService->pointer($document, $ligne, (int) $valide['equipement_id']);
            } else {
                $pointageService->depointer($document, (int) $valide['tampon_id']);
            }
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        [$pointees, $attendues] = $pointageService->progression($document);

        return response()->json([
            'success' => true,
            'statut_pointage' => ['pointees' => $pointees, 'attendues' => $attendues],
        ]);
    }

    /** Scan express (brouillon) — factorisé pour les transferts. */
    protected function traiterScanExpress($document, Request $request, PointageService $pointageService): JsonResponse
    {
        if ($document->statut !== 'BROUILLON') {
            return $this->refusVerrouillage($document);
        }

        $valide = $request->validate(['numero_serie' => ['required', 'string', 'max:255']]);

        try {
            $resultat = $pointageService->scanExpress($document, $valide['numero_serie']);
        } catch (StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => trim(($resultat['ligne']->article->nom ?? '').' — '.($resultat['equipement']['numero_serie'] ?? '').' ajouté'),
            'data' => $resultat,
        ]);
    }

    protected function refusVerrouillage($document): JsonResponse
    {
        $message = $document->verrouille()
            ? static::MESSAGE_VERROUILLAGE
            : 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.';

        return response()->json(['success' => false, 'message' => $message], 409);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    private function vueFormulaire(?Sortie $sortie)
    {
        return view('stock::sorties.form', [
            'sortie' => $sortie,
            'magasins' => Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'libelle']),
            'motifs' => config('stock.motifs_sortie'),
        ]);
    }

    private function synchroniserLignes(Sortie $sortie, array $lignes): void
    {
        $sortie->lignes()->delete();

        foreach ($lignes as $ligne) {
            LigneSortie::create([
                'sortie_id' => $sortie->id,
                'article_id' => $ligne['article_id'],
                'quantite' => $ligne['quantite'],
                'emplacement_local_id' => $ligne['emplacement_local_id'] ?? null,
            ]);
        }
    }

    /** Unités sorties (fiche/PDF) : journal + affectation créée (référence croisée). */
    private function unitesDuBon(Sortie $sortie): \Illuminate\Support\Collection
    {
        return $sortie->mouvements()
            ->whereNotNull('equipement_id')
            ->with(['equipement.categorie:id,code', 'affectationEquipement:id,code'])
            ->get()
            ->map(function ($mouvement) {
                $equipement = $mouvement->equipement;
                $routeFiche = 'parc-info.'.($equipement->categorie->code ?? '').'.show';

                return [
                    'code_inventaire' => $equipement->code_inventaire,
                    'numero_serie' => $equipement->numero_serie,
                    'modele' => $equipement->modele,
                    'affectation_code' => $mouvement->affectationEquipement?->code,
                    'url_fiche' => \Illuminate\Support\Facades\Route::has($routeFiche)
                        ? route($routeFiche, $equipement->id)
                        : null,
                ];
            });
    }
}
