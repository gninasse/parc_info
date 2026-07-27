@extends('stock::layouts.master')

@section('title', 'Alertes - Stocks')
@section('header', 'Alertes et notifications')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Alertes</li>
@endsection

@section('content')

<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-bell me-2 text-primary"></i>Mes notifications de stock</h6>
        <button id="btn-lire-tout" class="btn btn-sm btn-outline-secondary rounded-1">
            <i class="fas fa-check-double me-1"></i>Tout marquer comme lu
        </button>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush" id="alertes-liste">
            <li class="list-group-item text-center text-muted py-4">Chargement…</li>
        </ul>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('js/modules/stock/alertes/index.js') }}?v={{ time() }}"></script>
@endpush
