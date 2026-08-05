<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\RegularisationRattachement;
use Modules\Achat\Services\AchatParametres;

/**
 * A-01 — Tableau de bord du module (SPEC_UX A-01).
 *
 * Doctrine : chaque chiffre est cliquable vers la liste filtrée qui le
 * justifie ; un KPI non justifiable est retiré (UX-03). Les cartes dont la
 * cible n'est pas encore développée n'affichent pas de lien mort.
 */
class DashboardController extends Controller implements HasMiddleware
{
    /** Nombre de lignes du tableau « reliquats les plus anciens » (Z3). */
    private const MAX_RELIQUATS = 5;

    public function __construct(private readonly AchatParametres $parametres) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.dashboard.view', only: ['index']),
        ];
    }

    public function index()
    {
        return view('achat::dashboard.index', [
            'kpis' => $this->kpis(),
            'delaiAlerteReliquat' => $this->parametres->delaiAlerteReliquatJours(),
            'reliquatsAnciens' => $this->reliquatsLesPlusAnciens(),
            'regularisationActive' => $this->parametres->regularisationActive(),
        ]);
    }

    /**
     * Z1 — les 6 cartes KPI (SPEC_UX A-01). « À valider » n'est calculée que
     * pour qui détient le visa : la carte est masquée aux autres, elle ne
     * leur apprendrait rien d'actionnable.
     */
    private function kpis(): array
    {
        $peutValider = auth()->user()?->can('achat.bons_commande.valider') ?? false;

        return [
            'a_valider' => $peutValider ? BonCommande::query()->aValider()->count() : null,
            'engage_du_mois' => $this->engageDuMois(),
            'bons_ouverts' => BonCommande::query()
                ->whereIn('statut', [BonCommande::STATUT_VALIDE, BonCommande::STATUT_PARTIEL])
                ->count(),
            'reliquats_anciens' => $this->compterReliquatsAnciens(),
            'dette_interim' => $this->detteInterim(),
        ];
    }

    /**
     * Montant HT engagé sur le mois courant. Les régularisations en sont
     * exclues : elles documentent le passé et fausseraient la dépense du mois
     * (SFD §7.6).
     */
    private function engageDuMois(): float
    {
        return (float) BonCommande::query()
            ->engages()
            ->horsRegularisation()
            ->whereBetween('date_document', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('montant_ht');
    }

    /** Lignes dont le reste dort depuis plus de N jours (N = paramètre A-08). */
    private function compterReliquatsAnciens(): int
    {
        $limite = now()->subDays($this->parametres->delaiAlerteReliquatJours());

        return $this->requeteReliquats()
            ->where('achat_bons_commande.valide_le', '<', $limite)
            ->count();
    }

    /**
     * Équipements du parc sans commande d'origine — la dette d'intérim
     * (A15). La carte disparaît à zéro : l'objectif est de la faire
     * disparaître, pas de l'afficher éternellement (UX2-12).
     */
    private function detteInterim(): int
    {
        return DB::table('parc_info_equipements')
            ->whereNotIn(
                'parc_info_equipements.id',
                RegularisationRattachement::query()->select('equipement_id')
            )
            ->count();
    }

    /** Z3 — les 5 reliquats les plus anciens, avec leur âge. */
    private function reliquatsLesPlusAnciens(): Collection
    {
        return $this->requeteReliquats()
            ->join('catalogue_fournisseurs', 'catalogue_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
            ->orderBy('achat_bons_commande.valide_le')
            ->limit(self::MAX_RELIQUATS)
            ->get([
                'achat_lignes_commande.id',
                'achat_lignes_commande.bon_commande_id',
                'achat_lignes_commande.designation',
                'achat_lignes_commande.quantite',
                'achat_lignes_commande.quantite_livree',
                'achat_bons_commande.numero',
                'achat_bons_commande.valide_le',
                'catalogue_fournisseurs.raison_sociale as fournisseur',
            ])
            ->map(fn ($ligne) => [
                'id' => $ligne->id,
                'bon_commande_id' => $ligne->bon_commande_id,
                'numero' => $ligne->numero,
                'designation' => $ligne->designation,
                'fournisseur' => $ligne->fournisseur,
                'reste' => round((float) $ligne->quantite - (float) $ligne->quantite_livree, 2),
                'age_jours' => $ligne->valide_le
                    ? (int) \Illuminate\Support\Carbon::parse($ligne->valide_le)->diffInDays(now())
                    : 0,
            ]);
    }

    /**
     * Base commune des reliquats : lignes non soldées de bons encore ouverts.
     * Un bon clôturé n'a plus de reliquat — son reste a été abandonné
     * volontairement (SFD §7.5).
     */
    private function requeteReliquats()
    {
        return DB::table('achat_lignes_commande')
            ->join('achat_bons_commande', 'achat_bons_commande.id', '=', 'achat_lignes_commande.bon_commande_id')
            ->whereIn('achat_bons_commande.statut', BonCommande::STATUTS_RECEPTIONNABLES)
            ->whereColumn('achat_lignes_commande.quantite_livree', '<', 'achat_lignes_commande.quantite');
    }
}
