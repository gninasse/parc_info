@extends('achat::layouts.master')

@section('title', 'Suivi des stocks - Achat')
@section('header', 'Suivi du stock des consommables')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Suivi des stocks</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- EF-STK-05 : l'écran est consultatif et le rappelle explicitement. --}}
<div class="alert alert-light border rounded-1 d-flex align-items-start gap-3 py-2 px-3 mb-3">
    <i class="fas fa-info-circle text-primary mt-1"></i>
    <div class="small text-muted">
        Les quantités présentées ici reflètent les réceptions enregistrées par le module Achat.
        Les mouvements de stock (sorties, transferts, inventaires) et la valorisation sont tenus
        par le module Stock, qui constitue le référentiel de référence.
    </div>
</div>

<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-niveau">Niveau de stock</label>
                <select class="form-select form-select-sm" id="filter-niveau">
                    <option value="">Tous les niveaux</option>
                    <option value="rupture">En rupture</option>
                    <option value="alerte">Sous le seuil d'alerte</option>
                    <option value="normal">Stock correct</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>Consommables du catalogue</h6>
    </div>
    <div class="card-body p-0">
        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('achat.stocks.data') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="code_article" data-sortable="true" class="fw-semibold">Code article</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="marque" data-sortable="true">Marque</th>
                    <th data-field="unite_mesure">Unité</th>
                    <th data-field="stock_actuel" data-sortable="true" class="text-center">Stock actuel</th>
                    <th data-field="seuil_alerte" data-sortable="true" class="text-center">Seuil d'alerte</th>
                    <th data-field="niveau" data-formatter="niveauStockFormatter" class="text-center">Niveau</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/stocks/index.js') }}?v={{ time() }}"></script>
@endpush
