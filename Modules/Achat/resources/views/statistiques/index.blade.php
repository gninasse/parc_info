@extends('achat::layouts.master')

@section('title', 'États & Statistiques - Achats')
@section('header', 'États & Statistiques des Achats')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">États & Statistiques</li>
@endsection

@push('css')
<style>
    .kpi-card-custom {
        border: none;
        border-radius: 12px;
        color: white;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .kpi-card-custom:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }
    .bg-gradient-blue {
        background: linear-gradient(135deg, hsl(210, 95%, 48%), hsl(195, 90%, 42%));
    }
    .bg-gradient-green {
        background: linear-gradient(135deg, hsl(145, 80%, 40%), hsl(160, 75%, 32%));
    }
    .bg-gradient-purple {
        background: linear-gradient(135deg, hsl(265, 80%, 45%), hsl(280, 75%, 38%));
    }
    .bg-gradient-orange {
        background: linear-gradient(135deg, hsl(32, 95%, 48%), hsl(18, 95%, 42%));
    }
    .chart-container-custom {
        position: relative;
        height: 250px;
        width: 100%;
    }
    .report-table-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .pdf-modal-iframe {
        width: 100%;
        height: 70vh;
        border: none;
        border-radius: 4px;
    }
</style>
@endpush

@section('content')
    {{-- ── 1. KPI CARDS ROW ── --}}
    <div class="row g-3 mb-4">
        {{-- Total Commandes --}}
        <div class="col-md-3">
            <div class="card kpi-card-custom bg-gradient-blue h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Total Commandes</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $totalCommandes }}</h2>
                        </div>
                        <div class="bg-white bg-opacity-20 text-white rounded-3 p-3 fs-3">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Toutes commandes confondues</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Montant Cumulé --}}
        <div class="col-md-3">
            <div class="card kpi-card-custom bg-gradient-green h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Montant Cumulé</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0" style="font-size: 1.6rem;">
                                {{ number_format($totalMontantAchats, 0, ',', ' ') }} FCFA
                            </h2>
                        </div>
                        <div class="bg-white bg-opacity-20 text-white rounded-3 p-3 fs-3">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Sur commandes validées</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Complétion Livraisons --}}
        <div class="col-md-3">
            <div class="card kpi-card-custom bg-gradient-purple h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Complétion Livraisons</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $completionRate }} %</h2>
                        </div>
                        <div class="bg-white bg-opacity-20 text-white rounded-3 p-3 fs-3">
                            <i class="bi bi-truck"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Lignes totalement livrées</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fournisseurs Actifs --}}
        <div class="col-md-3">
            <div class="card kpi-card-custom bg-gradient-orange h-100 shadow-sm">
                <div class="card-body p-4 d-flex flex-column justify-content-between">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="text-white-50 text-uppercase fw-semibold tracking-wider small">Fournisseurs Actifs</span>
                            <h2 class="text-white fw-bold display-6 mt-1 mb-0">{{ $fournisseurs->count() }}</h2>
                        </div>
                        <div class="bg-white bg-opacity-20 text-white rounded-3 p-3 fs-3">
                            <i class="bi bi-shop"></i>
                        </div>
                    </div>
                    <div class="pt-3 border-top border-white border-opacity-10 mt-auto">
                        <span class="text-white-50 small">Fournisseurs référencés</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 2. CHARTS SECTION ── --}}
    <div class="row g-3 mb-4">
        {{-- Dépenses par Mois --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Dépenses Mensuelles</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container-custom">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Part par Fournisseur --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-pie-chart me-2 text-success"></i>Dépenses par Fournisseur (Top 5)</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container-custom">
                        <canvas id="supplierChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Part par Type d'Article --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box me-2 text-warning"></i>Articles par Type</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container-custom">
                        <canvas id="articleTypeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 3. FILTERING & REPORT GENERATION SECTION ── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-2">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-filter-left me-2 text-primary"></i>Sélection et Paramètres du Rapport</h5>
        </div>
        <div class="card-body pt-2">
            <form id="filter-form">
                <div class="row g-3">
                    {{-- Type de rapport --}}
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted">Type de rapport</label>
                        <select name="report_type" id="report_type" class="form-select form-select-sm" required>
                            <option value="global_purchases">Rapport Global des Bons de Commande</option>
                            <option value="by_supplier_detail">Rapport de Dépenses par Fournisseur</option>
                            <option value="reliquats">Rapport des Reliquats de Livraison</option>
                            <option value="popular_articles">Articles les plus commandés</option>
                        </select>
                    </div>

                    {{-- Fournisseur --}}
                    <div class="col-md-3 filter-group" id="group-supplier">
                        <label class="form-label small fw-bold text-muted">Fournisseur</label>
                        <select name="fournisseur_id" id="fournisseur_id" class="form-select form-select-sm">
                            <option value="">Tous les fournisseurs</option>
                            @foreach($fournisseurs as $f)
                                <option value="{{ $f->id }}">{{ $f->nom }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Statut --}}
                    <div class="col-md-2 filter-group" id="group-status">
                        <label class="form-label small fw-bold text-muted">Statut</label>
                        <select name="statut" id="statut" class="form-select form-select-sm">
                            <option value="">Tous les statuts</option>
                            <option value="brouillon">Brouillon</option>
                            <option value="valide">Validé</option>
                            <option value="partiel">Partiel</option>
                            <option value="livre">Livré</option>
                            <option value="annule">Annulé</option>
                        </select>
                    </div>

                    {{-- Date début --}}
                    <div class="col-md-2 filter-group" id="group-start-date">
                        <label class="form-label small fw-bold text-muted">Date Début</label>
                        <input type="date" name="date_debut" id="date_debut" class="form-control form-control-sm">
                    </div>

                    {{-- Date fin --}}
                    <div class="col-md-2 filter-group" id="group-end-date">
                        <label class="form-label small fw-bold text-muted">Date Fin</label>
                        <input type="date" name="date_fin" id="date_fin" class="form-control form-control-sm">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" id="btn-filter" class="btn btn-sm btn-primary px-4 rounded-1">
                        <i class="fas fa-search me-1"></i> Rechercher
                    </button>
                    <button type="button" id="btn-print" class="btn btn-sm btn-outline-success px-4 rounded-1">
                        <i class="fas fa-print me-1"></i> Afficher / Imprimer PDF
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── 4. REPORT DATA TABLE ── --}}
    <div class="card border-0 shadow-sm report-table-card mb-4" id="data-card" style="display: none;">
        <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold text-dark mb-0" id="table-title">Données du Rapport</h6>
            <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1.5" id="row-count">0 lignes</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small" id="report-table">
                    <thead class="table-light">
                        <tr id="table-header">
                            {{-- Dynamically generated --}}
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        {{-- Dynamically generated --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── 5. PRINT PDF MODAL WITH IFRAME ── --}}
    <div class="modal fade" id="printPdfModal" tabindex="-1" aria-labelledby="printPdfModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0 text-primary py-3">
                    <h5 class="modal-title fw-bold" id="printPdfModalLabel">
                        <i class="fas fa-file-pdf me-2 text-danger"></i>Visualisation et Impression du Rapport
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="pdf-viewer-iframe" class="pdf-modal-iframe" src=""></iframe>
                </div>
                <div class="modal-footer bg-light border-0 py-2">
                    <button type="button" class="btn btn-sm btn-secondary rounded-1 px-3" data-bs-dismiss="modal">Fermer</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle filters depending on report type
        $('#report_type').on('change', function() {
            const report = $(this).val();
            $('.filter-group').show();

            if (report === 'by_supplier_detail') {
                $('#group-status').hide();
                $('#group-start-date').hide();
                $('#group-end-date').hide();
            } else if (report === 'reliquats') {
                $('#group-status').hide();
                $('#group-start-date').hide();
                $('#group-end-date').hide();
            } else if (report === 'popular_articles') {
                $('#group-supplier').hide();
                $('#group-status').hide();
                $('#group-start-date').hide();
                $('#group-end-date').hide();
            }
        });

        // ── CHARTS ──
        // 1. Monthly Chart
        const monthData = @json($expendituresByMonth);
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: monthData.map(m => m.label),
                datasets: [{
                    label: 'Dépenses',
                    data: monthData.map(m => m.total),
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    borderColor: '#0d6efd',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        // 2. Supplier Chart
        const supplierData = @json($expendituresBySupplier);
        new Chart(document.getElementById('supplierChart'), {
            type: 'doughnut',
            data: {
                labels: supplierData.map(s => s.label),
                datasets: [{
                    data: supplierData.map(s => s.total),
                    backgroundColor: ['#198754', '#20c997', '#ffc107', '#dc3545', '#0d6efd']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } }
            }
        });

        // 3. Article Type Chart
        const articleTypeData = @json($articlesByType);
        new Chart(document.getElementById('articleTypeChart'), {
            type: 'pie',
            data: {
                labels: articleTypeData.map(a => a.label),
                datasets: [{
                    data: articleTypeData.map(a => a.count),
                    backgroundColor: ['#fd7e14', '#0dcaf0', '#6f42c1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } } }
            }
        });

        // ── AJAX FILTER & DATA TABLE ──
        $('#btn-filter').on('click', function() {
            const formData = $('#filter-form').serialize();
            $.ajax({
                url: "{{ route('achat.statistiques.data') }}",
                method: 'GET',
                data: formData,
                success: function(res) {
                    if (res.success) {
                        $('#table-title').text(res.title);
                        $('#row-count').text(res.rows.length + ' lignes');

                        // Clean table header
                        const headerTr = $('#table-header').empty();
                        const columnsKeys = Object.keys(res.columns);
                        columnsKeys.forEach(key => {
                            headerTr.append($('<th>').text(res.columns[key]));
                        });

                        // Clean table body
                        const body = $('#table-body').empty();
                        res.rows.forEach(row => {
                            const tr = $('<tr>');
                            columnsKeys.forEach(key => {
                                tr.append($('<td>').text(row[key]));
                            });
                            body.append(tr);
                        });

                        $('#data-card').fadeIn(300);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Erreur',
                        text: 'Impossible de récupérer les données du rapport.'
                    });
                }
            });
        });

        // Trigger filter initially to display default report
        $('#btn-filter').trigger('click');

        // ── PRINT PDF IN MODAL IFRAME ──
        $('#btn-print').on('click', function() {
            const formData = $('#filter-form').serialize();
            const pdfUrl = "{{ route('achat.statistiques.pdf') }}?" + formData;

            // Set iframe src to PDF route
            $('#pdf-viewer-iframe').attr('src', pdfUrl);

            // Show printing modal
            const printModal = new bootstrap.Modal(document.getElementById('printPdfModal'));
            printModal.show();
        });
    });
</script>
@endpush
