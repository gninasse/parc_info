@extends('achat::layouts.master')

@section('title', 'Stocks Consommables - Achat')
@section('header', 'Stock Consommables (Achat)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Stock Consommables</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .card-filter {
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
</style>
@endpush

@section('content')

{{-- ── CARD DE FILTRES RECHERCHE ── --}}
<div class="card card-filter mb-3 rounded-1">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-statut-stock">Statut Stock</label>
                <select class="form-select form-select-sm" id="filter-statut-stock">
                    <option value="">Tous les niveaux</option>
                    <option value="alerte">En alerte (≤ Seuil)</option>
                    <option value="ok">Correct (>&nbsp;Seuil)</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── COMPONENT: TABLE CARD ── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>Stock Consommables (Catalogue Achat)</h6>
    </div>
    <div class="card-body p-0">
        <table id="stocks-table"
               data-toggle="table"
               data-url="{{ route('achat.stocks.index') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="code_article" data-sortable="true" class="fw-semibold">Code Article</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="marque" data-sortable="true">Marque</th>
                    <th data-field="stock_actuel" data-sortable="true" class="text-center">Stock Actuel</th>
                    <th data-field="seuil_alerte" data-sortable="true" class="text-center">Seuil Alerte</th>
                    <th data-field="status_badge" class="text-center">Alerte / Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const $table = $('#stocks-table');

        // Filtre
        $('#filter-statut-stock').on('change', function() {
            $table.bootstrapTable('refresh');
        });

        $table.bootstrapTable('refreshOptions', {
            queryParams: function(params) {
                params.statut_stock = $('#filter-statut-stock').val();
                return params;
            }
        });
    });
</script>
@endpush
