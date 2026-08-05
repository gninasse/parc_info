@extends('stock::layouts.master')

@section('header', 'États & rapports')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard') }}">Stock</a></li>
    <li class="breadcrumb-item active" aria-current="page">États & rapports</li>
@endsection

@push('css')
<style>
    .carte-etat { transition: transform .15s ease, box-shadow .15s ease; border: 1px solid rgba(0,0,0,.05); }
    .carte-etat:hover { transform: translateY(-4px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08) !important; }
    .carte-etat .icone {
        width: 46px; height: 46px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center; font-size: 1.35rem;
    }
    .famille-titre { letter-spacing: .06em; }
</style>
@endpush

@section('content')

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="fw-bold mb-1"><i class="bi bi-journals me-2 text-primary"></i>Bibliothèque des états</h5>
            <p class="text-muted mb-0 small">
                Chaque état se consulte à l'écran, se filtre, puis s'exporte à l'identique en CSV, Excel ou PDF.
                Les filtres retenus sont imprimés en tête de chaque export, et le PDF porte le cartouche d'audit.
            </p>
        </div>
        <a href="{{ route('stock.statistiques.index') }}" class="btn btn-primary">
            <i class="bi bi-bar-chart-line me-1"></i>Tableau de bord statistique
        </a>
    </div>
</div>

@php
    $couleurs = ['Photo du stock' => 'primary', 'Registres de flux' => 'success', 'Analyses' => 'info'];
@endphp

@foreach($catalogue as $famille => $etats)
    <h6 class="text-uppercase text-muted fw-bold small famille-titre mb-3">{{ $famille }}</h6>
    <div class="row g-3 mb-4">
        @foreach($etats as $etat)
            @php $couleur = $couleurs[$famille] ?? 'primary'; @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card border-0 shadow-sm h-100 carte-etat position-relative">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-3">
                            <div class="icone bg-{{ $couleur }}-subtle text-{{ $couleur }} flex-shrink-0">
                                <i class="bi {{ $etat['icone'] }}"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1">{{ $etat['titre'] }}</h6>
                                <p class="text-muted small mb-0">{{ $etat['intention'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pt-0 d-flex justify-content-between align-items-center">
                        <span class="small text-muted">
                            <i class="bi bi-funnel me-1"></i>{{ count($etat['filtres']) }} filtre(s)
                        </span>
                        <span class="small fw-semibold text-{{ $couleur }}">Ouvrir <i class="bi bi-arrow-right"></i></span>
                        <a href="{{ route('stock.rapports.show', $etat['code']) }}" class="stretched-link"
                           aria-label="Ouvrir l'état {{ $etat['titre'] }}"></a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach

@endsection
