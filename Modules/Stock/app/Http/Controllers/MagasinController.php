<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Log;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Http\Requests\StoreMagasinRequest;
use Modules\Stock\Http\Requests\UpdateMagasinRequest;

class MagasinController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:stock.magasins.index', only: [
                'index', 'getData', 'show', 'getEquipementsData', 'getMouvementsData',
            ]),
            // Cascades de la modale MD-MAGASIN : accessibles en création comme en édition
            new Middleware('permission:stock.magasins.store|stock.magasins.update', only: [
                'getSitesDisponibles', 'getLocaux', 'getResponsables',
            ]),
            new Middleware('permission:stock.magasins.store', only: ['store']),
            new Middleware('permission:stock.magasins.update', only: ['update']),
            new Middleware('permission:stock.magasins.destroy', only: ['destroy']),
            new Middleware('permission:stock.magasins.toggle-status', only: ['toggleStatus']),
        ];
    }

    public function index()
    {
        return view('stock::magasins.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Magasin::query()
            ->with(['site:id,code,libelle', 'local:id,code,libelle', 'responsable:id,nom,prenom'])
            ->withCount([
                'niveaux as nb_references' => fn ($q) => $q->where('quantite', '>', 0),
                'equipementsRattaches as nb_equipements',
            ]);

        if ($request->filled('search')) {
            $search = mb_strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(libelle) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('statut')) {
            $query->where('est_actif', $request->input('statut') === 'actif');
        }

        $sort = $request->input('sort', 'id');
        $allowed = ['id', 'code', 'libelle', 'nb_references', 'est_actif'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'id';
        }
        $query->orderBy($sort, strtolower($request->input('order', 'asc')) === 'desc' ? 'desc' : 'asc');

        $total = $query->count();
        $rows = $query
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Magasin $magasin) => [
                'id' => $magasin->id,
                'code' => $magasin->code,
                'libelle' => $magasin->libelle,
                'site' => $magasin->site?->libelle,
                'local' => $magasin->local?->libelle,
                'responsable' => $magasin->responsable ? trim($magasin->responsable->prenom.' '.$magasin->responsable->nom) : null,
                'nb_references' => $magasin->nb_references,
                'nb_equipements' => $magasin->nb_equipements,
                'est_actif' => $magasin->est_actif,
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /**
     * Sites sans magasin (D2 : un magasin par site). En édition, le site du
     * magasin courant reste listé (?inclure={magasin_id}) — il est verrouillé
     * côté UI mais doit s'afficher.
     */
    public function getSitesDisponibles(Request $request): JsonResponse
    {
        $query = Site::query()
            ->where('actif', true)
            ->whereDoesntHave('magasins')
            ->orderBy('libelle');

        if ($request->filled('inclure')) {
            $magasin = Magasin::query()->find((int) $request->input('inclure'));

            if ($magasin !== null) {
                $query->orWhere('id', $magasin->site_id);
            }
        }

        return response()->json([
            'data' => $query->get(['id', 'code', 'libelle'])
                ->map(fn (Site $site) => ['id' => $site->id, 'code' => $site->code, 'text' => $site->libelle]),
        ]);
    }

    /** Locaux d'un site — type « magasin » en premier (UX MD-MAGASIN). */
    public function getLocaux(Request $request): JsonResponse
    {
        $request->validate(['site_id' => ['required', 'integer']]);

        $locaux = Local::query()
            ->where('actif', true)
            ->whereHas('etage.batiment', fn ($q) => $q->where('site_id', (int) $request->input('site_id')))
            ->orderByRaw("CASE WHEN type_local = 'magasin' THEN 0 ELSE 1 END")
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle', 'type_local'])
            ->map(fn (Local $local) => [
                'id' => $local->id,
                'text' => $local->libelle.' ('.$local->code.')',
                'type_local' => $local->type_local,
            ]);

        return response()->json(['data' => $locaux]);
    }

    /** Employés Grh actifs pour le Select2 « Responsable ». */
    public function getResponsables(Request $request): JsonResponse
    {
        $query = Employe::query()->where('est_actif', true);

        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));
            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(nom) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(prenom) LIKE ?', ["%{$q}%"])
                    ->orWhereRaw('LOWER(matricule) LIKE ?', ["%{$q}%"]);
            });
        }

        return response()->json([
            'data' => $query->orderBy('nom')->limit(30)->get(['id', 'matricule', 'nom', 'prenom'])
                ->map(fn (Employe $employe) => [
                    'id' => $employe->id,
                    'text' => trim($employe->prenom.' '.$employe->nom).' ('.$employe->matricule.')',
                ]),
        ]);
    }

    public function store(StoreMagasinRequest $request): JsonResponse
    {
        try {
            $site = Site::query()->findOrFail((int) $request->validated()['site_id']);

            $magasin = Magasin::create(array_merge($request->validated(), [
                'code' => 'MAG-'.$site->code,
                'created_by' => auth()->id(),
            ]));

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->libelle} » a été créé ({$magasin->code}).",
                'data' => ['id' => $magasin->id, 'code' => $magasin->code],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du magasin', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /** JSON de pré-remplissage de la modale, ou fiche HTML à onglets (UX §9). */
    public function show(Request $request, $id)
    {
        $magasin = Magasin::query()
            ->with(['site:id,code,libelle', 'local:id,code,libelle', 'responsable:id,matricule,nom,prenom'])
            ->withCount([
                'niveaux as nb_references' => fn ($q) => $q->where('quantite', '>', 0),
                'equipementsRattaches as nb_equipements',
            ])
            ->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'data' => $magasin]);
        }

        $kpis = [
            'nb_references' => $magasin->nb_references,
            'nb_equipements' => $magasin->nb_equipements,
            'valeur_estimee' => $this->valeurEstimee($magasin),
        ];

        $motifsBlocage = $this->motifsBlocage($magasin);

        return view('stock::magasins.show', compact('magasin', 'kpis', 'motifsBlocage'));
    }

    /** Onglet « Équipements présents » de la fiche. */
    public function getEquipementsData(Request $request, $id): JsonResponse
    {
        $magasin = Magasin::query()->findOrFail($id);

        $query = EquipementMagasin::query()
            ->duMagasin($magasin->id)
            ->with('equipement:id,code_inventaire,numero_serie,modele,statut');

        $total = $query->count();
        $rows = $query
            ->orderByDesc('date_rattachement')
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (EquipementMagasin $rattachement) => [
                'id' => $rattachement->id,
                'code_inventaire' => $rattachement->equipement?->code_inventaire,
                'modele' => $rattachement->equipement?->modele,
                'numero_serie' => $rattachement->equipement?->numero_serie,
                'statut' => $rattachement->equipement?->statut,
                'date_rattachement' => $rattachement->date_rattachement?->format('d/m/Y H:i'),
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    /** Onglet « Derniers mouvements » de la fiche. */
    public function getMouvementsData(Request $request, $id): JsonResponse
    {
        $magasin = Magasin::query()->findOrFail($id);

        $query = Mouvement::query()
            ->duMagasin($magasin->id)
            ->with(['article:id,code,nom', 'equipement:id,code_inventaire,modele', 'createur:id,name']);

        $total = $query->count();
        $rows = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->offset((int) $request->input('offset', 0))
            ->limit((int) $request->input('limit', 10))
            ->get()
            ->map(fn (Mouvement $mouvement) => [
                'id' => $mouvement->id,
                'date' => $mouvement->created_at?->format('d/m/Y H:i'),
                'type' => $mouvement->type,
                'article' => $mouvement->article?->nom ?? $mouvement->equipement?->modele,
                'quantite_signee' => $mouvement->quantite_signee,
                'par' => $mouvement->createur?->name,
            ]);

        return response()->json(['total' => $total, 'rows' => $rows]);
    }

    public function update(UpdateMagasinRequest $request, $id): JsonResponse
    {
        $magasin = Magasin::query()->findOrFail($id);

        try {
            $magasin->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->libelle} » a été modifié avec succès.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du magasin', ['exception' => $e, 'magasin_id' => $magasin->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        $magasin = Magasin::query()->findOrFail($id);

        if (($reponse = $this->refuserSiGarni($magasin, 'supprimer')) !== null) {
            return $reponse;
        }

        if ($magasin->mouvements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => "Le magasin {$magasin->code} est référencé par le journal des mouvements : il ne peut plus être supprimé, seulement désactivé.",
            ], 422);
        }

        try {
            $magasin->delete();

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->libelle} » a été supprimé.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la suppression du magasin', ['exception' => $e, 'magasin_id' => $magasin->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function toggleStatus($id): JsonResponse
    {
        $magasin = Magasin::query()->findOrFail($id);

        // La désactivation d'un magasin garni est refusée (SFD §3.2) ;
        // la réactivation est toujours possible.
        if ($magasin->est_actif && ($reponse = $this->refuserSiGarni($magasin, 'désactiver')) !== null) {
            return $reponse;
        }

        try {
            $magasin->update(['est_actif' => ! $magasin->est_actif]);

            $etat = $magasin->est_actif ? 'activé' : 'désactivé';

            return response()->json([
                'success' => true,
                'message' => "Le magasin « {$magasin->libelle} » a été {$etat}.",
                'data' => ['est_actif' => $magasin->est_actif],
            ]);
        } catch (Exception $e) {
            Log::error('Erreur au changement de statut du magasin', ['exception' => $e, 'magasin_id' => $magasin->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /**
     * Garde UX §9 : magasin garni → SW-422 énumérant le contenu, avec
     * bouton contextuel « Voir l'état des stocks ».
     */
    private function refuserSiGarni(Magasin $magasin, string $action): ?JsonResponse
    {
        $motifs = $this->motifsBlocage($magasin);

        if ($motifs === []) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => "Le magasin {$magasin->code} contient ".implode(' et ', $motifs)
                ." — transférez ou sortez son contenu avant de le {$action}.",
            'action' => [
                'label' => 'Voir l\'état des stocks',
                'url' => route('stock.niveaux.index', ['magasin_id' => $magasin->id]),
            ],
        ], 422);
    }

    private function motifsBlocage(Magasin $magasin): array
    {
        $motifs = [];

        $nbReferences = $magasin->niveaux()->where('quantite', '>', 0)->count();
        if ($nbReferences > 0) {
            $motifs[] = "{$nbReferences} référence(s) (".$this->formatMontant($this->valeurEstimee($magasin)).')';
        }

        $nbEquipements = $magasin->equipementsRattaches()->count();
        if ($nbEquipements > 0) {
            $motifs[] = "{$nbEquipements} équipement(s)";
        }

        return $motifs;
    }

    /** Valorisation au prix indicatif du catalogue (quantité × prix). */
    private function valeurEstimee(Magasin $magasin): float
    {
        return (float) $magasin->niveaux()
            ->join('catalogue_articles', 'catalogue_articles.id', '=', 'stock_niveaux.article_id')
            ->selectRaw('COALESCE(SUM(stock_niveaux.quantite * COALESCE(catalogue_articles.prix_indicatif, 0)), 0) AS valeur')
            ->value('valeur');
    }

    /** « 3,3 M FCFA » au-delà du million, sinon « 450 000 FCFA ». */
    private function formatMontant(float $montant): string
    {
        if ($montant >= 1_000_000) {
            return number_format($montant / 1_000_000, 1, ',', ' ').' M FCFA';
        }

        return number_format($montant, 0, ',', ' ').' FCFA';
    }
}
