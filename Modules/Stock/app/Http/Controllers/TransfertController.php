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
use Modules\Stock\Exceptions\StockException;
use Modules\Stock\Exceptions\TransitionInterditeException;
use Modules\Stock\Http\Requests\StoreTransfertRequest;
use Modules\Stock\Http\Requests\UpdateTransfertRequest;
use Modules\Stock\Models\LigneTransfert;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Transfert;
use Modules\Stock\Services\PointageService;
use Modules\Stock\Services\ValiderTransfertService;

class TransfertController extends Controller implements HasMiddleware
{
    use Concerns\GerePointageHttp;

    public const MESSAGE_VERROUILLAGE = SortieController::MESSAGE_VERROUILLAGE;

    public static function middleware(): array
    {
        // SFD §5 : store couvre création, pointage, validation, PDF
        return [
            new Middleware('permission:stock.transferts.index', only: ['index', 'getData', 'show']),
            new Middleware('permission:stock.transferts.store', only: [
                'create', 'store', 'pointage', 'pointageShow', 'pointageUpdate', 'scanExpress',
                'retourBrouillon', 'valider', 'pdf',
            ]),
            new Middleware('permission:stock.transferts.update', only: ['edit', 'update']),
            new Middleware('permission:stock.transferts.destroy', only: ['destroy']),
        ];
    }

    public function index()
    {
        return view('stock::transferts.index', [
            'magasins' => Magasin::query()->orderBy('libelle')->get(['id', 'libelle']),
            'monMagasin' => $this->magasinDeLUtilisateur(),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Transfert::query()
            ->with(['magasinSource:id,code,libelle', 'magasinCible:id,code,libelle', 'createur:id,name'])
            ->withCount('lignes as nb_lignes');

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('magasin_id')) {
            $query->duMagasin((int) $request->input('magasin_id'));
        }

        // ?cible=mon-magasin (SFD §8) : magasin dont l'utilisateur est
        // responsable, résolu via users.dossier_employe_id ↔
        // stock_magasins.responsable_id
        if ($request->input('cible') === 'mon-magasin') {
            $monMagasin = $this->magasinDeLUtilisateur();

            $query->where('magasin_cible_id', $monMagasin?->id ?? -1);
        }

        if ($request->filled('du')) {
            $query->where('date_document', '>=', $request->input('du'));
        }

        if ($request->filled('au')) {
            $query->where('date_document', '<=', $request->input('au'));
        }

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->whereRaw('LOWER(numero) LIKE ?', ["%{$search}%"]);
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
            ->map(function (Transfert $transfert) use ($pointageService) {
                [$pointees, $attendues] = $transfert->statut === Transfert::STATUT_POINTAGE
                    ? $pointageService->progression($transfert)
                    : [0, 0];

                return [
                    'id' => $transfert->id,
                    'numero_affiche' => $transfert->numero_affiche,
                    'date_document' => $transfert->date_document?->format('d/m/Y'),
                    'magasin_source' => $transfert->magasinSource?->libelle,
                    'magasin_cible' => $transfert->magasinCible?->libelle,
                    'transporte_par' => $transfert->transporte_par_nom,
                    'nb_lignes' => $transfert->nb_lignes,
                    'statut' => $transfert->statut,
                    'progression' => $attendues > 0 ? "{$pointees}/{$attendues} unités" : null,
                    'nb_pointees' => $pointees,
                    'can_edit' => $transfert->canEdit(),
                    'can_delete' => $transfert->canDelete(),
                    'cree_par' => $transfert->createur?->name,
                ];
            });

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function create()
    {
        return $this->vueFormulaire(null);
    }

    public function store(StoreTransfertRequest $request): JsonResponse
    {
        try {
            $transfert = DB::transaction(function () use ($request) {
                $transfert = Transfert::create(array_merge(
                    collect($request->validated())->except('lignes')->all(),
                    ['created_by' => auth()->id()]
                ));

                $this->synchroniserLignes($transfert, $request->validated()['lignes'] ?? []);

                return $transfert;
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$transfert->id} enregistré.",
                'data' => ['id' => $transfert->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du brouillon de transfert', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $transfert = Transfert::query()
            ->with(['magasinSource', 'magasinCible', 'lignes.article', 'transporteParEmploye', 'createur:id,name', 'valideur:id,name'])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $transfert]);
        }

        return match ($transfert->statut) {
            Transfert::STATUT_BROUILLON => redirect()->route('stock.transferts.edit', $transfert->id),
            Transfert::STATUT_POINTAGE => redirect()->route('stock.transferts.pointage.show', $transfert->id),
            default => view('stock::transferts.show', [
                'transfert' => $transfert,
                'unites' => $this->unitesDuBon($transfert),
            ]),
        };
    }

    public function edit($id)
    {
        $transfert = Transfert::query()->with(['lignes.article'])->findOrFail($id);

        if (! $transfert->canEdit()) {
            return $transfert->statut === Transfert::STATUT_POINTAGE
                ? redirect()->route('stock.transferts.pointage.show', $transfert->id)
                : redirect()->route('stock.transferts.show', $transfert->id);
        }

        return $this->vueFormulaire($transfert);
    }

    public function update(UpdateTransfertRequest $request, $id): JsonResponse
    {
        $transfert = Transfert::query()->findOrFail($id);

        if (! $transfert->canEdit()) {
            return $this->refusVerrouillage($transfert);
        }

        try {
            DB::transaction(function () use ($request, $transfert) {
                $transfert->update(collect($request->validated())->except('lignes')->all());
                $this->synchroniserLignes($transfert, $request->validated()['lignes'] ?? []);
            });

            return response()->json([
                'success' => true,
                'message' => "Brouillon #{$transfert->id} enregistré.",
                'data' => ['id' => $transfert->id],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du brouillon de transfert', ['exception' => $e, 'transfert_id' => $transfert->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $transfert = Transfert::query()->findOrFail($id);

        if (! $transfert->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Bon validé — non modifiable. Corrigez par contre-mouvement depuis l\'historique.',
            ], 409);
        }

        $transfert->delete();

        return response()->json(['success' => true, 'message' => "Le brouillon #{$transfert->id} a été supprimé."]);
    }

    // ── Pointage — mêmes handlers que les sorties (GerePointageHttp) ──────

    public function pointage($id): JsonResponse
    {
        $transfert = Transfert::query()->findOrFail($id);

        try {
            app(PointageService::class)->passerEnPointage($transfert);

            return response()->json([
                'success' => true,
                'data' => ['pointage_url' => route('stock.transferts.pointage.show', $transfert->id)],
            ]);
        } catch (TransitionInterditeException|StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    public function pointageShow($id)
    {
        $transfert = Transfert::query()->with(['magasinSource:id,code,libelle'])->findOrFail($id);

        if ($transfert->statut !== Transfert::STATUT_POINTAGE) {
            return match ($transfert->statut) {
                Transfert::STATUT_BROUILLON => redirect()->route('stock.transferts.edit', $transfert->id),
                default => redirect()->route('stock.transferts.show', $transfert->id),
            };
        }

        $pointageService = app(PointageService::class);
        [$pointees, $attendues] = $pointageService->progression($transfert);

        return view('stock::shared.pointage_ecran', [
            'titre' => 'Pointage — '.$transfert->numero_affiche,
            'filAriane' => 'Transferts',
            'typeDocument' => 'transfert',
            'document' => $transfert,
            'lignesModeles' => $pointageService->lignesModeles($transfert),
            'pointees' => $pointees,
            'attendues' => $attendues,
            'routes' => [
                'update' => route('stock.transferts.pointage.update', $transfert->id),
                'retour' => route('stock.transferts.retour-brouillon', $transfert->id),
                'valider' => route('stock.transferts.valider', $transfert->id),
                'unites' => route('stock.equipements.du-magasin'),
                'index' => route('stock.transferts.index'),
            ],
            'magasinSourceId' => $transfert->magasinSourceId(),
            'verrouillageMessage' => self::MESSAGE_VERROUILLAGE,
        ]);
    }

    public function pointageUpdate(Request $request, $id): JsonResponse
    {
        return $this->traiterPointageUpdate(Transfert::query()->findOrFail($id), $request, app(PointageService::class));
    }

    public function scanExpress(Request $request, $id): JsonResponse
    {
        return $this->traiterScanExpress(Transfert::query()->findOrFail($id), $request, app(PointageService::class));
    }

    public function retourBrouillon($id): JsonResponse
    {
        $transfert = Transfert::query()->findOrFail($id);

        try {
            $depointees = app(PointageService::class)->retourBrouillon($transfert);

            return response()->json([
                'success' => true,
                'message' => $depointees > 0
                    ? "Retour au brouillon : {$depointees} unité(s) dépointée(s). Les articles et quantités du bon sont conservés."
                    : 'Retour au brouillon. Les articles et quantités du bon sont conservés.',
                'data' => ['edit_url' => route('stock.transferts.edit', $transfert->id)],
            ]);
        } catch (TransitionInterditeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }
    }

    public function valider(Request $request, $id): JsonResponse
    {
        $transfert = Transfert::query()->with(['magasinSource', 'magasinCible'])->findOrFail($id);
        $service = app(ValiderTransfertService::class);

        if ($request->boolean('recap')) {
            return response()->json(['success' => true, 'recap' => $service->recapDocument($transfert)]);
        }

        $valide = $request->validate(['jeton' => ['required', 'string', 'max:64']]);

        try {
            $recap = $service->valider($transfert, $valide['jeton'], auth()->id());
        } catch (TransitionInterditeException|StockException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->status());
        }

        return response()->json([
            'success' => true,
            'message' => "Transfert {$recap['numero']} validé.",
            'recap' => $recap,
            'data' => ['show_url' => route('stock.transferts.show', $transfert->id)],
        ]);
    }

    public function pdf($id)
    {
        $transfert = Transfert::query()
            ->with(['magasinSource', 'magasinCible', 'lignes.article', 'transporteParEmploye', 'createur:id,name', 'valideur:id,name'])
            ->findOrFail($id);

        if (! $transfert->estValide()) {
            return response()->json(['success' => false, 'message' => 'Le bon PDF n\'existe qu\'après validation.'], 409);
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('stock::pdf.transfert', [
            'transfert' => $transfert,
            'unites' => $this->unitesDuBon($transfert),
        ])->setPaper('a4')->stream("bon-transfert-{$transfert->numero}.pdf");
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** Magasin dont l'utilisateur est responsable (users.dossier_employe_id). */
    private function magasinDeLUtilisateur(): ?Magasin
    {
        $employeId = auth()->user()?->dossier_employe_id;

        return $employeId
            ? Magasin::query()->where('responsable_id', $employeId)->first()
            : null;
    }

    private function vueFormulaire(?Transfert $transfert)
    {
        return view('stock::transferts.form', [
            'transfert' => $transfert,
            'magasins' => Magasin::query()->actifs()->orderBy('libelle')->get(['id', 'libelle']),
        ]);
    }

    private function synchroniserLignes(Transfert $transfert, array $lignes): void
    {
        $transfert->lignes()->delete();

        foreach ($lignes as $ligne) {
            LigneTransfert::create([
                'transfert_id' => $transfert->id,
                'article_id' => $ligne['article_id'],
                'quantite' => $ligne['quantite'],
            ]);
        }
    }

    /** Unités transférées (fiche/PDF), retrouvées par le journal (côté entrée cible). */
    private function unitesDuBon(Transfert $transfert): \Illuminate\Support\Collection
    {
        return $transfert->mouvements()
            ->whereNotNull('equipement_id')
            ->where('type', \Modules\Stock\Models\Mouvement::TYPE_TRANSFERT_ENTREE)
            ->with('equipement.categorie:id,code')
            ->get()
            ->map(function ($mouvement) {
                $equipement = $mouvement->equipement;
                $routeFiche = 'parc-info.'.($equipement->categorie->code ?? '').'.show';

                return [
                    'code_inventaire' => $equipement->code_inventaire,
                    'numero_serie' => $equipement->numero_serie,
                    'modele' => $equipement->modele,
                    'url_fiche' => \Illuminate\Support\Facades\Route::has($routeFiche)
                        ? route($routeFiche, $equipement->id)
                        : null,
                ];
            });
    }
}
