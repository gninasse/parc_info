@extends('parcinfo::layouts.master')

@section('header', 'Parc Informatique — Tableau de Bord')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Parc Informatique</li>
    <li class="breadcrumb-item active" aria-current="page">Tableau de Bord</li>
@endsection

@section('content')

    {{-- ── Ligne 1 : Cartes KPI principales (HSL Gradients) ── --}}
    <div class="row g-4 mb-4">
        {{-- Total Équipements --}}
        <div class="col-xl-3 col-sm-6">
            <div class="card dashboard-card kpi-card kpi-blue h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Total Actifs</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $stats['total_equipements'] }}</h2>
                        </div>
                        <div class="icon-badge bg-white bg-opacity-20 text-white fs-3">
                            <i class="bi bi-pc-display-horizontal"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Inventaire physique global</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- En Service --}}
        <div class="col-xl-3 col-sm-6">
            <div class="card dashboard-card kpi-card kpi-green h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">En Production</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $stats['en_service'] }}</h2>
                        </div>
                        <div class="icon-badge bg-white bg-opacity-20 text-white fs-3">
                            <i class="bi bi-check-circle"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Actifs en exploitation active</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- En Maintenance --}}
        <div class="col-xl-3 col-sm-6">
            <div class="card dashboard-card kpi-card kpi-orange h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">En Réparation</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $stats['en_maintenance'] }}</h2>
                        </div>
                        <div class="icon-badge bg-white bg-opacity-20 text-white fs-3">
                            <i class="bi bi-tools"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Actifs en atelier ou panne</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- En Stock --}}
        <div class="col-xl-3 col-sm-6">
            <div class="card dashboard-card kpi-card kpi-gray h-100 shadow-sm">
                <div class="card-body d-flex flex-column justify-content-between p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Disponibles</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $stats['en_stock'] }}</h2>
                        </div>
                        <div class="icon-badge bg-white bg-opacity-20 text-white fs-3">
                            <i class="bi bi-archive"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Matériel en réserve</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ligne 2 : Répartition par type & Centre d'Alertes Critiques ── --}}
    <div class="row g-4 mb-4">
        {{-- Ventilation par Catégories --}}
        <div class="col-lg-6">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title fw-bold text-dark mb-0">
                            <i class="bi bi-pie-chart text-primary me-2"></i>Répartition du Matériel
                        </h5>
                        <span class="badge bg-light text-muted border px-2 py-1 small">Filtres dynamiques</span>
                    </div>
                </div>
                <div class="card-body px-4 py-3">
                    @if($stats['total_equipements'] > 0)
                        <div class="d-flex flex-column gap-4">
                            @foreach($repartitionParType as $type)
                                <div class="d-flex align-items-center">
                                    <div class="icon-badge bg-{{ $type['color'] }} bg-opacity-10 text-{{ $type['color'] }} fs-4 me-3">
                                        <i class="{{ $type['icon'] }}"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <a href="{{ $type['route'] }}" class="text-dark fw-semibold text-decoration-none hover-link">
                                                {{ $type['label'] }}
                                            </a>
                                            <span class="text-muted fw-bold small">
                                                {{ $type['count'] }} <small class="fw-normal">({{ $type['percent'] }}%)</small>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px; border-radius: 4px; background-color: #f1f3f5;">
                                            <div class="progress-bar progress-bar-glow bg-{{ $type['color'] }}" role="progressbar"
                                                 style="width: {{ $type['percent'] }}%; border-radius: 4px;"
                                                 aria-valuenow="{{ $type['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="d-flex flex-column align-items-center justify-content-center h-100 py-5">
                            <div class="fs-1 text-muted"><i class="bi bi-activity"></i></div>
                            <p class="text-muted mt-2">Aucun équipement enregistré pour calculer la répartition.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Alertes & indicateurs --}}
        <div class="col-lg-6">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-shield-exclamation text-danger me-2"></i>Alertes & Risques Système
                    </h5>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="d-flex flex-column gap-3">
                        {{-- Alerte Licences Expirées --}}
                        <div class="d-flex align-items-center justify-content-between p-3 glass-alert {{ $stats['licences_expirees'] > 0 ? 'pulse-danger' : '' }}">
                            <div class="d-flex align-items-center">
                                <div class="icon-badge bg-danger bg-opacity-10 text-danger fs-4 me-3">
                                    <i class="bi bi-x-octagon-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark">Licences Expirées</h6>
                                    <span class="text-muted small">Arrêt immédiat de validité</span>
                                </div>
                            </div>
                            <span class="badge {{ $stats['licences_expirees'] > 0 ? 'bg-danger' : 'bg-light text-muted border' }} rounded-pill px-3 py-2 fs-6 fw-bold">
                                {{ $stats['licences_expirees'] }}
                            </span>
                        </div>

                        {{-- Alerte Garanties Expirées --}}
                        <div class="d-flex align-items-center justify-content-between p-3 glass-alert">
                            <div class="d-flex align-items-center">
                                <div class="icon-badge bg-secondary bg-opacity-10 text-secondary fs-4 me-3">
                                    <i class="bi bi-shield-slash-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark">Garanties Expirées</h6>
                                    <span class="text-muted small">Matériel hors couverture de maintenance</span>
                                </div>
                            </div>
                            <span class="badge {{ $stats['garantie_expiree'] > 0 ? 'bg-dark' : 'bg-light text-muted border' }} rounded-pill px-3 py-2 fs-6 fw-bold">
                                {{ $stats['garantie_expiree'] }}
                            </span>
                        </div>

                        {{-- Alerte Renouvellements garanties --}}
                        <div class="d-flex align-items-center justify-content-between p-3 glass-alert">
                            <div class="d-flex align-items-center">
                                <div class="icon-badge bg-info bg-opacity-10 text-info fs-4 me-3">
                                    <i class="bi bi-alarm-fill"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-semibold text-dark">Garanties expirant bientôt (90j)</h6>
                                    <span class="text-muted small">Renouvellements contractuels prévisibles</span>
                                </div>
                            </div>
                            <span class="badge {{ $stats['renouvellement_prevu'] > 0 ? 'bg-info text-dark' : 'bg-light text-muted border' }} rounded-pill px-3 py-2 fs-6 fw-bold">
                                {{ $stats['renouvellement_prevu'] }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ligne 2.5 : Graphiques Interactifs ── --}}
    <div class="row g-4 mb-4">
        {{-- Graphique des Statuts --}}
        <div class="col-lg-6">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-bar-chart-fill text-primary me-2"></i>Statuts des Actifs
                    </h5>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        {{-- Graphique des États Physiques --}}
        <div class="col-lg-6">
            <div class="card dashboard-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <h5 class="card-title fw-bold text-dark mb-0">
                        <i class="bi bi-activity text-success me-2"></i>État Physique du Parc
                    </h5>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="chart-container" style="position: relative; height: 260px; width: 100%;">
                        <canvas id="statesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ligne 3 : Derniers équipements enregistrés ── --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card dashboard-card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title fw-bold text-dark mb-0">
                            <i class="bi bi-clock-history text-primary me-2"></i>Derniers Équipements Enregistrés
                        </h5>
                        <div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">Actualisation automatique</span>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 py-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light border-0">
                                <tr>
                                    <th class="border-0 rounded-start">Code Inventaire</th>
                                    <th class="border-0">Désignation</th>
                                    <th class="border-0">Catégorie</th>
                                    <th class="border-0">Site géographique</th>
                                    <th class="border-0">Statut</th>
                                    <th class="border-0 rounded-end">Date d'ajout</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentEquipements as $eq)
                                    <tr>
                                        <td>
                                            <a href="{{ $eq['detail_route'] }}" class="fw-bold text-primary text-decoration-none">
                                                <code>{{ $eq['code'] }}</code>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="icon-badge bg-{{ $eq['type_color'] }} bg-opacity-10 text-{{ $eq['type_color'] }} fs-5 me-2 py-1 px-2 rounded">
                                                    <i class="{{ $eq['type_icon'] }}"></i>
                                                </div>
                                                <span class="fw-semibold">{{ $eq['libelle'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $eq['type_color'] }} bg-opacity-10 text-{{ $eq['type_color'] }} border border-{{ $eq['type_color'] }} border-opacity-20 px-2 py-1">
                                                {{ $eq['type'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i>{{ $eq['site'] }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $eq['statut_color'] }}-subtle text-{{ $eq['statut_color'] }} border border-{{ $eq['statut_color'] }}-subtle px-2.5 py-1.5 rounded">
                                                {{ $eq['statut'] }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>{{ $eq['date'] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="border-0">
                                            <div class="empty-state my-4">
                                                <div class="fs-1 text-muted mb-2"><i class="bi bi-box-seam"></i></div>
                                                <h6 class="fw-bold text-dark">Aucun matériel trouvé dans la base</h6>
                                                <p class="text-muted small max-w-md mx-auto mb-0">
                                                    Aucun équipement, licence ou consommable n'a été enregistré pour le moment.
                                                    Dès que vous en ajouterez dans l'un des modules, ils apparaîtront ici.
                                                </p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css')
<style>
    .dashboard-card {
        border: none;
        border-radius: 16px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow: hidden;
        position: relative;
    }
    .dashboard-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.08) !important;
    }
    .kpi-card {
        color: white;
        background-size: 200% 200%;
        animation: gradientBG 10s ease infinite;
    }
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .kpi-blue {
        background: linear-gradient(135deg, hsl(210, 95%, 55%), hsl(195, 90%, 45%));
    }
    .kpi-green {
        background: linear-gradient(135deg, hsl(145, 80%, 42%), hsl(160, 75%, 33%));
    }
    .kpi-orange {
        background: linear-gradient(135deg, hsl(32, 95%, 53%), hsl(18, 95%, 48%));
    }
    .kpi-gray {
        background: linear-gradient(135deg, hsl(215, 20%, 52%), hsl(215, 15%, 38%));
    }
    .glass-alert {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(222, 226, 230, 0.5);
        border-radius: 12px;
        transition: all 0.2s ease;
    }
    .glass-alert:hover {
        background: rgba(255, 255, 255, 0.95);
        transform: scale(1.008);
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
    }
    .pulse-danger {
        animation: dangerPulse 2s infinite;
    }
    @keyframes dangerPulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .icon-badge {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .hover-link:hover {
        color: var(--bs-primary) !important;
        text-decoration: underline !important;
    }
    .empty-state {
        padding: 40px;
        text-align: center;
        border-radius: 16px;
        background: #f8f9fa;
        border: 2px dashed #dee2e6;
    }
</style>
@endpush
@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Status Chart
        const statusData = @json($statusStats);
        const statusLabels = statusData.map(s => s.label);
        const statusCounts = statusData.map(s => s.count);
        const statusColors = statusData.map(s => s.color);

        new Chart(document.getElementById('statusChart'), {
            type: 'bar',
            data: {
                labels: statusLabels,
                datasets: [{
                    label: 'Nombre d\'actifs',
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderWidth: 0,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // 2. States Chart
        const statesData = @json($etatStats);
        const statesLabels = statesData.map(e => e.label);
        const statesCounts = statesData.map(e => e.count);
        const statesColors = statesData.map(e => e.color);

        new Chart(document.getElementById('statesChart'), {
            type: 'doughnut',
            data: {
                labels: statesLabels,
                datasets: [{
                    data: statesCounts,
                    backgroundColor: statesColors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                },
                cutout: '60%'
            }
        });
    });
</script>
@endpush
