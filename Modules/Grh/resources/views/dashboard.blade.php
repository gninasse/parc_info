@extends('grh::layouts.master')

@section('header', 'Tableau de bord GRH')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">GRH Dashboard</li>
@endsection

@section('content')
<div class="row g-4 mb-4">
    {{-- Total Employés --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 overflow-hidden">
            <div class="card-body position-relative">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0">TOTAL EMPLOYÉS</h6>
                    <div class="bg-primary-subtle text-primary rounded-pill px-3 py-1">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1">{{ $stats['total_employes'] }}</h2>
                <p class="text-muted small mb-0">Dans tout l'établissement</p>
                <div class="position-absolute bottom-0 end-0 opacity-10 p-3" style="font-size: 4rem;">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Actifs vs Inactifs --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="text-muted fw-bold mb-0">STATUT ACTIF</h6>
                    <div class="bg-success-subtle text-success rounded-pill px-3 py-1">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <h2 class="fw-bold mb-1 text-success">{{ $stats['actifs'] }}</h2>
                <div class="progress mt-3" style="height: 6px;">
                    @php
                        $pct = $stats['total_employes'] > 0 ? ($stats['actifs'] / $stats['total_employes']) * 100 : 0;
                    @endphp
                    <div class="progress-bar bg-success" style="width: {{ $pct }}%"></div>
                </div>
                <p class="text-muted small mt-2 mb-0">Soit {{ round($pct) }}% de l'effectif total</p>
            </div>
        </div>
    </div>

    {{-- Hommes vs Femmes --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="text-muted fw-bold mb-3">RÉPARTITION GENRE</h6>
                <div class="d-flex align-items-center justify-content-around mt-2">
                    <div class="text-center">
                        <div class="text-info fs-3 fw-bold">{{ $stats['hommes'] }}</div>
                        <div class="small text-muted"><i class="fas fa-mars me-1"></i> Hommes</div>
                    </div>
                    <div class="vr"></div>
                    <div class="text-center">
                        <div class="text-danger fs-3 fw-bold" style="color: #e83e8c !important;">{{ $stats['femmes'] }}</div>
                        <div class="small text-muted"><i class="fas fa-venus me-1"></i> Femmes</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Postes/Rattachements --}}
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100 bg-primary text-white">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-white-50">ACTIONS RAPIDES</h6>
                <div class="d-grid gap-2">
                    <a href="{{ route('grh.employes.index') }}" class="btn btn-light btn-sm text-start py-2">
                        <i class="fas fa-list me-2"></i> Liste des dossiers
                    </a>
                    <a href="{{ route('grh.employes.index') }}?action=add" class="btn btn-outline-light btn-sm text-start py-2">
                        <i class="fas fa-user-plus me-2"></i> Nouvel employé
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Répartition par niveau --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0 fw-bold">Répartition par Niveau de Rattachement</h5>
            </div>
            <div class="card-body py-4">
                <div class="d-flex flex-column gap-3">
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Directions</span>
                            <span class="fw-bold">{{ $stats['par_niveau']['direction'] }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php $pctD = $stats['total_employes'] > 0 ? ($stats['par_niveau']['direction'] / $stats['total_employes']) * 100 : 0; @endphp
                            <div class="progress-bar bg-primary" style="width: {{ $pctD }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Services</span>
                            <span class="fw-bold">{{ $stats['par_niveau']['service'] }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php $pctS = $stats['total_employes'] > 0 ? ($stats['par_niveau']['service'] / $stats['total_employes']) * 100 : 0; @endphp
                            <div class="progress-bar bg-info" style="width: {{ $pctS }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Unités</span>
                            <span class="fw-bold">{{ $stats['par_niveau']['unite'] }}</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            @php $pctU = $stats['total_employes'] > 0 ? ($stats['par_niveau']['unite'] / $stats['total_employes']) * 100 : 0; @endphp
                            <div class="progress-bar bg-warning" style="width: {{ $pctU }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Récemment ajoutés --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="card-title mb-0 fw-bold">Derniers Employés Enregistrés</h5>
            </div>
            <div class="card-body p-0">
                @php
                    $recents = \Modules\Grh\Models\Employe::latest()->take(5)->get();
                @endphp
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 px-4 py-3">Employé</th>
                                <th class="border-0 py-3">Poste</th>
                                <th class="border-0 text-end px-4 py-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recents as $emp)
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-light text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user small"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold small">{{ $emp->full_name }}</div>
                                                <div class="text-muted x-small" style="font-size: 0.7rem;">{{ $emp->matricule }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="small">{{ $emp->poste ?: '-' }}</td>
                                    <td class="text-end px-4 small text-muted">{{ $emp->created_at->format('d/m/Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aucun employé enregistré.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($recents->count() > 0)
            <div class="card-footer bg-white text-center py-3 border-0">
                <a href="{{ route('grh.employes.index') }}" class="btn btn-link btn-sm text-decoration-none fw-bold">
                    VOIR TOUS LES DOSSIERS <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
