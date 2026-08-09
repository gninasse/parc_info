<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
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

    /** Z4 — le fil d'activité (SPEC_UX A-01). */
    private const MAX_EVENEMENTS = 10;

    /**
     * Les événements qui MÉRITENT le fil : ceux qui engagent ou soldent.
     * Un dépôt de pièce ou une ouverture de wizard n'apprend rien à qui
     * survole son tableau de bord le matin — la fiche les raconte.
     */
    private const EVENEMENTS_NOTABLES = [
        'soumission',
        'renvoi_en_brouillon',
        'validation',
        'annulation',
        'cloture_reliquat',
        'reception_licences',
        'service_fait',
        'rattachement_regularisation',
    ];

    private const ICONES_EVENEMENTS = [
        'soumission' => 'bi-send',
        'renvoi_en_brouillon' => 'bi-arrow-return-left',
        'validation' => 'bi-check-lg',
        'annulation' => 'bi-x-octagon',
        'cloture_reliquat' => 'bi-lock',
        'reception_licences' => 'bi-key',
        'service_fait' => 'bi-clipboard-check',
        'rattachement_regularisation' => 'bi-link-45deg',
    ];

    private const COULEURS_EVENEMENTS = [
        'soumission' => 'warning',
        'renvoi_en_brouillon' => 'danger',
        'validation' => 'primary',
        'annulation' => 'danger',
        'cloture_reliquat' => 'dark',
        'reception_licences' => 'success',
        'service_fait' => 'success',
        'rattachement_regularisation' => 'warning',
    ];

    public function __construct(
        private readonly AchatParametres $parametres,
        private readonly \Modules\Achat\Services\StatistiquesAchatService $statistiques,
        private readonly \Modules\Achat\Services\RegularisationService $regularisation,
    ) {}

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
            // Z2 — le graphique 12 mois vient du service PARTAGÉ (D-16) :
            // c'est le même que la carte de rapport, au chiffre près.
            'evolution' => $this->statistiques->evolutionDouzeMois(),
            // Z4 — le fil des 10 derniers événements, issu du journal réel.
            'evenements' => $this->derniersEvenements(),
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
            // Lecture croisée du Stock : informative, et DÉGRADABLE — si le
            // module est coupé, la carte s'efface au lieu de casser la page.
            'en_cours_reception' => $this->enCoursDeReception(),
            'dette_interim' => $this->detteInterim(),
        ];
    }

    /**
     * Bons d'entrée LIÉS à une commande, non encore validés : ce que le
     * magasin est en train de saisir.
     *
     * `null` (et non zéro) quand la lecture échoue : zéro dirait « rien en
     * cours », ce qui est un mensonge — la carte se masque à la place.
     */
    private function enCoursDeReception(): ?int
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('stock_entrees')) {
                return null;
            }

            return DB::table('stock_entrees')
                ->whereNotNull('bon_commande_id')
                ->whereNotIn('statut', ['VALIDE', 'ANNULE'])
                ->count();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Lecture des entrées Stock impossible pour le tableau de bord', [
                'exception' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Montant HT engagé sur le mois courant.
     *
     * Délégué au service PARTAGÉ (D-16) : le tableau de bord, la carte de
     * rapport et l'export doivent afficher le même chiffre — même source,
     * même arrondi, même périmètre. Les régularisations en sont exclues :
     * elles documentent le passé et fausseraient la dépense du mois.
     */
    private function engageDuMois(): float
    {
        return $this->statistiques->engageDuMois()['ht'];
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
     *
     * Déléguée au service de régularisation (D-15), qui déduit aussi les
     * équipements tracés par la chaîne Stock — sinon le tableau de bord
     * annoncerait une dette que l'écran M-09 ne montre pas.
     */
    private function detteInterim(): int
    {
        return $this->regularisation->detteRestante();
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

    /**
     * Z4 — les 10 derniers événements du module, lus DANS LE JOURNAL.
     *
     * Même doctrine que la chronologie de la fiche (IA-14) : rien n'est
     * reconstruit depuis les colonnes. On réutilise le présentateur de
     * ChronologieBonCommande pour que la même action se raconte avec les
     * mêmes mots sur les deux écrans.
     */
    private function derniersEvenements(): Collection
    {
        $traces = \Modules\Core\Models\Activity::query()
            ->forModule('achat')
            ->where('subject_type', BonCommande::class)
            ->whereIn('description', self::EVENEMENTS_NOTABLES)
            ->with('causer:id,name')
            ->latest('created_at')
            ->latest('id')
            ->limit(self::MAX_EVENEMENTS)
            ->get();

        $bons = BonCommande::query()
            ->whereIn('id', $traces->pluck('subject_id')->filter()->unique())
            ->get(['id', 'numero'])
            ->keyBy('id');

        return $traces->map(function ($trace) use ($bons) {
            $bon = $bons->get($trace->subject_id);

            return [
                'phrase' => $this->phraseEvenement($trace, $bon),
                'icone' => self::ICONES_EVENEMENTS[$trace->description] ?? 'bi-dot',
                'couleur' => self::COULEURS_EVENEMENTS[$trace->description] ?? 'secondary',
                'auteur' => $trace->causer?->name,
                'quand' => $trace->created_at,
                'url' => $bon !== null && \Illuminate\Support\Facades\Route::has('achat.bons-commande.show')
                    ? route('achat.bons-commande.show', $bon->id)
                    : null,
            ];
        });
    }

    /** Phrase au passé, avec le numéro du bon quand il en porte un. */
    private function phraseEvenement($trace, ?BonCommande $bon): string
    {
        $document = $bon?->numero ?? 'Un bon';
        $props = $trace->properties ?? collect();

        return match ($trace->description) {
            \Modules\Achat\Services\CircuitSoumissionService::EVENEMENT_SOUMISSION => "{$document} soumis au visa",
            \Modules\Achat\Services\CircuitSoumissionService::EVENEMENT_RENVOI => "{$document} renvoyé en brouillon",
            \Modules\Achat\Services\VisaService::EVENEMENT_VALIDATION => sprintf(
                '%s validé', $props->get('numero') ?? $document
            ),
            \Modules\Achat\Services\FinDeVieService::EVENEMENT_ANNULATION => "{$document} annulé",
            \Modules\Achat\Services\FinDeVieService::EVENEMENT_CLOTURE => "Reliquat de {$document} clôturé",
            \Modules\Achat\Services\ReceptionLicencesService::EVENEMENT_FINALISATION => sprintf(
                '%s licence(s) reçue(s) sur %s', $props->get('licences_creees') ?? '', $document
            ),
            \Modules\Achat\Services\ReceptionLicencesService::EVENEMENT_SERVICE_FAIT => "Service fait constaté sur {$document}",
            \Modules\Achat\Services\RegularisationService::EVENEMENT_RATTACHEMENT => sprintf(
                '%s équipement(s) rattaché(s) à %s', $props->get('nombre') ?? '', $document
            ),
            default => "{$document} — activité",
        };
    }
}
