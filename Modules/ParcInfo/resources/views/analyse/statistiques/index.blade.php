@extends('parcinfo::layouts.master')

@section('header', 'Statistiques du Parc')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Analyse</li>
    <li class="breadcrumb-item active">Statistiques</li>
@endsection

@push('css')
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08) !important;
    }
    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>
@endpush

@section('content')
{{-- Loader --}}
<div id="stats-loader" class="text-center py-5">
    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
        <span class="visually-hidden">Chargement...</span>
    </div>
    <h5 class="mt-3 text-muted fw-semibold">Calcul des indicateurs en cours...</h5>
</div>

{{-- Stats Dashboard (hidden initially) --}}
<div id="stats-content" style="display: none;">
    {{-- Summary Row --}}
    <div class="row g-3 mb-4">
        {{-- Card 1: Total Equipements --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-primary-subtle text-primary me-3">
                        <i class="bi bi-pc-display"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Équipements Totaux</h6>
                        <h3 class="fw-bold mb-0" id="stat-equipements">0</h3>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 2: Catalog Logiciel --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-info-subtle text-info me-3">
                        <i class="bi bi-journal-code"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Logiciels Réf.</h6>
                        <h3 class="fw-bold mb-0" id="stat-logiciels">0</h3>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 3: Licences --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-success-subtle text-success me-3">
                        <i class="bi bi-file-lock2"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Licences Totales</h6>
                        <h3 class="fw-bold mb-0" id="stat-licences">0</h3>
                    </div>
                </div>
            </div>
        </div>
        {{-- Card 4: Licences Actives --}}
        <div class="col-md-3">
            <div class="card border-0 shadow-sm stat-card h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="card-icon bg-warning-subtle text-warning me-3">
                        <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                        <h6 class="text-muted small mb-1">Licences Actives</h6>
                        <h3 class="fw-bold mb-0" id="stat-licences-actives">0</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Types Distribution --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid-3x3-gap me-2"></i>Répartition par Type de Matériel</h6>
                </div>
                <div class="card-body" id="types-container">
                    {{-- Rendered dynamically --}}
                </div>
            </div>
        </div>

        {{-- Status Distribution --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-info-circle me-2"></i>Statut de Disponibilité</h6>
                </div>
                <div class="card-body" id="status-container">
                    {{-- Rendered dynamically --}}
                </div>
            </div>
        </div>

        {{-- States Distribution --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-heart-pulse me-2"></i>État de Santé des Équipements</h6>
                </div>
                <div class="card-body" id="states-container">
                    {{-- Rendered dynamically --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        $.ajax({
            url: "{{ route('parc-info.analyse.statistiques.data') }}",
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    // 1. Populate summary cards
                    $('#stat-equipements').text(res.summary.total_equipements);
                    $('#stat-logiciels').text(res.summary.total_logiciels);
                    $('#stat-licences').text(res.summary.total_licences);
                    $('#stat-licences-actives').text(res.summary.licences_actives);

                    // 2. Types Stats
                    let typesHtml = '';
                    res.types_stats.forEach(t => {
                        typesHtml += `
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-semibold text-muted">${t.label}</span>
                                    <span class="small fw-bold">${t.count} (${t.percentage}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: ${t.percentage}%;" aria-valuenow="${t.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        `;
                    });
                    $('#types-container').html(typesHtml);

                    // 3. Status Stats
                    let statusHtml = '';
                    const statusColors = {
                        'en_stock': 'bg-secondary',
                        'en_service': 'bg-success',
                        'en_reparation': 'bg-warning',
                        'perdu': 'bg-danger',
                        'reforme': 'bg-dark'
                    };
                    res.status_stats.forEach(s => {
                        let colorClass = statusColors[s.statut] || 'bg-secondary';
                        statusHtml += `
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-semibold text-muted">${s.label}</span>
                                    <span class="small fw-bold">${s.count} (${s.percentage}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${s.percentage}%;" aria-valuenow="${s.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        `;
                    });
                    $('#status-container').html(statusHtml);

                    // 4. States Stats
                    let statesHtml = '';
                    const stateColors = {
                        'bon': 'bg-success',
                        'passable': 'bg-info',
                        'mauvais': 'bg-warning',
                        'avarie': 'bg-danger'
                    };
                    res.state_stats.forEach(st => {
                        let colorClass = stateColors[st.etat] || 'bg-secondary';
                        statesHtml += `
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="small fw-semibold text-muted">${st.label}</span>
                                    <span class="small fw-bold">${st.count} (${st.percentage}%)</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar ${colorClass}" role="progressbar" style="width: ${st.percentage}%;" aria-valuenow="${st.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        `;
                    });
                    $('#states-container').html(statesHtml);

                    // Hide loader, show dashboard
                    $('#stats-loader').hide();
                    $('#stats-content').fadeIn(300);
                }
            },
            error: function() {
                $('#stats-loader').html('<h5 class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>Impossible de charger les statistiques.</h5>');
            }
        });
    });
</script>
@endpush
