@extends('stock::layouts.master')

@section('title', 'Valorisation & Historique des Stocks')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-hand-holding-usd me-2 text-success"></i>Valorisation & Instantanés
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Valorisation</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('stock.valorisation.create')
                <button type="button" class="btn btn-success btn-sm rounded-1 text-white" id="btn-add-snapshot">
                    <i class="fas fa-calculator me-1"></i> Calculer valorisation (Ponctuel)
                </button>
            @endcan
        </div>
    </div>

    <!-- Filtres & Table -->
    <div class="card border-0 shadow-sm rounded-1">
        <!-- Filtres externes -->
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filter-type">
                        <option value="">Tous les types</option>
                        <option value="MENSUEL">Mensuel (Automatique)</option>
                        <option value="PONCTUEL">Ponctuel (Manuel)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Toolbar Bootstrap Table -->
            <div id="valorisation-toolbar" class="d-flex gap-1">
                <button class="btn btn-light btn-sm border rounded-1" id="btn-view-snapshot" disabled>
                    <i class="fas fa-eye me-1"></i> Consulter le rapport de valorisation
                </button>
            </div>

            <!-- Table -->
            <table id="valorisation-table"
                   data-toggle="table"
                   data-url="{{ route('stock.valorisation.data') }}"
                   data-side-pagination="server"
                   data-pagination="true"
                   data-search="true"
                   data-toolbar="#valorisation-toolbar"
                   data-show-columns="true"
                   data-show-refresh="true"
                   data-click-to-select="true"
                   data-single-select="true"
                   data-page-size="10"
                   data-page-list="[10, 25, 50]"
                   class="table table-hover align-middle small">
                <thead>
                    <tr>
                        <th data-checkbox="true"></th>
                        <th data-field="reference" data-sortable="true" data-halign="center" data-align="center">Référence</th>
                        <th data-field="type" data-sortable="true" data-formatter="typeFormatter" data-halign="center" data-align="center">Type</th>
                        <th data-field="valeur_totale_globale" data-sortable="true" data-align="right">Valeur Globale</th>
                        <th data-field="created_by">Généré par</th>
                        <th data-field="date_snapshot" data-sortable="true" data-formatter="dateFormatter" data-align="center">Date Snapshot</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('stock::valorisation._modal')

@endsection

@push('scripts')
<script type="module" src="{{ asset('js/modules/stock/valorisation/index.js') }}?v={{ time() }}"></script>
@endpush
