<?php

namespace Modules\ParcInfo\Http\Controllers\Analyse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Imprimante;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Mobile;
use Modules\ParcInfo\Models\Ordinateur;
use Modules\ParcInfo\Models\Serveur;

class StatistiquesController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.analyse.statistiques.view', only: ['index', 'getData']),
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
        // 1. Counts by Type
        $totalEquipments = Equipement::count();
        $ordinateursCount = Ordinateur::count();
        $serveursCount = Serveur::count();
        $mobilesCount = Mobile::count();
        $imprimantesCount = Imprimante::count();
        $autresCount = $totalEquipments - ($ordinateursCount + $serveursCount + $mobilesCount + $imprimantesCount);

        $typesStats = [
            ['label' => 'Ordinateurs', 'count' => $ordinateursCount, 'percentage' => $totalEquipments > 0 ? round(($ordinateursCount / $totalEquipments) * 100, 1) : 0],
            ['label' => 'Serveurs', 'count' => $serveursCount, 'percentage' => $totalEquipments > 0 ? round(($serveursCount / $totalEquipments) * 100, 1) : 0],
            ['label' => 'Terminaux Mobiles', 'count' => $mobilesCount, 'percentage' => $totalEquipments > 0 ? round(($mobilesCount / $totalEquipments) * 100, 1) : 0],
            ['label' => 'Imprimantes', 'count' => $imprimantesCount, 'percentage' => $totalEquipments > 0 ? round(($imprimantesCount / $totalEquipments) * 100, 1) : 0],
            ['label' => 'Autres Équipements', 'count' => max(0, $autresCount), 'percentage' => $totalEquipments > 0 ? round((max(0, $autresCount) / $totalEquipments) * 100, 1) : 0],
        ];

        // 2. Counts by Status
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

        // Fill missing statuses with 0
        $allStatuses = array_keys($statutLabels);
        $existingStatuses = array_column($statusStats, 'statut');
        foreach ($allStatuses as $st) {
            if (! in_array($st, $existingStatuses)) {
                $statusStats[] = [
                    'statut' => $st,
                    'label' => $statutLabels[$st],
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
        }

        // Sort statusStats to keep a consistent order
        usort($statusStats, function ($a, $b) use ($allStatuses) {
            return array_search($a['statut'], $allStatuses) - array_search($b['statut'], $allStatuses);
        });

        // 3. Counts by State
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

        // Fill missing states with 0
        $allStates = array_keys($etatLabels);
        $existingStates = array_column($stateStats, 'etat');
        foreach ($allStates as $et) {
            if (! in_array($et, $existingStates)) {
                $stateStats[] = [
                    'etat' => $et,
                    'label' => $etatLabels[$et],
                    'count' => 0,
                    'percentage' => 0,
                ];
            }
        }

        // Sort stateStats
        usort($stateStats, function ($a, $b) use ($allStates) {
            return array_search($a['etat'], $allStates) - array_search($b['etat'], $allStates);
        });

        // 4. Software & Licenses Stats
        $logicielsCount = Logiciel::count();
        $licencesCount = Licence::count();
        $activeLicencesCount = Licence::where('actif', true)->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'total_equipements' => $totalEquipments,
                'total_logiciels' => $logicielsCount,
                'total_licences' => $licencesCount,
                'licences_actives' => $activeLicencesCount,
            ],
            'types_stats' => $typesStats,
            'status_stats' => $statusStats,
            'state_stats' => $stateStats,
        ]);
    }
}
