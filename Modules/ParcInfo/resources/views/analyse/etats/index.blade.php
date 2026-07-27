@extends('parcinfo::layouts.master')

@section('header', 'Rapports & États du Parc')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Analyse</li>
    <li class="breadcrumb-item active">Rapports & États</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .report-sidebar {
        max-height: calc(100vh - 180px);
        overflow-y: auto;
    }
    .report-group-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        color: #6c757d;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        padding-left: 0.5rem;
    }
    .report-item {
        font-size: 0.85rem;
        padding: 0.6rem 0.75rem;
        border-radius: 6px;
        color: #495057;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
        margin-bottom: 0.2rem;
    }
    .report-item i {
        font-size: 1rem;
        margin-right: 0.75rem;
        opacity: 0.7;
    }
    .report-item:hover {
        background-color: #f1f3f5;
        color: #0d6efd;
    }
    .report-item.active {
        background-color: #e7f1ff;
        color: #0d6efd;
        font-weight: 600;
    }
    .filter-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-left: 4px solid #0d6efd;
    }
    .pdf-iframe-container {
        position: relative;
        overflow: hidden;
        width: 100%;
        padding-top: 70%; /* Aspect Ratio */
    }
    .pdf-iframe {
        position: absolute;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        width: 100%;
        height: 100%;
        border: none;
    }
</style>
@endpush

@section('content')
<div class="row g-4">
    {{-- Left Sidebar: Reports List --}}
    <div class="col-md-4 col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-folder-symlink me-2 text-primary"></i>Catégories de Rapports</h6>
            </div>
            <div class="card-body p-2 report-sidebar">
                {{-- Category 1 --}}
                <div class="report-group-title">Équipements</div>
                <a href="#" class="report-item active" data-type="global_park" data-desc="Liste de tous les équipements avec leur statut, état, marque, modèle, valeur d'achat et garantie.">
                    <i class="bi bi-pc-display"></i> État du parc global
                </a>
                <a href="#" class="report-item" data-type="by_status" data-desc="Équipements filtrés et regroupés par leur statut (En stock, en service, en réparation, etc.).">
                    <i class="bi bi-tags"></i> Équipements par statut
                </a>
                <a href="#" class="report-item" data-type="by_state" data-desc="Équipements filtrés et regroupés par leur état physique (Bon, passable, mauvais, avarié).">
                    <i class="bi bi-heart-pulse"></i> Équipements par état
                </a>
                <a href="#" class="report-item" data-type="warranty_status" data-show-days="true" data-desc="Équipements dont la garantie est expirée ou expire sous 30/60/90 jours.">
                    <i class="bi bi-shield-check"></i> Garantie expirée ou proche
                </a>
                <a href="#" class="report-item" data-type="end_of_life" data-desc="Équipements dont la durée de vie théorique estimée est dépassée.">
                    <i class="bi bi-hourglass-bottom"></i> Fin de vie probable
                </a>
                <a href="#" class="report-item" data-type="change_history" data-desc="Historique chronologique des changements de statut, d'état et d'affectation des matériels.">
                    <i class="bi bi-clock-history"></i> Historique des changements
                </a>
                <a href="#" class="report-item" data-type="unassigned" data-desc="Équipements actuellement en stock et ne disposant d'aucune affectation active.">
                    <i class="bi bi-box-seam"></i> Matériels non affectés
                </a>

                {{-- Category 2 --}}
                <div class="report-group-title">Structure & Organisation</div>
                <a href="#" class="report-item" data-type="by_direction" data-desc="Équipements affectés par Direction avec nombre, valeur d'achat globale et état moyen.">
                    <i class="bi bi-building"></i> Affectés par direction
                </a>
                <a href="#" class="report-item" data-type="by_service" data-desc="Équipements affectés par Service de l'entreprise.">
                    <i class="bi bi-diagram-3"></i> Affectés par service
                </a>
                <a href="#" class="report-item" data-type="by_unite" data-desc="Équipements affectés par Unité organisationnelle.">
                    <i class="bi bi-grid-1x2"></i> Affectés par unité
                </a>
                <a href="#" class="report-item" data-type="by_site" data-desc="Équipements localisés par site géographique.">
                    <i class="bi bi-geo-alt"></i> Affectés par site
                </a>
                <a href="#" class="report-item" data-type="by_local" data-desc="Équipements affectés par local (Bureau, Salle serveur, etc.).">
                    <i class="bi bi-door-open"></i> Affectés par local
                </a>
                <a href="#" class="report-item" data-type="by_employee" data-show-employe="true" data-desc="Équipements affectés à un employé donné du parc.">
                    <i class="bi bi-person-badge"></i> Affectés à un employé
                </a>
                <a href="#" class="report-item" data-type="by_post" data-desc="Équipements affectés à un poste de travail.">
                    <i class="bi bi-laptop"></i> Affectés à un poste
                </a>
                <a href="#" class="report-item" data-type="empty_structures" data-desc="Liste des directions et services n'ayant aucun équipement affecté actuellement.">
                    <i class="bi bi-exclamation-triangle"></i> Structures sans équipement
                </a>
                <a href="#" class="report-item" data-type="org_distribution" data-desc="Distribution quantitative et financière globale par Direction -> Service -> Unité.">
                    <i class="bi bi-bar-chart-steps"></i> Répartition par structure
                </a>

                {{-- Category 3 --}}
                <div class="report-group-title">Par Type d'Équipement</div>
                <a href="#" class="report-item" data-type="type_computers" data-desc="Liste des ordinateurs avec détails techniques (Type, RAM, CPU, Stockage, OS, etc.).">
                    <i class="bi bi-laptop"></i> Liste des ordinateurs
                </a>
                <a href="#" class="report-item" data-type="type_physical_servers" data-desc="Liste des serveurs physiques avec rôle, ressources matérielles et position en rack.">
                    <i class="bi bi-hdd-rack"></i> Liste des serveurs physiques
                </a>
                <a href="#" class="report-item" data-type="type_virtual_servers" data-desc="Liste des serveurs virtuels avec hôte physique de rattachement et ressources allouées.">
                    <i class="bi bi-clouds"></i> Liste des serveurs virtuels
                </a>
                <a href="#" class="report-item" data-type="type_printers" data-desc="Liste des imprimantes IP avec détails couleur, multifonction et adresse réseau.">
                    <i class="bi bi-printer"></i> Liste des imprimantes
                </a>
                <a href="#" class="report-item" data-type="type_scanners" data-desc="Liste des scanners et numériseurs du parc.">
                    <i class="bi bi-qr-code-scan"></i> Liste des scanners
                </a>
                <a href="#" class="report-item" data-type="type_network" data-desc="Liste des switchs, routeurs et pare-feu avec gestion PoE, VLAN et adresse IP.">
                    <i class="bi bi-ethernet"></i> Équipements réseau
                </a>
                <a href="#" class="report-item" data-type="type_ip_phones" data-desc="Liste des téléphones IP, extensions et protocoles (SIP/H323).">
                    <i class="bi bi-telephone-inbound"></i> Téléphones IP
                </a>
                <a href="#" class="report-item" data-type="type_mobiles" data-desc="Liste des terminaux mobiles, smartphones, tablettes et statut MDM.">
                    <i class="bi bi-phone"></i> Liste des mobiles
                </a>
                <a href="#" class="report-item" data-type="type_cameras" data-desc="Liste des caméras IP de surveillance avec résolution et emplacement.">
                    <i class="bi bi-camera-video"></i> Caméras IP
                </a>
                <a href="#" class="report-item" data-type="type_infra" data-desc="Infrastructures de salle (Onduleurs, Racks, Brassage) avec puissance et U.">
                    <i class="bi bi-cpu"></i> Infrastructures
                </a>

                {{-- Category 4 --}}
                <div class="report-group-title">Licences & Logiciels</div>
                <a href="#" class="report-item" data-type="lic_active_util" data-desc="Licences actives avec taux d'utilisation (postes accordés vs utilisés).">
                    <i class="bi bi-key"></i> Licences actives & utilisation
                </a>
                <a href="#" class="report-item" data-type="lic_expired" data-show-days="true" data-desc="Licences logicielles expirées ou expirant prochainement.">
                    <i class="bi bi-patch-exclamation"></i> Licences expirées ou proches
                </a>
                <a href="#" class="report-item" data-type="lic_underused" data-desc="Licences logicielles sous-utilisées (taux d'usage inférieur à 50%).">
                    <i class="bi bi-arrow-down-left-square"></i> Licences sous-utilisées
                </a>
                <a href="#" class="report-item" data-type="lic_overused" data-desc="Licences sur-utilisées (postes utilisés >= accordés — risque légal).">
                    <i class="bi bi-shield-slash"></i> Licences sur-utilisées
                </a>
                <a href="#" class="report-item" data-type="lic_by_employee" data-desc="Attribution des licences logicielles nominatives par employé.">
                    <i class="bi bi-person-check"></i> Licences par employé
                </a>
                <a href="#" class="report-item" data-type="lic_by_equipment" data-desc="Licences de licences attribuées aux matériels physiques.">
                    <i class="bi bi-pc-horizontal"></i> Licences par équipement
                </a>
                <a href="#" class="report-item" data-type="lic_documents" data-desc="Documents justificatifs, factures et certificats associés aux licences.">
                    <i class="bi bi-file-earmark-pdf"></i> Documents des licences
                </a>
                <a href="#" class="report-item" data-type="software_by_editor" data-desc="Logiciels classés par éditeur et catégorie fonctionnelle.">
                    <i class="bi bi-journal-code"></i> Logiciels par éditeur
                </a>

                {{-- Category 5 --}}
                <div class="report-group-title">Consommables</div>
                <a href="#" class="report-item" data-type="cons_equip_assign" data-desc="Affectation des consommables aux imprimantes avec date de remplacement prévue.">
                    <i class="bi bi-arrow-left-right"></i> Affectations consommables
                </a>
                <a href="#" class="report-item" data-type="cons_late_replace" data-desc="Remplacements de consommables dont la date prévue est dépassée.">
                    <i class="bi bi-calendar-x"></i> Remplacements en retard
                </a>

                {{-- Category 6 --}}
                <div class="report-group-title">Contrats & Fournisseurs</div>
                <a href="#" class="report-item" data-type="contracts_active" data-desc="Contrats de maintenance actifs avec dates de validité et coûts.">
                    <i class="bi bi-file-earmark-lock"></i> Contrats de maintenance actifs
                </a>
                <a href="#" class="report-item" data-type="contracts_expiring" data-show-days="true" data-desc="Contrats arrivant à échéance sous 30/60/90 jours.">
                    <i class="bi bi-file-earmark-break"></i> Contrats expirant
                </a>
                <a href="#" class="report-item" data-type="equip_covered" data-desc="Équipements associés à une licence ou un contrat de maintenance actif.">
                    <i class="bi bi-shield-fill-check text-success"></i> Équipements couverts
                </a>
                <a href="#" class="report-item" data-type="equip_not_covered" data-desc="Équipements non couverts par aucun contrat de maintenance.">
                    <i class="bi bi-shield-fill-x text-danger"></i> Équipements non couverts
                </a>
                <a href="#" class="report-item" data-type="active_vendors" data-desc="Liste des fournisseurs actifs avec délai de livraison et score de fiabilité.">
                    <i class="bi bi-truck"></i> Liste des fournisseurs
                </a>

                {{-- Category 7 --}}
                <div class="report-group-title">Utilisateurs & Accès</div>
                <a href="#" class="report-item" data-type="users_roles" data-desc="Utilisateurs actifs du système avec rôles et permissions associés.">
                    <i class="bi bi-people"></i> Utilisateurs et rôles
                </a>
                <a href="#" class="report-item" data-type="employees_no_user" data-desc="Employés ne possédant pas de compte utilisateur lié dans le système.">
                    <i class="bi bi-person-dash"></i> Employés sans compte
                </a>
                <a href="#" class="report-item" data-type="users_no_employee" data-desc="Comptes utilisateurs système qui ne sont rattachés à aucun dossier employé.">
                    <i class="bi bi-person-x"></i> Comptes sans employé
                </a>
            </div>
        </div>
    </div>

    {{-- Right Content Area --}}
    <div class="col-md-8 col-lg-9">
        {{-- Selected Report Metadata --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="fw-bold mb-1 text-primary" id="report-title-display">État du Parc Global</h4>
                        <p class="text-muted small mb-0" id="report-desc-display">Liste de tous les équipements avec statut, état, marque, modèle, valeur d'achat et garantie.</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download me-1"></i> Exporter
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item export-action" href="#" data-format="csv"><i class="bi bi-filetype-csv me-2 text-secondary"></i>Format CSV</a></li>
                            <li><a class="dropdown-item export-action" href="#" data-format="excel"><i class="bi bi-file-earmark-excel me-2 text-success"></i>Format Excel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item export-action" href="#" data-format="pdf"><i class="bi bi-file-earmark-pdf me-2 text-danger"></i>Aperçu / Imprimer PDF</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dynamic Filters --}}
        <div class="card border-0 shadow-sm mb-3 filter-card">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end" id="filters-form">
                    {{-- Common Filter: Direction --}}
                    <div class="col-md-3 common-filter filter-container">
                        <label class="form-label small fw-semibold mb-1">Direction</label>
                        <select class="form-select form-select-sm" id="filter-direction">
                            <option value="">Toutes</option>
                            @foreach($directions as $dir)
                                <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Common Filter: Service --}}
                    <div class="col-md-3 common-filter filter-container">
                        <label class="form-label small fw-semibold mb-1">Service</label>
                        <select class="form-select form-select-sm" id="filter-service">
                            <option value="">Tous</option>
                            @foreach($services as $srv)
                                <option value="{{ $srv->id }}">{{ $srv->libelle }}</option>
                            @endforeach
                        </select>
                    </div>
                    {{-- Common Filter: Statut --}}
                    <div class="col-md-2 common-filter filter-container">
                        <label class="form-label small fw-semibold mb-1">Statut</label>
                        <select class="form-select form-select-sm" id="filter-statut">
                            <option value="">Tous</option>
                            <option value="en_stock">En stock</option>
                            <option value="en_service">En service</option>
                            <option value="en_reparation">En réparation</option>
                            <option value="perdu">Perdu</option>
                            <option value="reforme">Réformé</option>
                        </select>
                    </div>
                    {{-- Common Filter: Etat --}}
                    <div class="col-md-2 common-filter filter-container">
                        <label class="form-label small fw-semibold mb-1">État Physique</label>
                        <select class="form-select form-select-sm" id="filter-etat">
                            <option value="">Tous</option>
                            <option value="bon">Bon</option>
                            <option value="passable">Passable</option>
                            <option value="mauvais">Mauvais</option>
                            <option value="avarie">Avarié</option>
                        </select>
                    </div>

                    {{-- Specific Filter: Days (Hidden by default) --}}
                    <div class="col-md-4 specific-filter filter-container" id="filter-days-container" style="display: none;">
                        <label class="form-label small fw-semibold mb-1">Période d'expiration</label>
                        <select class="form-select form-select-sm" id="filter-days">
                            <option value="30">Dans les 30 prochains jours</option>
                            <option value="60">Dans les 60 prochains jours</option>
                            <option value="90" selected>Dans les 90 prochains jours</option>
                            <option value="expired">Déjà expiré</option>
                        </select>
                    </div>

                    {{-- Specific Filter: Employee (Hidden by default) --}}
                    <div class="col-md-4 specific-filter filter-container" id="filter-employe-container" style="display: none;">
                        <label class="form-label small fw-semibold mb-1">Sélectionner Employé</label>
                        <select class="form-select form-select-sm" id="filter-employe">
                            <option value="">Tous les employés</option>
                            @foreach($employes as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 ms-auto d-flex gap-2">
                        <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                            <i class="bi bi-funnel me-1"></i> Filtrer
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" id="btn-reset-filters" title="Réinitialiser">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Dynamic Data Table --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table id="dynamic-report-table" class="table table-hover align-middle mb-0"></table>
            </div>
        </div>
    </div>
</div>

{{-- PDF Preview Modal --}}
<div class="modal fade" id="pdfPreviewModal" tabindex="-1" aria-labelledby="pdfPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold" id="pdfPreviewModalLabel"><i class="bi bi-file-earmark-pdf me-2"></i>Aperçu PDF - Rapport du Parc</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="pdf-iframe-container">
                    <iframe class="pdf-iframe" id="pdf-preview-iframe" src=""></iframe>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary" id="btn-print-iframe">
                    <i class="bi bi-printer me-1"></i> Imprimer / Télécharger
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $table = $('#dynamic-report-table');
        let currentReportType = 'global_park';

        // Initialize report load
        loadReport(currentReportType);

        // Sidebar Item Click Handler
        $('.report-item').on('click', function(e) {
            e.preventDefault();
            $('.report-item').removeClass('active');
            $(this).addClass('active');

            currentReportType = $(this).data('type');
            const desc = $(this).data('desc');
            
            // Update UI Title and Description
            $('#report-title-display').text($(this).text().trim());
            $('#report-desc-display').text(desc);

            // Configure Filters Visibility based on report attributes
            const showDays = $(this).data('show-days') === true;
            const showEmploye = $(this).data('show-employe') === true;

            // List of reports that don't need general equipment filters
            const isEquipmentReport = [
                'global_park', 'by_status', 'by_state', 'warranty_status', 
                'end_of_life', 'unassigned', 'equip_covered', 'equip_not_covered',
                'type_computers', 'type_physical_servers', 'type_virtual_servers',
                'type_printers', 'type_scanners', 'type_network', 'type_ip_phones',
                'type_mobiles', 'type_cameras', 'type_infra'
            ].includes(currentReportType);

            if (isEquipmentReport) {
                $('.common-filter').show();
            } else {
                $('.common-filter').hide();
            }

            if (showDays) {
                $('#filter-days-container').show();
            } else {
                $('#filter-days-container').hide();
            }

            if (showEmploye) {
                $('#filter-employe-container').show();
            } else {
                $('#filter-employe-container').hide();
            }

            // Reload table
            loadReport(currentReportType);
        });

        // Trigger Filters
        $('#btn-apply-filters').on('click', function() {
            $table.bootstrapTable('refresh');
        });

        // Reset Filters
        $('#btn-reset-filters').on('click', function() {
            $('#filters-form select').val('');
            $('#filter-days').val('90'); // default expired value
            $table.bootstrapTable('refresh');
        });

        // Load Report definition and build table
        function loadReport(reportType) {
            // Retrieve report columns metadata from server first
            $.ajax({
                url: "{{ route('parc-info.analyse.etats.data') }}",
                data: {
                    report_type: reportType,
                    limit: 1 // Fetch only 1 record to inspect columns metadata
                },
                success: function(res) {
                    let columns = [];
                    // Build columns mapping
                    for (let field in res.columns) {
                        columns.push({
                            field: field,
                            title: res.columns[field],
                            sortable: true,
                            formatter: function(value, row) {
                                return tableCellFormatter(field, value, row);
                            }
                        });
                    }

                    // Destroy old table and load new layout
                    $table.bootstrapTable('destroy');
                    $table.bootstrapTable({
                        columns: columns,
                        url: "{{ route('parc-info.analyse.etats.data') }}",
                        pagination: true,
                        sidePagination: 'server',
                        search: true,
                        showRefresh: true,
                        showColumns: true,
                        pageList: [10, 25, 50, 100],
                        pageSize: 10,
                        queryParams: function(params) {
                            return {
                                limit: params.limit,
                                offset: params.offset,
                                search: params.search,
                                sort: params.sort,
                                order: params.order,
                                report_type: reportType,
                                direction_id: $('#filter-direction').val(),
                                service_id: $('#filter-service').val(),
                                statut: $('#filter-statut').val(),
                                etat: $('#filter-etat').val(),
                                days: $('#filter-days').val(),
                                employe_id: $('#filter-employe').val()
                            };
                        }
                    });
                }
            });
        }

        // JS Cell Formatter to replicate status & states styling
        function tableCellFormatter(field, value, row) {
            if (value === null || value === undefined) {
                return '-';
            }

            if (field === 'statut') {
                const classes = {
                    'en_stock': 'bg-secondary',
                    'en_service': 'bg-success',
                    'en_reparation': 'bg-warning text-dark',
                    'perdu': 'bg-danger',
                    'reforme': 'bg-dark'
                };
                const labels = {
                    'en_stock': 'En stock',
                    'en_service': 'En service',
                    'en_reparation': 'En réparation',
                    'perdu': 'Perdu',
                    'reforme': 'Réformé'
                };
                return `<span class="badge ${classes[value] || 'bg-light'}">${labels[value] || value}</span>`;
            }

            if (field === 'etat') {
                const classes = {
                    'bon': 'bg-success-subtle text-success border border-success-subtle',
                    'passable': 'bg-info-subtle text-info border border-info-subtle',
                    'mauvais': 'bg-warning-subtle text-warning border border-warning-subtle',
                    'avarie': 'bg-danger-subtle text-danger border border-danger-subtle'
                };
                const labels = {
                    'bon': 'Bon',
                    'passable': 'Passable',
                    'mauvais': 'Mauvais',
                    'avarie': 'Avarié'
                };
                return `<span class="badge ${classes[value] || 'bg-light'} px-2.5 py-1 text-uppercase">${labels[value] || value}</span>`;
            }

            // Currency formatting
            if (['valeur_achat', 'cout', 'cout_unitaire', 'cout_total', 'valeur_totale'].includes(field)) {
                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF' }).format(value);
            }

            // Date formatting
            if (value && typeof value === 'string' && value.match(/^\d{4}-\d{2}-\d{2}$/)) {
                const dateParts = value.split('-');
                return `${dateParts[2]}/${dateParts[1]}/${dateParts[0]}`;
            }

            return value;
        }

        // Export Actions Click Listener
        $('.export-action').on('click', function(e) {
            e.preventDefault();
            const format = $(this).data('format');
            
            // Build URL query parameters
            const params = $.param({
                report_type: currentReportType,
                format: format,
                direction_id: $('#filter-direction').val(),
                service_id: $('#filter-service').val(),
                statut: $('#filter-statut').val(),
                etat: $('#filter-etat').val(),
                days: $('#filter-days').val(),
                employe_id: $('#filter-employe').val()
            });

            const exportUrl = "{{ route('parc-info.analyse.etats.export') }}?" + params;

            if (format === 'pdf') {
                // Open iframe modal for PDF viewing
                $('#pdf-preview-iframe').attr('src', exportUrl);
                const pdfModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
                pdfModal.show();
            } else {
                // Direct file download for CSV and Excel
                window.location.href = exportUrl;
            }
        });

        // Print PDF from Iframe
        $('#btn-print-iframe').on('click', function() {
            const iframe = document.getElementById('pdf-preview-iframe');
            if (iframe) {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }
        });
    });
</script>
@endpush
