@extends('stock::layouts.master')

@section('title', 'Tableau de Bord - Stock')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-chart-line me-2 text-primary"></i>Tableau de Bord des Stocks
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                </ol>
            </nav>
        </div>
        <div class="text-secondary small fw-semibold">
            <i class="far fa-clock me-1"></i> Données actualisées le : {{ now()->format('d/m/Y à H:i') }}
        </div>
    </div>

    <!-- Cards Stats Grid -->
    <div class="row g-3 mb-4">
        <!-- Magasins Card -->
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-1 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-info-subtle p-3 text-info me-3">
                        <i class="fas fa-warehouse fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small text-uppercase fw-semibold">Magasins Actifs</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalMagasins }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Valeur Card -->
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-1 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-success-subtle p-3 text-success me-3">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small text-uppercase fw-semibold">Valeur du Stock</h6>
                        <h4 class="mb-0 fw-bold text-dark">{{ number_format($totalValue, 2, ',', ' ') }} F</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mouvements Card -->
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-1 h-100 bg-white">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle bg-primary-subtle p-3 text-primary me-3">
                        <i class="fas fa-exchange-alt fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small text-uppercase fw-semibold">Mouvements Totaux</h6>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalMovements }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertes Card -->
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-1 h-100 {{ $alertCount > 0 ? 'bg-danger-subtle' : 'bg-white' }}">
                <div class="card-body d-flex align-items-center p-3">
                    <div class="rounded-circle p-3 me-3 {{ $alertCount > 0 ? 'bg-danger text-white pulse-alert' : 'bg-secondary-subtle text-secondary' }}">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <h6 class="text-secondary mb-1 small text-uppercase fw-semibold">Stocks Critiques</h6>
                        <h3 class="mb-0 fw-bold {{ $alertCount > 0 ? 'text-danger' : 'text-dark' }}">{{ $alertCount }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Panels -->
    <div class="row g-3">
        <!-- Recent Movements Panel -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-1 h-100 bg-white">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fas fa-history me-2 text-primary"></i> Mouvements Récents
                    </h5>
                    <span class="badge bg-light text-secondary border small">5 derniers</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Article</th>
                                    <th>Magasin</th>
                                    <th class="text-end">Quantité</th>
                                    <th class="text-center">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentMovements as $m)
                                    <tr>
                                        <td>
                                            @if($m->type_mouvement === 'ENTREE')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-arrow-down me-1"></i> Entrée</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-arrow-up me-1"></i> Sortie</span>
                                            @endif
                                        </td>
                                        <td><strong>{{ $m->article?->designation }}</strong><br><small class="text-muted">{{ $m->article?->code_article }}</small></td>
                                        <td>{{ $m->magasin?->nom }}</td>
                                        <td class="text-end fw-bold">{{ $m->quantite }}</td>
                                        <td class="text-center text-secondary small">{{ $m->created_at?->format('d/m H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Aucun mouvement récent enregistré.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Critical Stocks Panel -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-1 h-100 bg-white">
                <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark mb-0">
                        <i class="fas fa-bell me-2 text-danger"></i> Alertes Niveaux Bas
                    </h5>
                    @if($alertCount > 0)
                        <span class="badge bg-danger text-white blink-alert">CRITIQUE</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @forelse($criticalStocks as $stock)
                            @php
                                $seuil = $stock->article?->seuil_alerte ?? 5;
                                $percentage = $seuil > 0 ? min(100, round(($stock->quantite_actuelle / $seuil) * 100)) : 0;
                            @endphp
                            <div class="border rounded-1 p-2 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold text-dark">{{ $stock->article?->designation }}</span>
                                    <span class="badge bg-white text-danger border border-danger-subtle">{{ $stock->magasin?->nom }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center small text-secondary mb-1">
                                    <span>Stock Actuel : <strong>{{ $stock->quantite_actuelle }}</strong></span>
                                    <span>Seuil : <strong>{{ $seuil }}</strong></span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ $percentage }}%;" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-check-circle fa-3x text-success mb-2"></i>
                                <p class="mb-0 small">Tous les niveaux de stock sont au-dessus des seuils critiques !</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-info-subtle { background-color: rgba(13, 202, 240, 0.15); }
    .bg-success-subtle { background-color: rgba(25, 135, 84, 0.15); }
    .bg-primary-subtle { background-color: rgba(13, 110, 253, 0.15); }
    .bg-danger-subtle { background-color: rgba(220, 53, 69, 0.15); }
    
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
    }
    .pulse-alert {
        animation: pulse 2s infinite;
    }
    
    @keyframes blink {
        50% { opacity: 0.5; }
    }
    .blink-alert {
        animation: blink 1.5s infinite;
    }
</style>
@endsection
