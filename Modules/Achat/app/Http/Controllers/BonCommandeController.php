<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\ActionsBonCommande;
use Modules\Achat\Services\RechercheBonCommande;
use Modules\Catalogue\Models\Fournisseur;

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
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.bons_commande.index', only: ['index', 'getData']),
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

    /** Une ligne du tableau, telle que la vue l'attend. */
    private function ligne(BonCommande $bon, $utilisateur): array
    {
        $commande = (float) ($bon->total_commande ?? 0);
        $livre = (float) ($bon->total_livre ?? 0);

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
            'actions' => $this->actions->pour($bon, $utilisateur),
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
}
