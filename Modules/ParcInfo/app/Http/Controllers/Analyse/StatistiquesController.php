<?php

namespace Modules\ParcInfo\Http\Controllers\Analyse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\ContratMaintenance;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\MouvementConsommable;

class StatistiquesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parcinfo.analyse.view', only: ['index', 'getData']),
        ];
    }

    /**
     * Display the index page.
     */
    public function index()
    {
        return view('parcinfo::analyse.statistiques.index');
    }

    /**
     * Get aggregate statistics.
     */
    public function getData(Request $request)
    {
        // ── 1. VOLUMÉTRIE GLOBALE ──
        $totalEquipments = Equipement::count();
        $totalValPurchase = Equipement::sum('valeur_achat');

        // Types Counts (using dynamic categories)
        $typesStats = [
            ['label' => 'Ordinateurs', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'ordinateur'))->count()],
            ['label' => 'Serveurs Physiques', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'serveur'))->count()],
            ['label' => 'Serveurs Virtuels', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'serveur-virtuel'))->count()],
            ['label' => 'Imprimantes', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'imprimante'))->count()],
            ['label' => 'Scanners', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'scanner'))->count()],
            ['label' => 'Équipements Réseau', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'reseau'))->count()],
            ['label' => 'Téléphones IP', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'telephone'))->count()],
            ['label' => 'Mobiles', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'mobile'))->count()],
            ['label' => 'Caméras IP', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'camera'))->count()],
            ['label' => 'Infrastructures', 'count' => Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'infrastructure'))->count()],
        ];

        // Format Types Stats with percentages
        foreach ($typesStats as &$t) {
            $t['percentage'] = $totalEquipments > 0 ? round(($t['count'] / $totalEquipments) * 100, 1) : 0;
        }

        // Status Counts
        $statusCounts = Equipement::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get();

        $statutLabels = [
            'en_stock' => 'En stock',
            'en_service' => 'En service',
            'en_reparation' => 'En réparation',
            'perdu' => 'Perdu',
            'reforme' => 'Réformé',
        ];

        $statusStats = [];
        foreach ($statusCounts as $sc) {
            $statusStats[] = [
                'statut' => $sc->statut,
                'label' => $statutLabels[$sc->statut] ?? $sc->statut,
                'count' => $sc->total,
                'percentage' => $totalEquipments > 0 ? round(($sc->total / $totalEquipments) * 100, 1) : 0,
            ];
        }

        // Fill missing statuses
        foreach (array_keys($statutLabels) as $st) {
            if (! in_array($st, array_column($statusStats, 'statut'))) {
                $statusStats[] = ['statut' => $st, 'label' => $statutLabels[$st], 'count' => 0, 'percentage' => 0];
            }
        }

        // State Counts
        $stateCounts = Equipement::select('etat', DB::raw('count(*) as total'))
            ->groupBy('etat')
            ->get();

        $etatLabels = [
            'bon' => 'Bon',
            'passable' => 'Passable',
            'mauvais' => 'Mauvais',
            'avarie' => 'Avarié',
        ];

        $stateStats = [];
        foreach ($stateCounts as $stc) {
            $stateStats[] = [
                'etat' => $stc->etat,
                'label' => $etatLabels[$stc->etat] ?? $stc->etat,
                'count' => $stc->total,
                'percentage' => $totalEquipments > 0 ? round(($stc->total / $totalEquipments) * 100, 1) : 0,
            ];
        }

        // Fill missing states
        foreach (array_keys($etatLabels) as $et) {
            if (! in_array($et, array_column($stateStats, 'etat'))) {
                $stateStats[] = ['etat' => $et, 'label' => $etatLabels[$et], 'count' => 0, 'percentage' => 0];
            }
        }

        // Brand Counts
        $brandCounts = Equipement::select('marque_id', DB::raw('count(*) as total'))
            ->groupBy('marque_id')
            ->with('marque')
            ->get()
            ->map(function ($b) {
                return [
                    'label' => $b->marque ? $b->marque->libelle : 'Sans Marque',
                    'count' => $b->total,
                ];
            })->sortByDesc('count')->values()->take(5)->toArray();

        // ── 2. CALCULS DE VALEUR RÉSIDUELLE & VÉTUSTÉ ──
        $allEquipments = Equipement::get();
        $totalResidualVal = 0;
        $vetusteCount = 0;

        foreach ($allEquipments as $eq) {
            // Residual Value calculation (linear depreciation)
            $totalResidualVal += $this->calculateResidualValue($eq);

            // Vetuste rate
            if ($eq->date_mise_en_service && $eq->duree_vie_probable) {
                $finVie = $eq->date_mise_en_service->copy()->addYears($eq->duree_vie_probable);
                if ($finVie->isPast()) {
                    $vetusteCount++;
                }
            }
        }

        $vetusteRate = $totalEquipments > 0 ? round(($vetusteCount / $totalEquipments) * 100, 1) : 0;
        $inServiceCount = Equipement::where('statut', 'en_service')->count();
        $availabilityRate = $totalEquipments > 0 ? round(($inServiceCount / $totalEquipments) * 100, 1) : 0;

        // ── 3. PARC PAR ORGANISATION ──
        $directionStats = Direction::all()->map(function ($dir) {
            $eqs = Equipement::where('direction_id', $dir->id)->get();
            $val = $eqs->sum('valeur_achat');
            $resVal = 0;
            foreach ($eqs as $e) {
                $resVal += $this->calculateResidualValue($e);
            }

            return [
                'id' => $dir->id,
                'label' => $dir->libelle,
                'count' => $eqs->count(),
                'value' => $val,
                'residual_value' => $resVal,
            ];
        })->sortByDesc('count')->values();

        $serviceStats = Service::all()->map(function ($srv) {
            $eqs = Equipement::where('service_id', $srv->id)->get();
            $val = $eqs->sum('valeur_achat');
            $resVal = 0;
            foreach ($eqs as $e) {
                $resVal += $this->calculateResidualValue($e);
            }

            // Coverage rate: equipments assigned vs active workstations
            $workstationsCount = PosteTravail::where('service_id', $srv->id)->count();
            $coverageRate = $workstationsCount > 0 ? round(($eqs->count() / $workstationsCount) * 100, 1) : 100.0;

            return [
                'id' => $srv->id,
                'label' => $srv->libelle,
                'count' => $eqs->count(),
                'value' => $val,
                'residual_value' => $resVal,
                'coverage_rate' => $coverageRate,
            ];
        })->sortByDesc('count')->values();

        // Worst directions (most in mauvais or avarie state)
        $worstDirections = Direction::all()->map(function ($dir) {
            $badCount = Equipement::where('direction_id', $dir->id)
                ->whereIn('etat', ['mauvais', 'avarie'])
                ->count();

            return [
                'label' => $dir->libelle,
                'bad_count' => $badCount,
            ];
        })->sortByDesc('bad_count')->values()->take(5)->toArray();

        // ── 4. AFFECTATIONS ──
        $activeAssignments = AffectationEquipement::where('statut', true)->count();
        $endedAssignments = AffectationEquipement::where('statut', false)->count();

        $avgDurationPerm = round(AffectationEquipement::where('type_affectation', 'PERMANENTE')
            ->whereNotNull('date_fin')
            ->get()
            ->avg(function ($aff) {
                return $aff->date_debut->diffInDays($aff->date_fin);
            }) ?? 0);

        $avgDurationTemp = round(AffectationEquipement::where('type_affectation', 'TEMPORAIRE')
            ->whereNotNull('date_fin')
            ->get()
            ->avg(function ($aff) {
                return $aff->date_debut->diffInDays($aff->date_fin);
            }) ?? 0);

        // Most reassigned equipments
        $mostReassigned = AffectationEquipement::select('equipement_id', DB::raw('count(*) as count'))
            ->groupBy('equipement_id')
            ->orderByDesc('count')
            ->with('equipement')
            ->take(5)
            ->get()
            ->map(function ($aff) {
                return [
                    'label' => $aff->equipement ? $aff->equipement->code_inventaire : 'N/A',
                    'count' => $aff->count,
                ];
            })->toArray();

        // ── 5. LICENCES & CONFORMITÉ ──
        $logicielsCount = Logiciel::count();
        $licencesCount = Licence::count();
        $activeLicencesCount = Licence::where('actif', true)->count();
        $totalLicenseCost = Licence::where('actif', true)->sum('cout_total');

        $totalAllowedSeats = Licence::where('actif', true)->sum('nombre_postes_accordes');
        $totalUsedSeats = Licence::where('actif', true)->sum('nombre_postes_utilises');
        $globalLicenseUsage = $totalAllowedSeats > 0 ? round(($totalUsedSeats / $totalAllowedSeats) * 100, 1) : 0;

        $licenseModelStats = Licence::select('modele_licencing', DB::raw('count(*) as total'))
            ->groupBy('modele_licencing')
            ->get()
            ->map(function ($m) {
                return [
                    'label' => $m->modele_licencing ?? 'Non spécifié',
                    'count' => $m->total,
                ];
            })->toArray();

        $licenseStatusStats = Licence::select('statut', DB::raw('count(*) as total'))
            ->groupBy('statut')
            ->get()
            ->map(function ($s) {
                $labels = [
                    'actif' => 'Actif',
                    'expire' => 'Expiré',
                    'renouvellement' => 'En renouvellement',
                    'suspendu' => 'Suspendu',
                ];

                return [
                    'label' => $labels[$s->statut] ?? $s->statut,
                    'count' => $s->total,
                ];
            })->toArray();

        // Software by editor
        $softwareByEditor = Logiciel::select('editeur_id', DB::raw('count(*) as total'))
            ->groupBy('editeur_id')
            ->with('editeur')
            ->get()
            ->map(function ($e) {
                return [
                    'label' => $e->editeur ? $e->editeur->nom : 'Inconnu',
                    'count' => $e->total,
                ];
            })->sortByDesc('count')->values()->take(5)->toArray();

        // ── 6. CONSOMMABLES ──
        // EF-STK-05 — quantités et valorisation lues auprès du module Stock.
        $stock = app(\Modules\ParcInfo\Contracts\StockIntegrationInterface::class);
        $articleIds = Consommable::where('est_actif', true)->whereNotNull('article_id')->pluck('article_id')->all();
        $quantitesStock = $stock->quantitesParArticles($articleIds);
        $consStockVal = array_sum($stock->valorisationParArticles($articleIds));
        $refsInRupture = collect($articleIds)->filter(fn ($id) => ($quantitesStock[$id] ?? 0) === 0)->count();

        // Consumables cost by service (last 1 year)
        $consumablesByService = MouvementConsommable::where('type_mouvement', 'SORTIE')
            ->whereNotNull('service_id')
            ->select('service_id', DB::raw('sum(quantite * prix_unitaire) as cost'))
            ->groupBy('service_id')
            ->with('service')
            ->get()
            ->map(function ($c) {
                return [
                    'label' => $c->service ? $c->service->libelle : 'Inconnu',
                    'value' => (float) $c->cost,
                ];
            })->sortByDesc('value')->values()->take(5)->toArray();

        // Total purchase and consumption
        $totalConsumablePurchases = MouvementConsommable::where('type_mouvement', 'ENTREE')->sum(DB::raw('quantite * prix_unitaire'));

        // ── 7. MAINTENANCE & INCIDENTS ──
        $stateChangesCount = HistoriqueChangement::count();
        $repairsCount = HistoriqueChangement::where('nouveau_statut', 'en_reparation')->count();

        // Average repair duration (days)
        $avgRepairDays = 0;
        $repairs = HistoriqueChangement::where('nouveau_statut', 'en_reparation')->get();
        $repairDurations = [];
        foreach ($repairs as $rep) {
            $next = HistoriqueChangement::where('equipement_id', $rep->equipement_id)
                ->where('date_changement', '>', $rep->date_changement)
                ->where('nouveau_statut', 'en_service')
                ->orderBy('date_changement')
                ->first();
            if ($next) {
                $repairDurations[] = $rep->date_changement->diffInDays($next->date_changement);
            }
        }
        if (count($repairDurations) > 0) {
            $avgRepairDays = round(array_sum($repairDurations) / count($repairDurations), 1);
        }

        // Recurrence count (in repair > 1 time)
        $recurrences = DB::table('parc_info_historique_changements')
            ->where('nouveau_statut', 'en_reparation')
            ->select('equipement_id', DB::raw('count(*) as count'))
            ->groupBy('equipement_id')
            ->havingRaw('count(*) > 1')
            ->count();

        // Reforms count and residual value
        $reformsQuery = Equipement::where('statut', 'reforme')->get();
        $reformedCount = $reformsQuery->count();
        $reformedResidualVal = 0;
        foreach ($reformsQuery as $refEq) {
            $reformedResidualVal += $this->calculateResidualValue($refEq);
        }

        // ── 8. FINANCES & BUDGET ──
        $contractsCost = ContratMaintenance::where('est_actif', true)->sum('cout');
        $consumablePeriodCost = MouvementConsommable::where('type_mouvement', 'SORTIE')->sum(DB::raw('quantite * prix_unitaire'));

        // Estimated IT Budget (Contracts + Active Licenses + Consumables consumed)
        $estimatedBudget = $contractsCost + $totalLicenseCost + $consumablePeriodCost;

        // Average equipment per employee
        $activeEmployeesCount = Employe::where('est_actif', true)->count();
        $avgEquipPerEmployee = $activeEmployeesCount > 0 ? round($inServiceCount / $activeEmployeesCount, 1) : 0;

        return response()->json([
            'success' => true,
            'summary' => [
                'total_equipements' => $totalEquipments,
                'total_val_purchase' => $totalValPurchase,
                'total_residual_value' => $totalResidualVal,
                'vetuste_rate' => $vetusteRate,
                'availability_rate' => $availabilityRate,
                'avg_equip_per_employee' => $avgEquipPerEmployee,
                'estimated_budget' => $estimatedBudget,
            ],
            'types_stats' => $typesStats,
            'status_stats' => $statusStats,
            'state_stats' => $stateStats,
            'brand_stats' => $brandCounts,
            'worst_directions' => $worstDirections,
            'assignments' => [
                'active' => $activeAssignments,
                'ended' => $endedAssignments,
                'rate' => $totalEquipments > 0 ? round(($activeAssignments / $totalEquipments) * 100, 1) : 0,
                'avg_duration_perm' => $avgDurationPerm,
                'avg_duration_temp' => $avgDurationTemp,
                'most_reassigned' => $mostReassigned,
            ],
            'compliance' => [
                'logiciels_count' => $logicielsCount,
                'licences_count' => $licencesCount,
                'global_usage' => $globalLicenseUsage,
                'total_license_cost' => $totalLicenseCost,
                'models' => $licenseModelStats,
                'statuses' => $licenseStatusStats,
                'editors' => $softwareByEditor,
            ],
            'consumables' => [
                'stock_value' => $consStockVal,
                'refs_rupture' => $refsInRupture,
                'by_service' => $consumablesByService,
                'purchases' => $totalConsumablePurchases,
            ],
            'maintenance' => [
                'state_changes' => $stateChangesCount,
                'repairs' => $repairsCount,
                'avg_days' => $avgRepairDays,
                'recurrences' => $recurrences,
                'reformed_count' => $reformedCount,
                'reformed_val' => $reformedResidualVal,
            ],
            'finances' => [
                'contracts_cost' => $contractsCost,
                'consumables_cost' => $consumablePeriodCost,
                'licenses_cost' => $totalLicenseCost,
            ],
            'directions_list' => $directionStats->take(5)->values()->toArray(),
            'services_list' => $serviceStats->take(5)->values()->toArray(),
        ]);
    }

    /**
     * Helper to calculate residual value.
     */
    protected function calculateResidualValue($equipment): float
    {
        $valeurAchat = (float) $equipment->valeur_achat;
        if ($valeurAchat <= 0) {
            return 0.0;
        }
        if (! $equipment->date_mise_en_service || ! $equipment->duree_vie_probable || $equipment->duree_vie_probable <= 0) {
            return $valeurAchat;
        }

        // Calculate years elapsed since commission date
        $yearsElapsed = $equipment->date_mise_en_service->diffInDays(now()) / 365.25;

        // Depreciation factor
        $factor = max(0.0, 1.0 - ($yearsElapsed / $equipment->duree_vie_probable));

        return round($valeurAchat * $factor, 2);
    }
}
