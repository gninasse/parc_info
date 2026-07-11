@extends('achat::layouts.master')

@section('title', 'Tableau de bord - Achats')
@section('header', 'Tableau de bord Achats & Approvisionnement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Tableau de bord</li>
@endsection

@section('content')

{{-- ── KPI CARDS ── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-2 bg-primary bg-opacity-10 p-3"><i class="fas fa-cubes fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Catalogue Articles</div>
                    <div class="fw-bold fs-4">{{ $totalArticles }}</div>
                    <div class="small text-muted">{{ $stockAlerts }} en alerte de stock</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-2 bg-success bg-opacity-10 p-3"><i class="fas fa-file-invoice-dollar fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Bons de Commande</div>
                    <div class="fw-bold fs-4">{{ $totalBC }}</div>
                    <div class="small text-muted">{{ $bcValide }} validé(s), {{ $bcBrouillon }} brouillon(s)</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-2 bg-info bg-opacity-10 p-3"><i class="fas fa-shipping-fast fs-4 text-info"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Bordereaux de Livraison</div>
                    <div class="fw-bold fs-4">{{ $totalBL }}</div>
                    <div class="small text-muted">{{ $blValide }} validé(s), {{ $blBrouillon }} en cours</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-2 bg-danger bg-opacity-10 p-3"><i class="fas fa-exclamation-triangle fs-4 text-danger"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Alertes Stock</div>
                    <div class="fw-bold fs-4 text-danger">{{ $stockAlerts }}</div>
                    <div class="small text-muted">Consommables sous le seuil</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── QUICK LINK ACTION PANELS ── --}}
<div class="row g-3 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4 hover-shadow">
            <div class="card-body">
                <div class="fs-1 text-primary mb-3"><i class="fas fa-plus-circle"></i></div>
                <h6 class="fw-bold">Nouveau Bon de Commande</h6>
                <p class="small text-muted mb-3">Enregistrer un nouveau BC fournisseur avec ses articles.</p>
                <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-sm btn-primary rounded-1">Créer un BC</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4 hover-shadow">
            <div class="card-body">
                <div class="fs-1 text-success mb-3"><i class="fas fa-truck-loading"></i></div>
                <h6 class="fw-bold">Enregistrer une Livraison</h6>
                <p class="small text-muted mb-3">Saisir un bordereau de livraison lié à un BC existant.</p>
                <a href="{{ route('achat.bordereaux.create') }}" class="btn btn-sm btn-success text-white rounded-1">Créer un BL</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4 hover-shadow">
            <div class="card-body">
                <div class="fs-1 text-info mb-3"><i class="fas fa-clipboard-list"></i></div>
                <h6 class="fw-bold">Catalogue des Articles</h6>
                <p class="small text-muted mb-3">Gérer la liste des références d'équipements, licences et consommables.</p>
                <a href="{{ route('achat.articles.index') }}" class="btn btn-sm btn-info text-white rounded-1">Voir les articles</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4 hover-shadow">
            <div class="card-body">
                <div class="fs-1 text-warning mb-3"><i class="fas fa-magic"></i></div>
                <h6 class="fw-bold">Assistant d'Intégration</h6>
                <p class="small text-muted mb-3">Associer les codes inventaires et S/N pour intégrer dans ParcInfo.</p>
                <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-warning text-white rounded-1">Lancer l'assistant</a>
            </div>
        </div>
    </div>
</div>

{{-- ── GRAPHICS ROW ── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-line me-2 text-primary"></i>Évolution Mensuelle des Dépenses (FCFA)</h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="monthlyExpendituresChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-chart-pie me-2 text-primary"></i>Répartition par Fournisseur (Top 5)</h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="suppliersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── RECENT ORDERS & RECENT BLS ── --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Dernières Commandes</h6>
                <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-xs btn-outline-secondary rounded-1">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>N° Commande</th>
                                <th>Fournisseur</th>
                                <th class="text-end">Montant</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recentBcs = \Modules\Achat\Models\BonCommande::with('fournisseur')->latest()->limit(5)->get();
                            @endphp
                            @forelse($recentBcs as $rbc)
                                <tr>
                                    <td><a href="{{ route('achat.bons-commande.show', $rbc->id) }}" class="fw-bold">{{ $rbc->numero_commande }}</a></td>
                                    <td>{{ $rbc->fournisseur->nom }}</td>
                                    <td class="text-end fw-semibold">{{ number_format($rbc->montant_total, 0, ',', ' ') }} FCFA</td>
                                    <td class="text-center">
                                        @if($rbc->statut === 'brouillon')
                                            <span class="badge bg-secondary">Brouillon</span>
                                        @elseif($rbc->statut === 'valide')
                                            <span class="badge bg-primary">Validé</span>
                                        @elseif($rbc->statut === 'partiel')
                                            <span class="badge bg-info text-white">Partiel</span>
                                        @elseif($rbc->statut === 'livre')
                                            <span class="badge bg-success">Livré</span>
                                        @elseif($rbc->statut === 'annule')
                                            <span class="badge bg-danger">Annulé</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Aucune commande enregistrée.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast me-2 text-primary"></i>Dernières Livraisons</h6>
                <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-xs btn-outline-secondary rounded-1">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>N° Livraison</th>
                                <th>BC Associé</th>
                                <th>Bordereau Physique</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $recentBls = \Modules\Achat\Models\BordereauLivraison::with('bonCommande')->latest()->limit(5)->get();
                            @endphp
                            @forelse($recentBls as $rbl)
                                <tr>
                                    <td><a href="{{ route('achat.bordereaux.show', $rbl->id) }}" class="fw-bold">{{ $rbl->numero_livraison }}</a></td>
                                    <td>{{ $rbl->bonCommande->numero_commande }}</td>
                                    <td>{{ $rbl->ref_bordereau_physique }}</td>
                                    <td class="text-center">
                                        @if($rbl->statut === 'brouillon')
                                            <span class="badge bg-secondary">Brouillon</span>
                                        @elseif($rbl->statut === 'wizard')
                                            <span class="badge bg-warning text-white">Wizard</span>
                                        @elseif($rbl->statut === 'valide')
                                            <span class="badge bg-success">Intégré</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Aucune livraison enregistrée.</td>
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

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Monthly Expenditures Chart (Bar/Line)
        const expendituresData = @json($expendituresByMonth);
        const expendituresLabels = expendituresData.map(d => d.label);
        const expendituresValues = expendituresData.map(d => d.total);

        new Chart(document.getElementById('monthlyExpendituresChart'), {
            type: 'line',
            data: {
                labels: expendituresLabels,
                datasets: [{
                    label: 'Dépenses totales',
                    data: expendituresValues,
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    borderColor: 'rgb(37, 99, 235)',
                    borderWidth: 2,
                    tension: 0.1,
                    fill: true
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
                            callback: function(value) {
                                return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(value);
                            }
                        }
                    }
                }
            }
        });

        // 2. Top Suppliers Chart (Doughnut)
        const suppliersData = @json($expendituresBySupplier);
        const suppliersLabels = suppliersData.map(d => d.label);
        const suppliersValues = suppliersData.map(d => d.total);

        new Chart(document.getElementById('suppliersChart'), {
            type: 'doughnut',
            data: {
                labels: suppliersLabels,
                datasets: [{
                    data: suppliersValues,
                    backgroundColor: [
                        '#2563eb',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { boxWidth: 12, font: { size: 10 } }
                    }
                }
            }
        });
    });
</script>
@endpush
