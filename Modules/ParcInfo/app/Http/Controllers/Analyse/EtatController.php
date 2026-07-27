<?php

namespace Modules\ParcInfo\Http\Controllers\Analyse;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;
use Modules\ParcInfo\Models\AffectationConsommable;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\AffectationLicence;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\ContratMaintenance;
use Modules\ParcInfo\Models\DocumentLicence;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\MouvementConsommable;
use Rap2hpoutre\FastExcel\FastExcel;

class EtatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parcinfo.analyse.view', only: ['index', 'getData', 'export']),
        ];
    }

    /**
     * Display the listing view.
     */
    public function index()
    {
        $directions = Direction::orderBy('libelle')->get();
        $services = Service::orderBy('libelle')->get();
        $unites = Unite::orderBy('libelle')->get();
        $employes = Employe::orderBy('nom')->get();

        return view('parcinfo::analyse.etats.index', compact('directions', 'services', 'unites', 'employes'));
    }

    /**
     * Get data for the Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $reportType = $request->get('report_type', 'global_park');
        $report = $this->getReportData($reportType, $request);

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $allRows = $report['rows'];
        $total = count($allRows);

        $slicedRows = array_slice($allRows, $offset, $limit);

        return response()->json([
            'total' => $total,
            'rows' => $slicedRows,
            'columns' => $report['columns'],
            'title' => $report['title'],
        ]);
    }

    /**
     * Export report data (CSV, Excel, PDF).
     */
    public function export(Request $request)
    {
        $reportType = $request->get('report_type', 'global_park');
        $format = $request->get('format', 'csv');
        $report = $this->getReportData($reportType, $request);

        $title = $report['title'];
        $columns = $report['columns'];
        $rows = $report['rows'];

        $filters = [];
        if ($request->filled('direction_id') && $dir = Direction::find($request->direction_id)) {
            $filters['Direction'] = $dir->libelle;
        }
        if ($request->filled('service_id') && $srv = Service::find($request->service_id)) {
            $filters['Service'] = $srv->libelle;
        }
        if ($request->filled('statut')) {
            $filters['Statut'] = $request->statut;
        }
        if ($request->filled('etat')) {
            $filters['État'] = $request->etat;
        }

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('parcinfo::analyse.etats.pdf', compact('title', 'columns', 'rows', 'filters'));
            $pdf->setPaper('a4', 'landscape');

            return $pdf->stream(str_replace(' ', '_', strtolower($title)).'.pdf');
        }

        // Export to CSV or Excel
        $exportData = [];
        foreach ($rows as $row) {
            $exportRow = [];
            foreach ($columns as $key => $label) {
                $val = data_get($row, $key);

                // Format specific columns for the export file
                if (in_array($key, ['statut', 'etat'])) {
                    $labels = [
                        'en_stock' => 'En stock',
                        'en_service' => 'En service',
                        'en_reparation' => 'En réparation',
                        'perdu' => 'Perdu',
                        'reforme' => 'Réformé',
                        'bon' => 'Bon',
                        'passable' => 'Passable',
                        'mauvais' => 'Mauvais',
                        'avarie' => 'Avarié',
                    ];
                    $exportRow[$label] = $labels[$val] ?? $val;
                } elseif (in_array($key, ['valeur_achat', 'cout', 'cout_unitaire', 'cout_total', 'valeur_totale'])) {
                    $exportRow[$label] = $val ? (float) $val : 0.0;
                } elseif ($val instanceof \Carbon\Carbon || (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val))) {
                    $exportRow[$label] = \Carbon\Carbon::parse($val)->format('d/m/Y');
                } else {
                    $exportRow[$label] = $val ?? '';
                }
            }
            $exportData[] = $exportRow;
        }

        $filename = str_replace(' ', '_', strtolower($title)).'_'.date('YmdHis');

        if ($format === 'excel') {
            return (new FastExcel($exportData))->download($filename.'.xlsx');
        }

        return (new FastExcel($exportData))->download($filename.'.csv');
    }

    /**
     * Build report queries and structures.
     */
    protected function getReportData(string $reportType, Request $request): array
    {
        $title = 'Rapport du Parc';
        $columns = [];
        $rows = [];

        // Base Equipment Columns
        $eqColumns = [
            'code_inventaire' => 'Code Inventaire',
            'numero_serie' => 'N° Série',
            'marque_libelle' => 'Marque',
            'modele' => 'Modèle',
            'statut' => 'Statut',
            'etat' => 'État Physique',
            'date_acquisition' => 'Date Acquisition',
            'valeur_achat' => 'Valeur Achat',
            'duree_vie_probable' => 'Durée Vie (ans)',
            'date_fin_garantie' => 'Fin Garantie',
            'direction_libelle' => 'Direction',
            'service_libelle' => 'Service',
        ];

        // Apply base equipment filters
        $applyEqFilters = function ($query) use ($request) {
            if ($request->filled('direction_id')) {
                $query->where('direction_id', $request->direction_id);
            }
            if ($request->filled('service_id')) {
                $query->where('service_id', $request->service_id);
            }
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }
            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('code_inventaire', 'like', "%{$search}%")
                        ->orWhere('numero_serie', 'like', "%{$search}%")
                        ->orWhere('modele', 'like', "%{$search}%");
                });
            }
        };

        // Format basic equipment properties
        $formatEqRow = function ($eq) {
            $eq->marque_libelle = $eq->marque ? $eq->marque->libelle : '-';
            $eq->direction_libelle = $eq->direction ? $eq->direction->libelle : '-';
            $eq->service_libelle = $eq->service ? $eq->service->libelle : '-';

            return $eq;
        };

        switch ($reportType) {
            case 'global_park':
                $title = 'État du Parc Global';
                $columns = $eqColumns;
                $query = Equipement::with(['marque', 'direction', 'service']);
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'by_status':
                $title = 'Équipements par Statut';
                $columns = $eqColumns;
                $query = Equipement::with(['marque', 'direction', 'service'])->orderBy('statut');
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'by_state':
                $title = 'Équipements par État Physique';
                $columns = $eqColumns;
                $query = Equipement::with(['marque', 'direction', 'service'])->orderBy('etat');
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'warranty_status':
                $days = $request->get('days', '30');
                $title = "Équipements dont la garantie expire sous {$days} jours";
                if ($days === 'expired') {
                    $title = 'Équipements dont la garantie est expirée';
                }

                $columns = $eqColumns;
                $columns['days_remaining'] = 'Jours Restants';

                $query = Equipement::with(['marque', 'direction', 'service'])->whereNotNull('date_fin_garantie');
                $applyEqFilters($query);

                if ($days === 'expired') {
                    $query->whereDate('date_fin_garantie', '<', now());
                } else {
                    $query->whereDate('date_fin_garantie', '>=', now())
                        ->whereDate('date_fin_garantie', '<=', now()->addDays((int) $days));
                }

                $rows = $query->get()->map(function ($eq) use ($formatEqRow) {
                    $eq = $formatEqRow($eq);
                    $daysDiff = now()->diffInDays($eq->date_fin_garantie, false);
                    $eq->days_remaining = $daysDiff < 0 ? 'Expiré depuis '.abs($daysDiff).'j' : "{$daysDiff} jours";

                    return $eq;
                })->toArray();
                break;

            case 'end_of_life':
                $title = 'Équipements en fin de vie probable';
                $columns = $eqColumns;
                $columns['date_fin_vie'] = 'Date Fin de Vie Est.';

                $query = Equipement::with(['marque', 'direction', 'service'])
                    ->whereNotNull('date_mise_en_service')
                    ->whereNotNull('duree_vie_probable');
                $applyEqFilters($query);

                $all = $query->get();
                $filtered = [];
                foreach ($all as $eq) {
                    $finVie = $eq->date_mise_en_service->copy()->addYears($eq->duree_vie_probable);
                    if ($finVie->isPast() || $finVie->isToday()) {
                        $eq = $formatEqRow($eq);
                        $eq->date_fin_vie = $finVie->format('Y-m-d');
                        $filtered[] = $eq;
                    }
                }
                $rows = $filtered;
                break;

            case 'change_history':
                $title = "Historique des changements d'équipements";
                $columns = [
                    'equipement_code' => 'Code Equipement',
                    'date_changement' => 'Date',
                    'type_changement' => 'Type',
                    'ancien_statut' => 'Ancien Statut',
                    'nouveau_statut' => 'Nouveau Statut',
                    'ancien_etat' => 'Ancien État',
                    'nouvel_etat' => 'Nouvel État',
                    'motif' => 'Motif',
                ];
                $query = HistoriqueChangement::with(['equipement'])->orderBy('date_changement', 'desc');
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->whereHas('equipement', function ($q) use ($search) {
                        $q->where('code_inventaire', 'like', "%{$search}%");
                    });
                }
                $rows = $query->get()->map(function ($hist) {
                    $hist->equipement_code = $hist->equipement ? $hist->equipement->code_inventaire : '-';

                    return $hist;
                })->toArray();
                break;

            case 'unassigned':
                $title = 'Équipements sans affectation active (En stock)';
                $columns = $eqColumns;
                $query = Equipement::with(['marque'])
                    ->where('statut', 'en_stock')
                    ->whereNull('direction_id')
                    ->whereNull('service_id')
                    ->whereNull('unite_id');
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'by_direction':
                $title = 'Équipements affectés par Direction';
                $columns = [
                    'direction_libelle' => 'Direction',
                    'total_count' => 'Nombre d\'Équipements',
                    'valeur_totale' => 'Valeur Totale (FCFA)',
                    'etat_moyen' => 'État Moyen',
                ];
                $rows = Direction::all()->map(function ($dir) {
                    $eqs = Equipement::where('direction_id', $dir->id)->get();
                    $states = ['bon' => 4, 'passable' => 3, 'mauvais' => 2, 'avarie' => 1];
                    $totalState = 0;
                    $stateCount = 0;
                    foreach ($eqs as $eq) {
                        if (isset($states[$eq->etat])) {
                            $totalState += $states[$eq->etat];
                            $stateCount++;
                        }
                    }
                    $avgStateVal = $stateCount > 0 ? round($totalState / $stateCount) : 0;
                    $stateLabels = [4 => 'Bon', 3 => 'Passable', 2 => 'Mauvais', 1 => 'Avarié', 0 => 'N/A'];

                    return [
                        'direction_libelle' => $dir->libelle,
                        'total_count' => $eqs->count(),
                        'valeur_totale' => $eqs->sum('valeur_achat'),
                        'etat_moyen' => $stateLabels[$avgStateVal],
                    ];
                })->toArray();
                break;

            case 'by_service':
                $title = 'Équipements affectés par Service';
                $columns = [
                    'service_libelle' => 'Service',
                    'direction_libelle' => 'Direction',
                    'total_count' => 'Nombre d\'Équipements',
                    'valeur_totale' => 'Valeur Totale (FCFA)',
                    'etat_moyen' => 'État Moyen',
                ];
                $rows = Service::with('direction')->get()->map(function ($srv) {
                    $eqs = Equipement::where('service_id', $srv->id)->get();
                    $states = ['bon' => 4, 'passable' => 3, 'mauvais' => 2, 'avarie' => 1];
                    $totalState = 0;
                    $stateCount = 0;
                    foreach ($eqs as $eq) {
                        if (isset($states[$eq->etat])) {
                            $totalState += $states[$eq->etat];
                            $stateCount++;
                        }
                    }
                    $avgStateVal = $stateCount > 0 ? round($totalState / $stateCount) : 0;
                    $stateLabels = [4 => 'Bon', 3 => 'Passable', 2 => 'Mauvais', 1 => 'Avarié', 0 => 'N/A'];

                    return [
                        'service_libelle' => $srv->libelle,
                        'direction_libelle' => $srv->direction ? $srv->direction->libelle : '-',
                        'total_count' => $eqs->count(),
                        'valeur_totale' => $eqs->sum('valeur_achat'),
                        'etat_moyen' => $stateLabels[$avgStateVal],
                    ];
                })->toArray();
                break;

            case 'by_unite':
                $title = 'Équipements affectés par Unité';
                $columns = [
                    'unite_libelle' => 'Unité',
                    'service_libelle' => 'Service',
                    'total_count' => 'Nombre d\'Équipements',
                    'valeur_totale' => 'Valeur Totale (FCFA)',
                ];
                $rows = Unite::with('service')->get()->map(function ($unit) {
                    $eqs = Equipement::where('unite_id', $unit->id)->get();

                    return [
                        'unite_libelle' => $unit->libelle,
                        'service_libelle' => $unit->service ? $unit->service->libelle : '-',
                        'total_count' => $eqs->count(),
                        'valeur_totale' => $eqs->sum('valeur_achat'),
                    ];
                })->toArray();
                break;

            case 'by_site':
                $title = 'Équipements affectés par Site';
                $columns = [
                    'site_libelle' => 'Site',
                    'total_count' => 'Nombre d\'Équipements',
                    'valeur_totale' => 'Valeur Totale (FCFA)',
                ];
                $rows = Site::all()->map(function ($site) {
                    $dirIds = Direction::where('site_id', $site->id)->pluck('id');
                    $eqs = Equipement::whereIn('direction_id', $dirIds)->get();

                    return [
                        'site_libelle' => $site->libelle,
                        'total_count' => $eqs->count(),
                        'valeur_totale' => $eqs->sum('valeur_achat'),
                    ];
                })->toArray();
                break;

            case 'by_local':
                $title = 'Équipements affectés par Local (Bureaux/Salles)';
                $columns = [
                    'local_libelle' => 'Local',
                    'code_inventaire' => 'Equipement Code',
                    'modele' => 'Modèle',
                    'statut' => 'Statut',
                    'date_debut' => 'Date Affectation',
                ];
                $rows = AffectationEquipement::where('statut', true)
                    ->whereNotNull('local_id')
                    ->with(['local', 'equipement'])
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'local_libelle' => $aff->local ? $aff->local->libelle : '-',
                            'code_inventaire' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                            'modele' => $aff->equipement ? $aff->equipement->modele : '-',
                            'statut' => $aff->equipement ? $aff->equipement->statut : '-',
                            'date_debut' => $aff->date_debut ? $aff->date_debut->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

            case 'by_employee':
                $title = 'Équipements affectés à un Employé';
                $columns = [
                    'employe_nom' => 'Employé',
                    'code_inventaire' => 'Code Inventaire',
                    'modele' => 'Modèle',
                    'type_affectation' => 'Type Affectation',
                    'date_debut' => 'Date Début',
                ];
                $query = AffectationEquipement::where('statut', true)
                    ->whereNotNull('dossier_employe_id')
                    ->with(['employe', 'equipement']);
                if ($request->filled('employe_id')) {
                    $query->where('dossier_employe_id', $request->employe_id);
                }
                $rows = $query->get()->map(function ($aff) {
                    return [
                        'employe_nom' => $aff->employe ? $aff->employe->full_name : '-',
                        'code_inventaire' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                        'modele' => $aff->equipement ? $aff->equipement->modele : '-',
                        'type_affectation' => $aff->type_affectation ?? '-',
                        'date_debut' => $aff->date_debut ? $aff->date_debut->format('Y-m-d') : '-',
                    ];
                })->toArray();
                break;

            case 'by_post':
                $title = 'Équipements affectés à un Poste de travail';
                $columns = [
                    'poste_code' => 'Code Poste',
                    'poste_nom' => 'Poste',
                    'code_inventaire' => 'Code Inventaire',
                    'modele' => 'Modèle',
                    'date_debut' => 'Date Début',
                ];
                $rows = AffectationEquipement::where('statut', true)
                    ->whereNotNull('poste_travail_id')
                    ->with(['posteTravail', 'equipement'])
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'poste_code' => $aff->posteTravail ? $aff->posteTravail->code : '-',
                            'poste_nom' => $aff->posteTravail ? $aff->posteTravail->libelle : '-',
                            'code_inventaire' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                            'modele' => $aff->equipement ? $aff->equipement->modele : '-',
                            'date_debut' => $aff->date_debut ? $aff->date_debut->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

            case 'empty_structures':
                $title = 'Directions et Services sans aucun équipement affecté';
                $columns = [
                    'type_structure' => 'Type Structure',
                    'structure_nom' => 'Nom de la Structure',
                    'code' => 'Code',
                ];
                $emptyDirs = Direction::whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('parc_info_equipements')
                        ->whereColumn('parc_info_equipements.direction_id', 'organisation_directions.id');
                })->get()->map(function ($d) {
                    return [
                        'type_structure' => 'Direction',
                        'structure_nom' => $d->libelle,
                        'code' => $d->code,
                    ];
                });
                $emptySrvs = Service::whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('parc_info_equipements')
                        ->whereColumn('parc_info_equipements.service_id', 'organisation_services.id');
                })->get()->map(function ($s) {
                    return [
                        'type_structure' => 'Service',
                        'structure_nom' => $s->libelle,
                        'code' => $s->code,
                    ];
                });
                $rows = $emptyDirs->concat($emptySrvs)->toArray();
                break;

            case 'org_distribution':
                $title = 'Répartition du parc par niveau organisationnel';
                $columns = [
                    'direction' => 'Direction',
                    'service' => 'Service',
                    'unite' => 'Unité',
                    'quantite' => 'Quantité Équipements',
                    'valeur_totale' => 'Valeur Totale (FCFA)',
                ];
                $rows = DB::table('parc_info_equipements')
                    ->leftJoin('organisation_directions', 'parc_info_equipements.direction_id', '=', 'organisation_directions.id')
                    ->leftJoin('organisation_services', 'parc_info_equipements.service_id', '=', 'organisation_services.id')
                    ->leftJoin('organisation_unites', 'parc_info_equipements.unite_id', '=', 'organisation_unites.id')
                    ->select(
                        'organisation_directions.libelle as direction',
                        'organisation_services.libelle as service',
                        'organisation_unites.libelle as unite',
                        DB::raw('count(parc_info_equipements.id) as quantite'),
                        DB::raw('sum(parc_info_equipements.valeur_achat) as valeur_totale')
                    )
                    ->whereNotNull('parc_info_equipements.direction_id')
                    ->groupBy('organisation_directions.libelle', 'organisation_services.libelle', 'organisation_unites.libelle')
                    ->orderBy('direction')
                    ->orderBy('service')
                    ->orderBy('unite')
                    ->get()
                    ->toArray();
                break;

                // Types Specifique Case
            case 'type_computers':
                $title = 'Liste des Ordinateurs';
                $columns = [
                    'code_inventaire' => 'Code',
                    'numero_serie' => 'N° Série',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'type_pc' => 'Type PC',
                    'ram_capacite' => 'RAM (Go)',
                    'cpu_processeur' => 'Processeur',
                    'stockage_capacite' => 'Stockage (Go)',
                    'os_libelle' => 'Système d\'Exploitation',
                    'statut' => 'Statut',
                    'etat' => 'État',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'ordinateur'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'numero_serie' => $eq->numero_serie,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'type_pc' => $eq->champs_valeurs['type_pc'] ?? '-',
                        'ram_capacite' => $eq->champs_valeurs['ram_capacite_go'] ?? '-',
                        'cpu_processeur' => $eq->champs_valeurs['processeur_model'] ?? '-',
                        'stockage_capacite' => $eq->champs_valeurs['stockage_capacite_go'] ?? '-',
                        'os_libelle' => $eq->getValeurAffichee('os_type_id') ?? '-',
                        'statut' => $eq->statut,
                        'etat' => $eq->etat,
                    ];
                })->toArray();
                break;

            case 'type_physical_servers':
                $title = 'Liste des Serveurs Physiques';
                $columns = [
                    'code_inventaire' => 'Code',
                    'numero_serie' => 'N° Série',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'role_serveur' => 'Rôle',
                    'ram_capacite' => 'RAM (Go)',
                    'stockage_capacite' => 'Stockage (Go)',
                    'adresse_ip' => 'Adresse IP',
                    'position_u' => 'Position Rack',
                    'statut' => 'Statut',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'serveur'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    $posD = $eq->champs_valeurs['u_position_depart'] ?? null;
                    $posF = $eq->champs_valeurs['u_position_fin'] ?? null;

                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'numero_serie' => $eq->numero_serie,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'role_serveur' => $eq->champs_valeurs['role_serveur'] ?? '-',
                        'ram_capacite' => $eq->champs_valeurs['ram_capacite_go'] ?? '-',
                        'stockage_capacite' => $eq->champs_valeurs['stockage_capacite_go'] ?? '-',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                        'position_u' => ($posD && $posF) ? "U{$posD}-U{$posF}" : '-',
                        'statut' => $eq->statut,
                    ];
                })->toArray();
                break;

            case 'type_virtual_servers':
                $title = 'Liste des Serveurs Virtuels';
                $columns = [
                    'code_inventaire' => 'Code',
                    'modele' => 'Modèle',
                    'role_serveur' => 'Rôle',
                    'ram_capacite' => 'RAM (Go)',
                    'stockage_capacite' => 'Stockage (Go)',
                    'adresse_ip' => 'Adresse IP',
                    'hyperviseur' => 'Hyperviseur',
                    'hote_physique' => 'Hôte Physique',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'serveur-virtuel'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    $hoteId = $eq->champs_valeurs['serveur_hote_id'] ?? null;
                    $hote = $hoteId ? (Equipement::find($hoteId)?->code_inventaire ?? '-') : '-';

                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'modele' => $eq->modele,
                        'role_serveur' => $eq->champs_valeurs['role_serveur'] ?? '-',
                        'ram_capacite' => $eq->champs_valeurs['ram_capacite_go'] ?? '-',
                        'stockage_capacite' => $eq->champs_valeurs['stockage_capacite_go'] ?? '-',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                        'hyperviseur' => $eq->champs_valeurs['hyperviseur'] ?? '-',
                        'hote_physique' => $hote,
                    ];
                })->toArray();
                break;

            case 'type_printers':
                $title = 'Liste des Imprimantes';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'type_imprimante' => 'Type',
                    'est_multifonction' => 'Multifonction',
                    'est_couleur' => 'Couleur',
                    'adresse_ip' => 'Adresse IP',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'imprimante'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'type_imprimante' => $eq->getValeurAffichee('type_imprimante_id') ?? '-',
                        'est_multifonction' => $eq->getValeurAffichee('est_multifonction') ?? 'Non',
                        'est_couleur' => $eq->getValeurAffichee('est_couleur') ?? 'Non',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                    ];
                })->toArray();
                break;

            case 'type_scanners':
                $title = 'Liste des Scanners';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'resolution_dpi' => 'Résolution Max (DPI)',
                    'format_max' => 'Format Max',
                    'est_recto_verso' => 'Recto Verso',
                    'a_chargeur' => 'Chargeur Auto',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'scanner'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'resolution_dpi' => $eq->champs_valeurs['resolution_dpi_max'] ?? '-',
                        'format_max' => $eq->champs_valeurs['format_max'] ?? '-',
                        'est_recto_verso' => $eq->getValeurAffichee('est_recto_verso') ?? 'Non',
                        'a_chargeur' => $eq->getValeurAffichee('a_chargeur_auto') ?? 'Non',
                    ];
                })->toArray();
                break;

            case 'type_network':
                $title = 'Liste des Équipements Réseau';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'type_reseau' => 'Type Réseau',
                    'nb_ports' => 'Ports',
                    'est_poe' => 'PoE',
                    'est_manageable' => 'Manageable',
                    'vlan' => 'VLAN Management',
                    'adresse_ip' => 'Adresse IP',
                ];
                $query = Equipement::whereHas('categorie', function ($q) {
                    $q->whereIn('code', ['switch', 'routeur', 'wifi', 'parefeu']);
                })->with(['marque', 'categorie']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'type_reseau' => $eq->categorie ? $eq->categorie->libelle : '-',
                        'nb_ports' => $eq->champs_valeurs['nb_ports'] ?? '-',
                        'est_poe' => $eq->getValeurAffichee('est_poe') ?? '-',
                        'est_manageable' => $eq->getValeurAffichee('est_manageable') ?? '-',
                        'vlan' => $eq->champs_valeurs['vlan_management'] ?? '-',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                    ];
                })->toArray();
                break;

            case 'type_ip_phones':
                $title = 'Liste des Téléphones IP';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'extension' => 'Extension',
                    'protocole' => 'Protocole',
                    'adresse_ip' => 'Adresse IP',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'telephone'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'extension' => $eq->champs_valeurs['extension'] ?? '-',
                        'protocole' => $eq->champs_valeurs['protocole'] ?? '-',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                    ];
                })->toArray();
                break;

            case 'type_mobiles':
                $title = 'Liste des Terminaux Mobiles';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'imei' => 'IMEI 1',
                    'os_version' => 'Version OS',
                    'statut_mdm' => 'Statut MDM',
                    'num_tel' => 'Numéro Associé',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'mobile'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'imei' => $eq->champs_valeurs['imei_1'] ?? '-',
                        'os_version' => $eq->champs_valeurs['version_os'] ?? '-',
                        'statut_mdm' => $eq->champs_valeurs['statut_mdm'] ?? '-',
                        'num_tel' => $eq->champs_valeurs['num_tel_associe'] ?? '-',
                    ];
                })->toArray();
                break;

            case 'type_cameras':
                $title = 'Liste des Caméras IP';
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'resolution' => 'Résolution',
                    'type_camera' => 'Type Caméra',
                    'emplacement' => 'Emplacement',
                    'adresse_ip' => 'Adresse IP',
                ];
                $query = Equipement::whereHas('categorie', fn ($q) => $q->where('code', 'camera'))->with(['marque']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'resolution' => $eq->champs_valeurs['resolution'] ?? '-',
                        'type_camera' => $eq->champs_valeurs['type_camera'] ?? '-',
                        'emplacement' => $eq->champs_valeurs['emplacement'] ?? '-',
                        'adresse_ip' => $eq->champs_valeurs['adresse_ip'] ?? '-',
                    ];
                })->toArray();
                break;

            case 'type_infra':
                $title = "Liste des Équipements d'Infrastructures";
                $columns = [
                    'code_inventaire' => 'Code',
                    'marque_libelle' => 'Marque',
                    'modele' => 'Modèle',
                    'type_infra' => 'Type',
                    'puissance' => 'Puissance (VA)',
                    'autonomie' => 'Autonomie (min)',
                    'capacite_u' => 'Capacité (U)',
                ];
                $query = Equipement::whereHas('categorie', function ($q) {
                    $q->whereIn('code', ['onduleur', 'rack', 'brassage']);
                })->with(['marque', 'categorie']);
                $applyEqFilters($query);
                $rows = $query->get()->map(function ($eq) {
                    return [
                        'code_inventaire' => $eq->code_inventaire,
                        'marque_libelle' => $eq->marque ? $eq->marque->libelle : '-',
                        'modele' => $eq->modele,
                        'type_infra' => $eq->categorie ? $eq->categorie->libelle : '-',
                        'puissance' => $eq->champs_valeurs['puissance_va'] ?? '-',
                        'autonomie' => $eq->champs_valeurs['autonomie_minutes'] ?? '-',
                        'capacite_u' => $eq->champs_valeurs['u_capacite_totale'] ?? '-',
                    ];
                })->toArray();
                break;

                // Licences
            case 'lic_active_util':
                $title = "Licences actives & Taux d'utilisation";
                $columns = [
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'nombre_postes_accordes' => 'Postes Accordés',
                    'nombre_postes_utilises' => 'Postes Utilisés',
                    'taux_utilisation' => 'Taux d\'Utilisation (%)',
                    'statut' => 'Statut',
                ];
                $query = Licence::with('logiciel')->where('actif', true);
                $rows = $query->get()->map(function ($lic) {
                    return [
                        'logiciel_nom' => $lic->logiciel ? $lic->logiciel->nom : '-',
                        'cle_licence' => $lic->cle_licence,
                        'nombre_postes_accordes' => $lic->nombre_postes_accordes,
                        'nombre_postes_utilises' => $lic->nombre_postes_utilises,
                        'taux_utilisation' => $lic->taux_utilisation,
                        'statut' => $lic->statut,
                    ];
                })->toArray();
                break;

            case 'lic_expired':
                $days = $request->get('days', '30');
                $title = "Licences expirées ou expirant sous {$days} jours";
                $columns = [
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'date_expiration' => 'Date Expiration',
                    'statut' => 'Statut',
                ];
                $query = Licence::with('logiciel');
                if ($days === 'expired') {
                    $query->whereDate('date_expiration', '<', now());
                } else {
                    $query->whereDate('date_expiration', '>=', now())
                        ->whereDate('date_expiration', '<=', now()->addDays((int) $days));
                }
                $rows = $query->get()->map(function ($lic) {
                    return [
                        'logiciel_nom' => $lic->logiciel ? $lic->logiciel->nom : '-',
                        'cle_licence' => $lic->cle_licence,
                        'date_expiration' => $lic->date_expiration ? $lic->date_expiration->format('Y-m-d') : '-',
                        'statut' => $lic->statut,
                    ];
                })->toArray();
                break;

            case 'lic_underused':
                $title = 'Licences sous-utilisées (< 50%)';
                $columns = [
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'nombre_postes_accordes' => 'Postes Accordés',
                    'nombre_postes_utilises' => 'Postes Utilisés',
                    'taux_utilisation' => 'Taux d\'Utilisation (%)',
                ];
                $rows = Licence::with('logiciel')
                    ->where('actif', true)
                    ->where('nombre_postes_accordes', '>', 0)
                    ->get()
                    ->filter(function ($lic) {
                        return $lic->taux_utilisation < 50;
                    })
                    ->map(function ($lic) {
                        return [
                            'logiciel_nom' => $lic->logiciel ? $lic->logiciel->nom : '-',
                            'cle_licence' => $lic->cle_licence,
                            'nombre_postes_accordes' => $lic->nombre_postes_accordes,
                            'nombre_postes_utilises' => $lic->nombre_postes_utilises,
                            'taux_utilisation' => $lic->taux_utilisation,
                        ];
                    })->values()->toArray();
                break;

            case 'lic_overused':
                $title = 'Licences sur-utilisées (Risque non-conformité)';
                $columns = [
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'nombre_postes_accordes' => 'Postes Accordés',
                    'nombre_postes_utilises' => 'Postes Utilisés',
                    'taux_utilisation' => 'Taux d\'Utilisation (%)',
                ];
                $rows = Licence::with('logiciel')
                    ->where('actif', true)
                    ->get()
                    ->filter(function ($lic) {
                        return $lic->nombre_postes_utilises >= $lic->nombre_postes_accordes;
                    })
                    ->map(function ($lic) {
                        return [
                            'logiciel_nom' => $lic->logiciel ? $lic->logiciel->nom : '-',
                            'cle_licence' => $lic->cle_licence,
                            'nombre_postes_accordes' => $lic->nombre_postes_accordes,
                            'nombre_postes_utilises' => $lic->nombre_postes_utilises,
                            'taux_utilisation' => $lic->taux_utilisation,
                        ];
                    })->values()->toArray();
                break;

            case 'lic_by_employee':
                $title = 'Affectations de licences par employé';
                $columns = [
                    'employe' => 'Employé',
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'date_affectation' => 'Date Affectation',
                ];
                $rows = AffectationLicence::where('actif', true)
                    ->whereNotNull('employe_id')
                    ->with(['employe', 'licence.logiciel'])
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'employe' => $aff->employe ? $aff->employe->full_name : '-',
                            'logiciel_nom' => $aff->licence && $aff->licence->logiciel ? $aff->licence->logiciel->nom : '-',
                            'cle_licence' => $aff->licence ? $aff->licence->cle_licence : '-',
                            'date_affectation' => $aff->date_affectation ? $aff->date_affectation->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

            case 'lic_by_equipment':
                $title = 'Affectations de licences par équipement';
                $columns = [
                    'equipement_code' => 'Equipement Code',
                    'modele' => 'Modèle',
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé de Licence',
                    'date_affectation' => 'Date Affectation',
                ];
                $rows = AffectationLicence::where('actif', true)
                    ->whereNotNull('equipement_id')
                    ->with(['equipement', 'licence.logiciel'])
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'equipement_code' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                            'modele' => $aff->equipement ? $aff->equipement->modele : '-',
                            'logiciel_nom' => $aff->licence && $aff->licence->logiciel ? $aff->licence->logiciel->nom : '-',
                            'cle_licence' => $aff->licence ? $aff->licence->cle_licence : '-',
                            'date_affectation' => $aff->date_affectation ? $aff->date_affectation->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

            case 'lic_documents':
                $title = 'Documents attachés aux licences';
                $columns = [
                    'logiciel_nom' => 'Logiciel',
                    'cle_licence' => 'Clé Licence',
                    'document_nom' => 'Nom du Document',
                    'document_type' => 'Type Document',
                    'date_document' => 'Date Document',
                    'description' => 'Description',
                ];
                $rows = DocumentLicence::with('licence.logiciel')
                    ->get()
                    ->map(function ($doc) {
                        return [
                            'logiciel_nom' => $doc->licence && $doc->licence->logiciel ? $doc->licence->logiciel->nom : '-',
                            'cle_licence' => $doc->licence ? $doc->licence->cle_licence : '-',
                            'document_nom' => $doc->nom_fichier,
                            'document_type' => $doc->type,
                            'date_document' => $doc->date_document ? $doc->date_document->format('Y-m-d') : '-',
                            'description' => $doc->description ?? '-',
                        ];
                    })->toArray();
                break;

            case 'software_by_editor':
                $title = 'Logiciels par éditeur et catégorie';
                $columns = [
                    'editeur' => 'Éditeur',
                    'nom' => 'Logiciel',
                    'categorie' => 'Catégorie',
                    'licences_count' => 'Licences Enregistrées',
                ];
                $rows = Logiciel::with(['editeur', 'licences'])
                    ->orderBy('categorie')
                    ->get()
                    ->map(function ($log) {
                        return [
                            'editeur' => $log->editeur ? $log->editeur->nom : '-',
                            'nom' => $log->nom,
                            'categorie' => $log->categorie,
                            'licences_count' => $log->licences->count(),
                        ];
                    })->toArray();
                break;

                // Consommables — EF-STK-05 : quantités lues auprès du module Stock.
            case 'cons_stock_state':
                $title = 'État des Stocks de Consommables';
                $columns = [
                    'code' => 'Code',
                    'nom' => 'Désignation',
                    'marque' => 'Marque',
                    'stock_actuel' => 'Stock Actuel',
                    'stock_min' => 'Stock Min',
                    'stock_max' => 'Stock Max',
                    'statut_stock' => 'Statut Stock',
                    'valeur_stock' => 'Valeur Stock (FCFA)',
                ];
                $consommables = Consommable::with('marque')->get();
                $stock = app(\Modules\ParcInfo\Contracts\StockIntegrationInterface::class);
                $quantites = $stock->quantitesParArticles($consommables->pluck('article_id')->filter()->values()->all());
                $valeurs = $stock->valorisationParArticles($consommables->pluck('article_id')->filter()->values()->all());

                $rows = $consommables->map(function ($cons) use ($quantites, $valeurs) {
                    $quantite = $cons->article_id !== null ? (int) ($quantites[$cons->article_id] ?? 0) : null;

                    return [
                        'code' => $cons->code,
                        'nom' => $cons->nom,
                        'marque' => $cons->marque ? $cons->marque->libelle : '-',
                        'stock_actuel' => $quantite ?? '—',
                        'stock_min' => $cons->quantite_stock_min,
                        'stock_max' => $cons->quantite_stock_max,
                        'statut_stock' => match (true) {
                            $quantite === null => 'NON SUIVI',
                            $quantite === 0 => 'RUPTURE',
                            $quantite <= (int) $cons->quantite_stock_min => 'ALERTE',
                            default => 'NORMAL',
                        },
                        'valeur_stock' => $cons->article_id !== null ? (float) ($valeurs[$cons->article_id] ?? 0) : '—',
                    ];
                })->toArray();
                break;

            case 'cons_under_min':
                $title = 'Consommables sous le seuil de réapprovisionnement';
                $columns = [
                    'code' => 'Code',
                    'nom' => 'Désignation',
                    'stock_actuel' => 'Stock Actuel',
                    'stock_min' => 'Stock Min',
                    'fournisseur' => 'Fournisseur Principal',
                ];
                $consommables = Consommable::with('fournisseur')->whereNotNull('article_id')->get();
                $quantites = app(\Modules\ParcInfo\Contracts\StockIntegrationInterface::class)
                    ->quantitesParArticles($consommables->pluck('article_id')->all());

                $rows = $consommables
                    ->filter(fn ($cons) => (int) ($quantites[$cons->article_id] ?? 0) <= (int) $cons->quantite_stock_min)
                    ->map(function ($cons) use ($quantites) {
                        return [
                            'code' => $cons->code,
                            'nom' => $cons->nom,
                            'stock_actuel' => (int) ($quantites[$cons->article_id] ?? 0),
                            'stock_min' => $cons->quantite_stock_min,
                            'fournisseur' => $cons->fournisseur ? $cons->fournisseur->nom : '-',
                        ];
                    })->values()->toArray();
                break;

            case 'cons_equip_assign':
                $title = 'Affectations de consommables aux équipements';
                $columns = [
                    'consommable' => 'Consommable',
                    'equipement' => 'Équipement Cible',
                    'quantite' => 'Quantité Fournie',
                    'date_affectation' => 'Date Affectation',
                    'date_remplacement' => 'Prochain Remplacement Prévu',
                ];
                $rows = AffectationConsommable::with(['consommable', 'equipement'])
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'consommable' => $aff->consommable ? $aff->consommable->nom : '-',
                            'equipement' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                            'quantite' => $aff->quantite_fournie,
                            'date_affectation' => $aff->date_affectation ? $aff->date_affectation->format('Y-m-d') : '-',
                            'date_remplacement' => $aff->date_remplacement_prochain_prevu ? $aff->date_remplacement_prochain_prevu->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

            case 'cons_late_replace':
                $title = 'Remplacements de consommables en retard';
                $columns = [
                    'consommable' => 'Consommable',
                    'equipement' => 'Équipement Cible',
                    'date_remplacement' => 'Date Prévue Dépassée',
                    'retard_jours' => 'Retard (jours)',
                ];
                $rows = AffectationConsommable::with(['consommable', 'equipement'])
                    ->whereDate('date_remplacement_prochain_prevu', '<', now())
                    ->get()
                    ->map(function ($aff) {
                        return [
                            'consommable' => $aff->consommable ? $aff->consommable->nom : '-',
                            'equipement' => $aff->equipement ? $aff->equipement->code_inventaire : '-',
                            'date_remplacement' => $aff->date_remplacement_prochain_prevu ? $aff->date_remplacement_prochain_prevu->format('Y-m-d') : '-',
                            'retard_jours' => $aff->date_remplacement_prochain_prevu ? now()->diffInDays($aff->date_remplacement_prochain_prevu) : '-',
                        ];
                    })->toArray();
                break;

            case 'cons_movements':
                $title = 'Historique des mouvements de stock consommables';
                $columns = [
                    'consommable' => 'Consommable',
                    'type_mouvement' => 'Type Mouvement',
                    'quantite' => 'Quantité',
                    'date_mouvement' => 'Date Mouvement',
                    'raison' => 'Raison / Motif',
                ];
                $rows = MouvementConsommable::with('consommable')
                    ->orderBy('date_mouvement', 'desc')
                    ->get()
                    ->map(function ($mov) {
                        return [
                            'consommable' => $mov->consommable ? $mov->consommable->nom : '-',
                            'type_mouvement' => $mov->type_mouvement,
                            'quantite' => $mov->quantite,
                            'date_mouvement' => $mov->date_mouvement ? $mov->date_mouvement->format('Y-m-d H:i') : '-',
                            'raison' => $mov->raison ?? '-',
                        ];
                    })->toArray();
                break;

            case 'cons_mov_by_structure':
                $title = 'Consommation de consommables par service/unité';
                $columns = [
                    'structure' => 'Service / Unité',
                    'consommable' => 'Consommable',
                    'quantite_consommee' => 'Quantité Consommée',
                    'date_mouvement' => 'Date',
                ];
                $rows = MouvementConsommable::where('type_mouvement', 'SORTIE')
                    ->where(function ($q) {
                        $q->whereNotNull('service_id')
                            ->orWhereNotNull('unite_id');
                    })
                    ->with(['consommable', 'service', 'unite'])
                    ->get()
                    ->map(function ($mov) {
                        $struct = $mov->service ? $mov->service->libelle : ($mov->unite ? $mov->unite->libelle : '-');

                        return [
                            'structure' => $struct,
                            'consommable' => $mov->consommable ? $mov->consommable->nom : '-',
                            'quantite_consommee' => $mov->quantite,
                            'date_mouvement' => $mov->date_mouvement ? $mov->date_mouvement->format('Y-m-d') : '-',
                        ];
                    })->toArray();
                break;

                // Contrats & Fournisseurs
            case 'contracts_active':
                $title = 'Contrats de Maintenance Actifs';
                $columns = [
                    'reference' => 'Référence',
                    'nom' => 'Nom du Contrat',
                    'fournisseur' => 'Fournisseur',
                    'date_debut' => 'Date Début',
                    'date_fin' => 'Date Fin',
                    'cout' => 'Coût Annuel (FCFA)',
                ];
                $rows = ContratMaintenance::where('est_actif', true)
                    ->where(function ($q) {
                        $q->whereNull('date_fin')
                            ->orWhere('date_fin', '>=', now());
                    })
                    ->with('fournisseur')
                    ->get()
                    ->map(function ($c) {
                        return [
                            'reference' => $c->reference,
                            'nom' => $c->nom,
                            'fournisseur' => $c->fournisseur ? $c->fournisseur->nom : '-',
                            'date_debut' => $c->date_debut ? $c->date_debut->format('Y-m-d') : '-',
                            'date_fin' => $c->date_fin ? $c->date_fin->format('Y-m-d') : '-',
                            'cout' => $c->cout,
                        ];
                    })->toArray();
                break;

            case 'contracts_expiring':
                $days = $request->get('days', '30');
                $title = "Contrats de maintenance expirant sous {$days} jours";
                $columns = [
                    'reference' => 'Référence',
                    'nom' => 'Nom du Contrat',
                    'date_fin' => 'Date Fin Expiration',
                    'fournisseur' => 'Fournisseur',
                ];
                $query = ContratMaintenance::with('fournisseur');
                if ($days === 'expired') {
                    $query->whereDate('date_fin', '<', now());
                } else {
                    $query->whereDate('date_fin', '>=', now())
                        ->whereDate('date_fin', '<=', now()->addDays((int) $days));
                }
                $rows = $query->get()->map(function ($c) {
                    return [
                        'reference' => $c->reference,
                        'nom' => $c->nom,
                        'date_fin' => $c->date_fin ? $c->date_fin->format('Y-m-d') : '-',
                        'fournisseur' => $c->fournisseur ? $c->fournisseur->nom : '-',
                    ];
                })->toArray();
                break;

            case 'equip_covered':
                $title = 'Équipements couverts par un contrat de maintenance';
                $columns = $eqColumns;

                // Get equipment IDs covered via active licenses linked to contracts
                $coveredIds = AffectationLicence::where('actif', true)
                    ->whereNotNull('equipement_id')
                    ->whereHas('licence', function ($q) {
                        $q->whereNotNull('contrat_maintenance_id');
                    })
                    ->pluck('equipement_id')
                    ->unique();

                $query = Equipement::with(['marque', 'direction', 'service'])
                    ->whereIn('id', $coveredIds);
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'equip_not_covered':
                $title = 'Équipements non couverts par aucun contrat';
                $columns = $eqColumns;

                $coveredIds = AffectationLicence::where('actif', true)
                    ->whereNotNull('equipement_id')
                    ->whereHas('licence', function ($q) {
                        $q->whereNotNull('contrat_maintenance_id');
                    })
                    ->pluck('equipement_id')
                    ->unique();

                $query = Equipement::with(['marque', 'direction', 'service'])
                    ->whereNotIn('id', $coveredIds);
                $applyEqFilters($query);
                $rows = $query->get()->map($formatEqRow)->toArray();
                break;

            case 'active_vendors':
                $title = 'Liste des Fournisseurs Actifs';
                $columns = [
                    'code' => 'Code',
                    'nom' => 'Fournisseur',
                    'email' => 'Email',
                    'telephone' => 'Téléphone',
                    'delai_livraison' => 'Délai Livraison (jours)',
                    'fiabilite_score' => 'Fiabilité Score (/100)',
                ];
                $rows = Fournisseur::where('est_actif', true)->get()->toArray();
                break;

                // Utilisateurs & Accès
            case 'users_roles':
                $title = 'Utilisateurs système avec rôles et permissions';
                $columns = [
                    'name' => 'Nom Utilisateur',
                    'email' => 'Email',
                    'roles' => 'Rôles Affectés',
                    'permissions' => 'Permissions Directes',
                ];
                $rows = User::with(['roles', 'permissions'])->get()->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'roles' => $user->roles->pluck('name')->implode(', ') ?: 'Aucun',
                        'permissions' => $user->permissions->pluck('name')->implode(', ') ?: 'Aucune',
                    ];
                })->toArray();
                break;

            case 'employees_no_user':
                $title = 'Employés sans compte utilisateur système';
                $columns = [
                    'matricule' => 'Matricule',
                    'full_name' => 'Nom Complet',
                    'poste' => 'Poste occupé',
                    'organisation' => 'Rattachement',
                ];
                $rows = Employe::whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('users')
                        ->whereColumn('users.dossier_employe_id', 'grh_dossiers_employes.id');
                })->get()->map(function ($emp) {
                    return [
                        'matricule' => $emp->matricule,
                        'full_name' => $emp->full_name,
                        'poste' => $emp->poste ?? '-',
                        'organisation' => $emp->organisation,
                    ];
                })->toArray();
                break;

            case 'users_no_employee':
                $title = 'Comptes utilisateurs sans dossier employé lié';
                $columns = [
                    'name' => 'Nom Utilisateur',
                    'email' => 'Email',
                    'created_at' => 'Date Création',
                ];
                $rows = User::whereNull('dossier_employe_id')->get()->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at ? $user->created_at->format('Y-m-d') : '-',
                    ];
                })->toArray();
                break;
        }

        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }
}
