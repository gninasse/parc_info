@extends('parcinfo::layouts.master')

@section('header', 'Tableau de Bord Statistique')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Analyse</li>
    <li class="breadcrumb-item active">Statistiques</li>
@endsection

@push('css')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
    }
    .chart-container {
        position: relative;
        height: 280px;
        width: 100%;
    }
</style>
@endpush

@section('content')
{{-- Loader --}}
<div id="stats-loader" class="text-center py-5">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Calcul des indicateurs...</span>
    </div>
    <h5 class="mt-3 text-muted fw-semibold">Calcul et agrégation des statistiques en cours...</h5>
</div>

{{-- Dashboard Grid --}}
<div id="stats-content" style="display: none;">
    {{-- KPI Cards Row 1 --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Valeur d'Achat Totale --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Valeur Achat Parc</h6>
                        <h4 class="fw-bold mb-0" id="stat-val-achat">0 FCFA</h4>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 2: Valeur Résiduelle --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-success-subtle text-success me-3">
                        <i class="bi bi-graph-down-arrow"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Valeur Résiduelle Est.</h6>
                        <h4 class="fw-bold mb-0" id="stat-val-residuelle">0 FCFA</h4>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 3: Taux de Vétusté --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-danger-subtle text-danger me-3">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Taux de Vétusté</h6>
                        <h4 class="fw-bold mb-0" id="stat-vetuste">0 %</h4>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 4: Taux de Disponibilité --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-warning-subtle text-warning me-3">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Taux Disponibilité</h6>
                        <h4 class="fw-bold mb-0" id="stat-dispo">0 %</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards Row 2 --}}
    <div class="row g-3 mb-4">
        {{-- Card 5: Equipements Moyen --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-info-subtle text-info me-3">
                        <i class="bi bi-people"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Moy. Équip. / Employé</h6>
                        <h4 class="fw-bold mb-0" id="stat-moy-emp">0</h4>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 6: Total Licences --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-secondary-subtle text-secondary me-3">
                        <i class="bi bi-key-fill"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Licences Actives</h6>
                        <h4 class="fw-bold mb-0" id="stat-licences">0</h4>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 8: Budget Informatique Estimé --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Budget IT Estimé</h6>
                        <h4 class="fw-bold mb-0" id="stat-budget">0 FCFA</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section 1 --}}
    <div class="row g-4 mb-4">
        {{-- Types Doughnut Chart --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart me-2"></i>Répartition par Type</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container">
                        <canvas id="typesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Bar Chart --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart me-2"></i>Statut de Disponibilité</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- States Polar Area Chart --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-activity me-2"></i>État Physique du Parc</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container">
                        <canvas id="statesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section 2 --}}
    <div class="row g-4 mb-4">
        {{-- Top Directions Value Chart --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-building me-2"></i>Valeur du Parc par Direction (Top 5)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="directionsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Financial breakdown Chart --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart-fill me-2"></i>Répartition Financière de Fonctionnement</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="financeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section 3 --}}
    <div class="row g-4">
        {{-- Maintenance KPI list --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-tools me-2"></i>Maintenance & Réparations</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush mb-0">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Durée Moyenne de Réparation</h6>
                                <small class="text-muted">Temps écoulé avant remise en service</small>
                            </div>
                            <span class="badge bg-warning text-dark fs-6 px-3 py-2" id="maint-avg-days">0 jours</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Taux de Récurrence de Pannes</h6>
                                <small class="text-muted">Équipements passés plus d'une fois en réparation</small>
                            </div>
                            <span class="badge bg-danger fs-6 px-3 py-2" id="maint-recurrence">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Changements de statuts/états</h6>
                                <small class="text-muted">Total des transactions enregistrées</small>
                            </div>
                            <span class="badge bg-secondary fs-6 px-3 py-2" id="maint-state-changes">0</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Équipements Réformés</h6>
                                <small class="text-muted">Matériels déclassés et mis au rebut</small>
                            </div>
                            <span class="badge bg-dark fs-6 px-3 py-2" id="maint-reforms">0</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Licences & Logiciels --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-key me-2"></i>Licences & Logiciels</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush mb-0">
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Taux de couverture des Licences</h6>
                                <small class="text-muted">Nombre de postes utilisés vs postes accordés</small>
                            </div>
                            <span class="badge bg-info text-white fs-6 px-3 py-2" id="lic-usage-rate">0 %</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                            <div>
                                <h6 class="mb-0 fw-semibold">Nombre de Logiciels Référencés</h6>
                                <small class="text-muted">Catalogue logiciels et progiciels</small>
                            </div>
                            <span class="badge bg-secondary fs-6 px-3 py-2" id="lic-soft-count">0</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $.ajax({
            url: "{{ route('parc-info.analyse.statistiques.data') }}",
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    const formatFCFA = (val) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(val);

                    // 1. Populate summary cards
                    $('#stat-val-achat').text(formatFCFA(res.summary.total_val_purchase));
                    $('#stat-val-residuelle').text(formatFCFA(res.summary.total_residual_value));
                    $('#stat-vetuste').text(res.summary.vetuste_rate + ' %');
                    $('#stat-dispo').text(res.summary.availability_rate + ' %');
                    $('#stat-moy-emp').text(res.summary.avg_equip_per_employee);
                    $('#stat-licences').text(res.compliance.licences_count);
                    $('#stat-budget').text(formatFCFA(res.summary.estimated_budget));

                    // 2. Populate maintenance & consumable KPIs
                    $('#maint-avg-days').text(res.maintenance.avg_days + ' jours');
                    $('#maint-recurrence').text(res.maintenance.recurrences);
                    $('#maint-state-changes').text(res.maintenance.state_changes);
                    $('#maint-reforms').text(res.maintenance.reformed_count);
                    $('#lic-usage-rate').text(res.compliance.global_usage + ' %');
                    $('#lic-soft-count').text(res.compliance.logiciels_count);

                    // 3. Render Chart 1: Types Doughnut Chart
                    const typesLabels = res.types_stats.map(t => t.label);
                    const typesData = res.types_stats.map(t => t.count);
                    new Chart(document.getElementById('typesChart'), {
                        type: 'doughnut',
                        data: {
                            labels: typesLabels,
                            datasets: [{
                                data: typesData,
                                backgroundColor: [
                                    '#0d6efd', '#198754', '#20c997', '#0dcaf0', 
                                    '#ffc107', '#fd7e14', '#dc3545', '#6610f2', 
                                    '#6f42c1', '#adb5bd'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
                            }
                        }
                    });

                    // 4. Render Chart 2: Status Bar Chart
                    const statusLabels = res.status_stats.map(s => s.label);
                    const statusData = res.status_stats.map(s => s.count);
                    new Chart(document.getElementById('statusChart'), {
                        type: 'bar',
                        data: {
                            labels: statusLabels,
                            datasets: [{
                                label: 'Équipements',
                                data: statusData,
                                backgroundColor: ['#6c757d', '#198754', '#ffc107', '#dc3545', '#212529']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false } },
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });

                    // 5. Render Chart 3: States Polar Area Chart
                    const statesLabels = res.state_stats.map(s => s.label);
                    const statesData = res.state_stats.map(s => s.count);
                    new Chart(document.getElementById('statesChart'), {
                        type: 'polarArea',
                        data: {
                            labels: statesLabels,
                            datasets: [{
                                data: statesData,
                                backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
                            }
                        }
                    });

                    // 6. Render Chart 4: Directions Value Bar Chart
                    const dirLabels = res.directions_list.map(d => d.label);
                    const dirPurchaseData = res.directions_list.map(d => d.value);
                    const dirResidualData = res.directions_list.map(d => d.residual_value);
                    new Chart(document.getElementById('directionsChart'), {
                        type: 'bar',
                        data: {
                            labels: dirLabels,
                            datasets: [
                                {
                                    label: 'Valeur Achat',
                                    data: dirPurchaseData,
                                    backgroundColor: '#0d6efd'
                                },
                                {
                                    label: 'Valeur Résiduelle',
                                    data: dirResidualData,
                                    backgroundColor: '#20c997'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });

                    // 7. Render Chart 5: Finance Chart
                    new Chart(document.getElementById('financeChart'), {
                        type: 'pie',
                        data: {
                            labels: ['Contrats Maintenance', 'Licences Logicielles Actives'],
                            datasets: [{
                                data: [
                                    res.finances.contracts_cost,
                                    res.finances.licenses_cost
                                ],
                                backgroundColor: ['#6f42c1', '#0d6efd']
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { position: 'right', labels: { boxWidth: 12 } }
                            }
                        }
                    });

                    // Hide loader and show content
                    $('#stats-loader').hide();
                    $('#stats-content').fadeIn(400);
                }
            },
            error: function() {
                $('#stats-loader').html('<h5 class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Impossible de charger les statistiques du parc.</h5>');
            }
        });
    });
</script>
@endpush
