@extends('parcinfo::layouts.master')

@section('header', 'Parc Informatique — Dashboard')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Parc Informatique</li>
    <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')

    {{-- ── Ligne 1 : KPI principaux ── --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-primary">
                <div class="inner">
                    <h3>{{ $stats['total_equipements'] }}</h3>
                    <p>Équipements total</p>
                </div>
                <i class="small-box-icon bi bi-pc-display-horizontal"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Plus d'info <i class="bi bi-link-45deg"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-success">
                <div class="inner">
                    <h3>{{ $stats['en_service'] }}</h3>
                    <p>En service</p>
                </div>
                <i class="small-box-icon bi bi-check-circle"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Plus d'info <i class="bi bi-link-45deg"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-warning">
                <div class="inner">
                    <h3>{{ $stats['en_maintenance'] }}</h3>
                    <p>En maintenance</p>
                </div>
                <i class="small-box-icon bi bi-tools"></i>
                <a href="#" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                    Plus d'info <i class="bi bi-link-45deg"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box text-bg-danger">
                <div class="inner">
                    <h3>{{ $stats['hors_service'] }}</h3>
                    <p>Hors service</p>
                </div>
                <i class="small-box-icon bi bi-x-circle"></i>
                <a href="#" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                    Plus d'info <i class="bi bi-link-45deg"></i>
                </a>
            </div>
        </div>
    </div>

    {{-- ── Ligne 2 : Répartition par type + Alertes ── --}}
    <div class="row">
        {{-- Répartition par type --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="bi bi-pie-chart me-2"></i>Répartition par type</h3>
                </div>
                <div class="card-body">
                    @foreach($repartitionParType as $type)
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3 fs-4 text-{{ $type['color'] }}">
                                <i class="{{ $type['icon'] }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-semibold">{{ $type['label'] }}</span>
                                    <span class="text-muted">{{ $type['count'] }} <small>({{ $type['percent'] }}%)</small></span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-{{ $type['color'] }}" role="progressbar"
                                         style="width: {{ $type['percent'] }}%"
                                         aria-valuenow="{{ $type['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Alertes & indicateurs --}}
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="bi bi-exclamation-triangle me-2"></i>Alertes & Indicateurs</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-shield-exclamation text-danger me-2"></i>Garanties expirées</span>
                            <span class="badge bg-danger rounded-pill">{{ $stats['garantie_expiree'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-arrow-repeat text-warning me-2"></i>Renouvellements prévus (90j)</span>
                            <span class="badge bg-warning text-dark rounded-pill">{{ $stats['renouvellement_prevu'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-pc-display text-primary me-2"></i>Postes de travail</span>
                            <span class="badge bg-primary rounded-pill">{{ $stats['postes_travail'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-server text-info me-2"></i>Serveurs</span>
                            <span class="badge bg-info text-dark rounded-pill">{{ $stats['serveurs'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-printer text-success me-2"></i>Imprimantes</span>
                            <span class="badge bg-success rounded-pill">{{ $stats['imprimantes'] }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-router text-secondary me-2"></i>Équipements réseau</span>
                            <span class="badge bg-secondary rounded-pill">{{ $stats['equipements_reseau'] }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Ligne 3 : Derniers équipements ── --}}
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title"><i class="bi bi-clock-history me-2"></i>Derniers équipements enregistrés</h3>
                    <div class="card-tools">
                        <a href="#" class="btn btn-tool btn-sm">
                            <i class="bi bi-list"></i> Voir tout
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Libellé</th>
                                    <th>Type</th>
                                    <th>Site</th>
                                    <th>Statut</th>
                                    <th>Enregistré</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentEquipements as $eq)
                                    <tr>
                                        <td><code>{{ $eq['code'] }}</code></td>
                                        <td>{{ $eq['libelle'] }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $eq['type'] }}</span></td>
                                        <td><small class="text-muted">{{ $eq['site'] }}</small></td>
                                        <td>
                                            <span class="badge bg-{{ $eq['statut_color'] }}">{{ $eq['statut'] }}</span>
                                        </td>
                                        <td><small class="text-muted">{{ $eq['date'] }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('css'){{-- aucun CSS supplémentaire requis --}}@endpush
@push('js'){{-- aucun JS requis pour les données statiques --}}@endpush
