<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Achat\Exceptions\AchatException;
use Modules\Achat\Exceptions\SoumissionRefuseeException;
use Modules\Achat\Http\Requests\MotifRequest;
use Modules\Achat\Http\Requests\RenvoyerBonCommandeRequest;
use Modules\Achat\Http\Requests\StoreBonCommandeRequest;
use Modules\Achat\Http\Requests\UpdateBonCommandeRequest;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\ActionsBonCommande;
use Modules\Achat\Services\CalculMontantsService;
use Modules\Achat\Services\ChronologieBonCommande;
use Modules\Achat\Services\CircuitSoumissionService;
use Modules\Achat\Services\ControlesSoumissionService;
use Modules\Achat\Services\DuplicationBonCommande;
use Modules\Achat\Services\FinDeVieService;
use Modules\Achat\Services\LignesBonCommandeService;
use Modules\Achat\Services\ReceptionsBonCommande;
use Modules\Achat\Services\RechercheBonCommande;
use Modules\Achat\Services\ReferencePrixService;
use Modules\Achat\Services\VisaService;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Organisation\Models\Service;

/**
 * A-02 — Liste des bons de commande (SPEC_UX A-02).
 *
 * Bootstrap Table en pagination SERVEUR, comme la liste des entrées du Stock
 * dont cet écran est dérivé : les filtres, le tri et les totaux sont calculés
 * par la base sur l'ensemble du jeu filtré, jamais sur la page affichée.
 */
class BonCommandeController extends Controller implements HasMiddleware
{
    /** Colonnes triables — liste blanche : `sort` vient du navigateur. */
    private const TRIS_AUTORISES = [
        'id', 'numero', 'date_document', 'statut', 'montant_ttc', 'nb_lignes',
    ];

    public function __construct(
        private readonly ActionsBonCommande $actions,
        private readonly RechercheBonCommande $recherche,
        private readonly AchatParametres $parametres,
        private readonly LignesBonCommandeService $lignes,
        private readonly CalculMontantsService $montants,
        private readonly ReferencePrixService $referencePrix,
        private readonly ControlesSoumissionService $controles,
        private readonly CircuitSoumissionService $circuit,
        private readonly VisaService $visa,
        private readonly ChronologieBonCommande $chronologie,
        private readonly FinDeVieService $finDeVie,
        private readonly ReceptionsBonCommande $receptions,
        private readonly DuplicationBonCommande $duplication,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.index', only: ['index', 'getData', 'show']),
            // D-23 : dupliquer, c'est CRÉER un bon — même permission que la
            // saisie, et non une permission de lecture.
            new Middleware('permission:achat.bons_commande.store', only: ['create', 'store', 'dupliquer']),
            new Middleware('permission:achat.bons_commande.update', only: ['edit', 'update']),
            new Middleware('permission:achat.bons_commande.destroy', only: ['destroy']),
            // Le récapitulatif de l'étape ② précède immédiatement la
            // soumission : même permission que le geste qu'il prépare.
            new Middleware('permission:achat.bons_commande.soumettre', only: ['recapitulatif', 'soumettre', 'reprendre']),
            // Valider et renvoyer sont les deux faces du visa : une seule
            // permission les porte (SFD §5).
            new Middleware('permission:achat.bons_commande.valider', only: ['renvoyer', 'signaux', 'valider']),
            // Fin de vie (SFD §7.5) : deux permissions dédiées, distinctes du
            // visa — annuler un engagement n'est pas le même pouvoir que le
            // créer.
            new Middleware('permission:achat.bons_commande.annuler', only: ['annuler']),
            new Middleware('permission:achat.bons_commande.cloturer', only: ['cloturer']),
            // La reference de prix sert les DEUX ecrans de saisie : la
            // creation comme l'edition y ont droit.
            new Middleware('permission:achat.bons_commande.store|achat.bons_commande.update', only: ['referencePrix']),
        ];
    }

    public function index(Request $request)
    {
        return view('achat::bons-commande.index', [
            'fournisseurs' => Fournisseur::query()
                ->orderBy('raison_sociale')
                ->get(['id', 'raison_sociale']),
            'statuts' => BonCommande::STATUT_LABELS,
            'couleurs' => BonCommande::STATUT_COULEURS,
            // Filtres pré-positionnés par les KPI du tableau de bord (UX-03) :
            // « À valider » arrive ici avec ?statut=SOUMIS déjà coché.
            'statutsPreselectionnes' => $this->statutsDemandes($request),
            'regularisationsSeules' => $request->boolean('regularisations'),
            'mesBrouillons' => $request->boolean('mes_brouillons'),
            // Le menu « BC de régularisation » ne s'ouvre que si la porte
            // d'intérim est ouverte (A15) : sinon l'entrée n'existe pas.
            'regularisationActive' => $this->parametres->regularisationActive(),
        ]);
    }

    /**
     * Charge utile du tableau : lignes, total du filtre courant, et somme TTC
     * du pied de tableau (SPEC_UX A-02 : « {n} bons · Total affiché : {X} FCFA
     * TTC »).
     */
    public function getData(Request $request): JsonResponse
    {
        $utilisateur = $request->user();

        $query = BonCommande::query()
            ->with(['fournisseur:id,raison_sociale', 'serviceDemandeur:id,libelle', 'createur:id,name'])
            ->withCount('lignes as nb_lignes')
            // Progression de livraison : sommes des lignes, calculées par la
            // base — le navigateur ne recompte rien (IA-1).
            ->withSum('lignes as total_commande', 'quantite')
            ->withSum('lignes as total_livre', 'quantite_livree');

        $this->appliquerFiltres($query, $request, $utilisateur);
        $recherche = $this->recherche->appliquer($query, $request->input('search'));

        // Totaux du filtre courant, AVANT pagination : le pied de tableau
        // décrit le jeu filtré entier, pas la page visible (UX2-05).
        $total = (clone $query)->count();
        $montantTtc = (float) (clone $query)->sum('montant_ttc');

        $this->appliquerTri($query, $request);

        $lignes = $query
            ->offset(max(0, (int) $request->input('offset', 0)))
            ->limit($this->limite($request))
            ->get()
            ->map(fn (BonCommande $bon) => $this->ligne($bon, $utilisateur));

        return response()->json([
            'total' => $total,
            'rows' => $lignes,
            'montant_ttc_affiche' => round($montantTtc, 2),
            'recherche' => $recherche,
        ]);
    }

    /**
     * Une ligne du tableau, telle que la vue l'attend.
     *
     * La toolbar de la liste (pattern du projet : sélection d'une ligne puis
     * boutons) décide de l'état de ses boutons à partir des DRAPEAUX émis ici,
     * calculés par la même grille serveur que partout (ActionsBonCommande) :
     * le navigateur ne déduit rien des statuts, il lit des booléens. Le
     * serveur revérifie de toute façon permission et état à chaque POST.
     */
    private function ligne(BonCommande $bon, $utilisateur): array
    {
        $commande = (float) ($bon->total_commande ?? 0);
        $livre = (float) ($bon->total_livre ?? 0);

        $actions = collect($this->actions->pour($bon, $utilisateur))->keyBy('cle');

        return [
            'id' => $bon->id,
            'numero_affiche' => $bon->numero_affiche,
            'est_regularisation' => (bool) $bon->est_regularisation,
            // Libellé photographié à la validation s'il existe, sinon le
            // référentiel vivant : un fournisseur supprimé au Catalogue ne
            // doit pas vider la colonne d'un bon engagé (SFD §6.2).
            'fournisseur' => $bon->fournisseur_libelle
                ?? $bon->fournisseur?->raison_sociale
                ?? '—',
            'date_document' => $bon->date_document?->format('d/m/Y'),
            'service_demandeur' => $bon->service_demandeur_libelle
                ?? $bon->serviceDemandeur?->libelle
                ?? '—',
            'nb_lignes' => (int) $bon->nb_lignes,
            'montant_ttc' => (float) $bon->montant_ttc,
            // La progression n'a de sens qu'une fois le bon engagé : avant
            // validation, il n'y a rien à livrer (SPEC_UX A-02).
            'progression' => $bon->estEngage() && $commande > 0
                ? [
                    'livre' => round($livre, 2),
                    'commande' => round($commande, 2),
                    'pourcentage' => (int) round($livre / $commande * 100),
                ]
                : null,
            'statut' => $bon->statut,
            'statut_label' => $bon->statut_label,
            'statut_couleur' => $bon->statut_couleur,
            'cree_par' => $bon->createur?->name ?? '—',

            // ── Drapeaux de la toolbar (grille ActionsBonCommande) ────────
            'peut_voir' => $actions->has('voir'),
            'peut_modifier' => (bool) ($actions['modifier']['actif'] ?? false),
            'peut_supprimer' => (bool) ($actions['supprimer']['actif'] ?? false),
            'peut_soumettre' => (bool) ($actions['soumettre']['actif'] ?? false),
            'peut_reprendre' => $actions->has('reprendre'),
            'peut_valider' => $actions->has('valider'),
            'peut_renvoyer' => $actions->has('renvoyer'),
            'peut_imprimer' => $actions->has('pdf'),
            // D-23 : la duplication est offerte sur tous les statuts sauf
            // ANNULÉ, et grisée sur un bon sans ligne.
            'peut_dupliquer' => (bool) ($actions['dupliquer']['actif'] ?? false),
            'diagnostic_duplication' => $actions['dupliquer']['titre'] ?? null,
            // Diagnostics des boutons grisés par l'état (SPEC_UX §0.3)
            'diagnostic_modification' => $actions['modifier']['titre'] ?? null,
            'diagnostic_soumission' => $actions['soumettre']['titre'] ?? null,
            'url_pdf' => $actions['pdf']['url'] ?? null,
        ];
    }

    private function appliquerFiltres(Builder $query, Request $request, $utilisateur): void
    {
        $query->parStatut($this->statutsDemandes($request));

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', (int) $request->input('fournisseur_id'));
        }

        if ($request->filled('du')) {
            $query->whereDate('date_document', '>=', $request->input('du'));
        }

        if ($request->filled('au')) {
            $query->whereDate('date_document', '<=', $request->input('au'));
        }

        if ($request->boolean('regularisations')) {
            $query->regularisations();
        }

        // « Mes brouillons » (raccourci Z5 du tableau de bord).
        if ($request->boolean('mes_brouillons')) {
            $query->where('created_by', $utilisateur->id)
                ->where('statut', BonCommande::STATUT_BROUILLON);
        }
    }

    /**
     * Statuts cochés : les pilules sont multiples, mais un lien du tableau de
     * bord n'en passe qu'un seul (`?statut=SOUMIS`). Les deux formes sont
     * acceptées, et les valeurs inconnues écartées.
     *
     * @return list<string>
     */
    private function statutsDemandes(Request $request): array
    {
        $statuts = $request->input('statut', $request->input('statuts', []));

        return array_values(array_intersect(
            array_map('strtoupper', array_filter((array) $statuts, 'is_string')),
            BonCommande::STATUTS
        ));
    }

    private function appliquerTri(Builder $query, Request $request): void
    {
        $colonne = (string) $request->input('sort', 'id');
        $sens = strtolower((string) $request->input('order', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy(
            in_array($colonne, self::TRIS_AUTORISES, true) ? $colonne : 'id',
            $sens
        );
    }

    /** Bornée : `limit` vient du navigateur, il ne décide pas de la charge. */
    private function limite(Request $request): int
    {
        return max(1, min(100, (int) $request->input('limit', 10)));
    }

    // ═══ A-03 — Création et édition d'un brouillon (SPEC_UX A-03) ═══════════

    /**
     * Étape ① d'un nouveau brouillon. `?regularisation=1` ouvre le mode
     * d'intérim, mais seulement si la porte est ouverte (A15) et que
     * l'utilisateur en a le droit : sinon on retombe sur un bon ordinaire
     * plutôt que d'afficher un écran qu'on refusera d'enregistrer.
     */
    public function create(Request $request)
    {
        $regularisation = $request->boolean('regularisation')
            && $this->parametres->regularisationActive()
            && $request->user()->can('achat.bons_commande.regulariser');

        return view('achat::bons-commande.form', $this->donneesFormulaire(null, $regularisation));
    }

    /**
     * Édition d'un brouillon. Hors brouillon, l'écran de saisie n'existe
     * pas : redirection vers la fiche avec l'explication (SPEC_UX A-03), et
     * non un 403 sec — l'utilisateur a le droit, c'est l'état qui s'y oppose.
     */
    public function edit(Request $request, int $id)
    {
        $bon = BonCommande::query()->with(['lignes.article', 'renvoyeur', 'fournisseur'])->findOrFail($id);

        if (! $bon->estModifiable()) {
            return $this->redirigerVersLaFiche($bon, $bon->diagnosticModification());
        }

        return view('achat::bons-commande.form', $this->donneesFormulaire($bon, (bool) $bon->est_regularisation));
    }

    public function store(StoreBonCommandeRequest $request): JsonResponse
    {
        try {
            $bon = DB::transaction(function () use ($request) {
                $bon = BonCommande::create(array_merge(
                    collect($request->validated())->except(['lignes', 'updated_at'])->all(),
                    ['created_by' => $request->user()->id]
                ));

                $this->lignes->synchroniser($bon, $request->validated()['lignes'] ?? []);

                return $bon;
            });

            return response()->json([
                'success' => true,
                'message' => 'Brouillon enregistré.',
                'data' => $this->etatBrouillon($bon->refresh()),
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la création du brouillon de bon de commande', ['exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    public function update(UpdateBonCommandeRequest $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        // Hors brouillon : 409, jamais d'écrasement d'un document engagé.
        if (! $bon->estModifiable()) {
            return response()->json([
                'success' => false,
                'message' => $bon->diagnosticModification(),
            ], 409);
        }

        if ($conflit = $this->detecterConflit($bon, $request->input('updated_at'))) {
            return $conflit;
        }

        try {
            DB::transaction(function () use ($request, $bon) {
                $bon->update(collect($request->validated())->except(['lignes', 'updated_at'])->all());
                $this->lignes->synchroniser($bon, $request->validated()['lignes'] ?? []);
            });

            return response()->json([
                'success' => true,
                'message' => 'Brouillon enregistré.',
                'data' => $this->etatBrouillon($bon->refresh()),
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la modification du brouillon', ['exception' => $e, 'bon_id' => $bon->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /** Suppression réelle avec cascade — brouillon uniquement (SW-04). */
    public function destroy(int $id): JsonResponse
    {
        $bon = BonCommande::query()->withCount('lignes as nb_lignes')->findOrFail($id);

        if (! $bon->estSupprimable()) {
            return response()->json([
                'success' => false,
                'message' => $bon->diagnosticModification(),
            ], 409);
        }

        try {
            $identifiant = $bon->numero_affiche;
            $bon->delete();

            return response()->json([
                'success' => true,
                'message' => "Le brouillon {$identifiant} a été supprimé.",
            ]);
        } catch (Exception $e) {
            Log::error('Erreur à la suppression du brouillon', ['exception' => $e, 'bon_id' => $bon->id]);

            return response()->json(['success' => false, 'message' => 'Une erreur interne est survenue.'], 500);
        }
    }

    /**
     * PO-01 — décomposition du prix d'un article, et écart du prix saisi.
     *
     * Servi par l'écran de saisie sous la permission de saisie : la référence
     * de prix est une donnée d'achat sensible, elle ne s'ouvre pas au premier
     * lecteur venu.
     */
    public function referencePrix(Request $request, int $articleId): JsonResponse
    {
        $decomposition = $this->referencePrix->pour($articleId);

        if ($request->filled('prix')) {
            $decomposition['ecart'] = $this->referencePrix->ecart(
                $articleId,
                (float) $request->input('prix')
            );
        }

        return response()->json($decomposition);
    }

    /**
     * Détection du conflit d'édition (SPEC_UX §15.2). On compare à la seconde
     * près : `updated_at` est renvoyé au format ISO par le formulaire, et une
     * comparaison de chaînes brutes échouerait sur des formats équivalents.
     */
    private function detecterConflit(BonCommande $bon, ?string $versionClient): ?JsonResponse
    {
        if ($versionClient === null || $bon->updated_at === null) {
            return null;
        }

        if ($bon->updated_at->equalTo(\Illuminate\Support\Carbon::parse($versionClient))) {
            return null;
        }

        return response()->json([
            'success' => false,
            'conflit' => true,
            'message' => 'Ce brouillon a été modifié depuis votre ouverture ('
                .($bon->updated_at->format('H\hi')).'). Rechargez-le ou écrasez-le avec votre version.',
            'data' => ['updated_at' => $bon->updated_at->toIso8601String()],
        ], 409);
    }

    /**
     * État du brouillon après enregistrement : les montants CALCULÉS PAR LE
     * SERVEUR y sont renvoyés, pour que l'écran remplace sa prévisualisation
     * par la vérité (IA-1). Le `updated_at` sert de nouveau jeton de verrou.
     */
    private function etatBrouillon(BonCommande $bon): array
    {
        return [
            'id' => $bon->id,
            'numero_affiche' => $bon->numero_affiche,
            'updated_at' => $bon->updated_at?->toIso8601String(),
            'montant_ht' => (float) $bon->montant_ht,
            'montant_tva' => (float) $bon->montant_tva,
            'montant_ttc' => (float) $bon->montant_ttc,
            'lignes' => $bon->lignes()->get()->map(fn ($ligne) => [
                'id' => $ligne->id,
                'article_id' => $ligne->article_id,
                'designation' => $ligne->designation,
                'nature' => $ligne->nature,
                'quantite' => (float) $ligne->quantite,
                'prix_unitaire_ht' => (float) $ligne->prix_unitaire_ht,
                'taux_tva' => (float) $ligne->taux_tva,
                'montant_ht' => $this->montants->montantHtLigne($ligne),
            ])->values(),
        ];
    }

    /** Données communes des écrans de saisie (création et édition). */
    private function donneesFormulaire(?BonCommande $bon, bool $regularisation): array
    {
        return [
            'bon' => $bon,
            'estRegularisation' => $regularisation,
            'services' => Service::query()
                ->where('actif', true)
                ->orderBy('libelle')
                ->get(['id', 'libelle']),
            'motifs' => $this->parametres->motifsObservation(),
            'seuilEcartPct' => $this->parametres->seuilEcartPrixPct(),
            'intermede' => [
                'debut' => $this->parametres->intermedeDebut()?->toDateString(),
                'fin' => $this->parametres->intermedeFin()?->toDateString(),
            ],
            'lignesExistantes' => $bon === null ? [] : $this->etatBrouillon($bon)['lignes'],
        ];
    }

    /**
     * Redirection vers la fiche quand elle existe, vers la liste sinon : tant
     * que A-04 n'est pas livrée, mieux vaut la liste avec un message qu'une
     * route inexistante.
     */
    private function redirigerVersLaFiche(BonCommande $bon, ?string $message)
    {
        $cible = Route::has('achat.bons-commande.show')
            ? redirect()->route('achat.bons-commande.show', $bon->id)
            : redirect()->route('achat.bons-commande.index');

        return $cible->with('info', $message);
    }

    // ═══ Circuit BROUILLON ⇄ SOUMIS (SFD §7.1) ═══════════════════════════════

    /**
     * Étape ② — récapitulatif avant soumission. Écran en lecture seule bâti
     * sur le partiel « Récapitulatif de BC », qui resservira tel quel au visa
     * (UX2-03) : le validateur doit voir EXACTEMENT ce que l'auteur a vu.
     */
    public function recapitulatif(int $id)
    {
        $bon = BonCommande::query()
            ->with(['lignes', 'fournisseur', 'serviceDemandeur', 'createur'])
            ->findOrFail($id);

        // Le récapitulatif décrit un brouillon en partance ; une fois soumis,
        // c'est la fiche qui prend le relais.
        if (! $bon->estSoumettable()) {
            return $this->redirigerVersLaFiche($bon, 'Ce bon n\'est plus en brouillon.');
        }

        return view('achat::bons-commande.recapitulatif', [
            'bon' => $bon,
            'diagnostic' => $this->controles->diagnostiquer($bon),
            'decomposition' => $this->montants->decompositionParTaux($bon->lignes),
        ]);
    }

    /** SW-01 — soumission au visa. */
    public function soumettre(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->circuit->soumettre($bon, $request->user());
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bon soumis au visa.',
            'data' => [
                'id' => $bon->id,
                'statut' => $bon->statut,
                'redirection' => $this->urlFiche($bon),
            ],
        ]);
    }

    /** M-06 — renvoi motivé par le validateur. */
    public function renvoyer(RenvoyerBonCommandeRequest $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->circuit->renvoyer($bon, $request->user(), $request->validated()['motif']);
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bon renvoyé à '.($bon->createur?->name ?? 'son auteur').'.',
            'data' => ['id' => $bon->id, 'statut' => $bon->statut],
        ]);
    }

    /** Reprise par l'auteur : il défait sa propre soumission (SFD §7.1). */
    public function reprendre(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->circuit->reprendre($bon, $request->user());
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bon repris — il est de nouveau modifiable.',
            'data' => [
                'id' => $bon->id,
                'statut' => $bon->statut,
                'redirection' => route('achat.bons-commande.edit', $bon->id),
            ],
        ]);
    }

    /**
     * Refus métier en réponse HTTP. Le code vient de l'exception elle-même
     * (409 pour une transition impossible, 422 pour un contenu incomplet), et
     * les blocages détaillés sont transmis pour que l'écran les pose sur les
     * lignes fautives plutôt qu'en message global.
     */
    private function refus(AchatException $e): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $e->getMessage(),
            'blocages' => $e instanceof SoumissionRefuseeException ? $e->blocages : null,
        ], fn ($valeur) => $valeur !== null), $e->status());
    }

    /** URL de la fiche si elle existe, la liste sinon (A-04 non livrée). */
    private function urlFiche(BonCommande $bon): string
    {
        return Route::has('achat.bons-commande.show')
            ? route('achat.bons-commande.show', $bon->id)
            : route('achat.bons-commande.index');
    }

    // ═══ D-06 — Le visa (SFD §7.2) ═══════════════════════════════════════════

    /**
     * Signaux de SW-02, servis AVANT la confirmation : cumul fournisseur du
     * mois, écarts de prix, fournisseur récent, auto-validation. Informatifs
     * par doctrine — leur présence ne bloque jamais, c'est au validateur d'en
     * juger.
     */
    public function signaux(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        return response()->json(array_merge(
            $this->visa->signaux($bon, $request->user()),
            [
                'bon' => [
                    'id' => $bon->id,
                    'numero_affiche' => $bon->numero_affiche,
                    'nb_lignes' => $bon->lignes()->count(),
                    'montant_ttc' => (float) $bon->montant_ttc,
                    'fournisseur' => $bon->fournisseur?->raison_sociale,
                    'est_regularisation' => (bool) $bon->est_regularisation,
                ],
            ]
        ));
    }

    /** SW-02 — validation : numéro sous verrou, dénormalisations, journal. */
    public function valider(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->visa->valider($bon, $request->user());
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => "Bon {$bon->numero} validé.",
            'data' => [
                'id' => $bon->id,
                'numero' => $bon->numero,
                'statut' => $bon->statut,
                'redirection' => $this->urlFiche($bon),
            ],
        ]);
    }

    // ═══ D-08 — La fiche A-04 (SPEC_UX A-04) ═════════════════════════════════

    /**
     * L'écran le plus partagé du module : bandeau d'état, barre d'actions
     * issue de la MÊME grille serveur que la liste (ActionsBonCommande),
     * onglets Lignes / Réceptions / Documents / Chronologie.
     *
     * Un brouillon aussi a sa fiche : c'est la cible des redirections « bon
     * non modifiable » et le point d'entrée de la consultation, quel que soit
     * le statut.
     */
    public function show(Request $request, int $id)
    {
        $bon = BonCommande::query()
            ->with([
                'lignes.article',
                'fournisseur',
                'serviceDemandeur',
                'createur',
                'validateur',
                'documents.createur',
                'documents.suppresseur',
            ])
            ->findOrFail($id);

        // Natures immatérielles (licence, prestation) : elles se réceptionnent
        // hors magasin, dans l'onglet dédié (D-13).
        $lignesImmaterielles = $bon->lignes->filter(
            fn ($ligne) => $ligne->estLicence() || $ligne->estPrestation()
        )->values();

        return view('achat::bons-commande.show', [
            'bon' => $bon,
            'actions' => $this->actions->pour($bon, $request->user()),
            'decomposition' => $this->montants->decompositionParTaux($bon->lignes),
            'chronologie' => $this->chronologie->pour($bon),
            // Onglet Réceptions (D-12) : intégrées (vérité Achat) + en cours
            // côté magasin (informatif, dégradation partielle si Stock coupe).
            'receptions' => $bon->estEngage()
                ? $this->receptions->pour($bon)
                : ['integrees' => collect(), 'en_cours' => collect(), 'magasin_disponible' => true],
            // UX4-03 : le badge « fournisseur récent » du bandeau, même signal
            // que le Swal du visa — nul si le fournisseur est établi.
            'fournisseurRecent' => $bon->estEngage() ? null : $this->visa->fournisseurRecent($bon),
            // UX4-07 : l'auto-validation se lit sur la fiche après coup.
            'autoValidation' => $bon->valide_par !== null
                && $bon->created_by !== null
                && $bon->valide_par === $bon->created_by,
            // M-05 : la taille max des pièces est un paramètre A-08.
            'tailleMaxPieceMo' => $this->parametres->tailleMaxPieceMo(),
            // Onglet Licences (D-13) : lignes des natures immatérielles, avec
            // leur diagnostic d'action (grisé + raison, SPEC_UX §0.3).
            'lignesImmaterielles' => $lignesImmaterielles,
            'diagnosticsImmateriels' => $lignesImmaterielles
                ->mapWithKeys(fn ($ligne) => [$ligne->id => $this->diagnosticReception($bon, $ligne)])
                ->all(),
            // D-15 : les équipements dont ce bon documente l'origine (M-09).
            'rattachements' => $bon->est_regularisation
                ? $bon->rattachements()->with(['equipement:id,code_inventaire,numero_serie,modele', 'createur:id,name'])->orderBy('id')->get()
                : collect(),
        ]);
    }

    /**
     * Pourquoi le bouton de réception d'une ligne immatérielle est grisé —
     * ou `null` s'il est ouvert (SPEC_UX A-04, doctrine §0.3).
     */
    private function diagnosticReception(BonCommande $bon, $ligne): ?string
    {
        if (! in_array($bon->statut, BonCommande::STATUTS_RECEPTIONNABLES, true)) {
            return 'Le bon doit être validé pour réceptionner.';
        }

        if ($ligne->estSoldee()) {
            return $ligne->estPrestation()
                ? 'Service déjà constaté.'
                : 'Toutes les licences ont été reçues.';
        }

        // IA-9 : la garde du logiciel se dit ici aussi, avant même le clic.
        if ($ligne->estLicence()) {
            $article = \Modules\Catalogue\Models\Article::query()->find($ligne->article_id);

            if ($article === null || $article->logiciel_id === null) {
                return 'Article sans logiciel rattaché : corrigez la fiche au Catalogue.';
            }
        }

        return null;
    }

    // ═══ D-14 (partiel) — Fin de vie : M-07 annuler, M-03 clôturer ═══════════

    /** M-07 — annulation d'un bon validé sans réception, motif obligatoire. */
    public function annuler(MotifRequest $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->finDeVie->annuler($bon, $request->user(), $request->validated()['motif']);
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => "Bon {$bon->numero} annulé.",
            'data' => [
                'id' => $bon->id,
                'statut' => $bon->statut,
                'redirection' => $this->urlFiche($bon),
            ],
        ]);
    }

    /**
     * D-23 — dupliquer un bon en brouillon.
     *
     * Le geste sert au quotidien : le trimestre de consommables ressemble au
     * précédent. Mais les valeurs sont RE-FIGÉES au jour de la duplication
     * (extension d'IA-2) — recopier un prix de l'an dernier dans un bon qu'on
     * s'apprête à engager reviendrait à commander à un prix qui n'existe plus.
     *
     * Un bon ANNULÉ ne se duplique pas : il a été écarté pour une raison, et
     * la reproduire d'un clic reviendrait à contourner cette décision.
     */
    public function dupliquer(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->with('lignes')->findOrFail($id);

        if ($bon->statut === BonCommande::STATUT_ANNULE) {
            return response()->json([
                'success' => false,
                'message' => 'Un bon annulé ne se duplique pas : il a été écarté pour une raison.',
            ], 409);
        }

        if ($bon->lignes->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Ce bon n\'a aucune ligne à dupliquer.',
            ], 422);
        }

        $resultat = $this->duplication->dupliquer($bon, $request->user());

        return response()->json([
            'success' => true,
            'message' => sprintf(
                'Brouillon créé depuis %s — les prix ont été actualisés au jour de la duplication.',
                $bon->numero_affiche
            ),
            'data' => [
                'id' => $resultat['bon']->id,
                'redirection' => route('achat.bons-commande.edit', $resultat['bon']->id),
                // L'écran affiche ces deux listes : ce qui demande vérification,
                // et ce que l'acheteur avait négocié la dernière fois.
                'avertissements' => $resultat['avertissements'],
                'comparaisons' => $resultat['comparaisons'],
            ],
        ]);
    }

    /** M-03 — clôture du reliquat d'un bon partiellement livré. */
    public function cloturer(MotifRequest $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        try {
            $bon = $this->finDeVie->cloturer($bon, $request->user(), $request->validated()['motif']);
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => "Reliquat du bon {$bon->numero} clôturé.",
            'data' => [
                'id' => $bon->id,
                'statut' => $bon->statut,
                'redirection' => $this->urlFiche($bon),
            ],
        ]);
    }
}
