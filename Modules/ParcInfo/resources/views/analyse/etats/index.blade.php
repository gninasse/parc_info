@extends('parcinfo::layouts.master')

@section('header', 'États des Équipements')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Analyse</li>
    <li class="breadcrumb-item active">États des Équipements</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .filter-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-left: 4px solid #0d6efd;
    }
</style>
@endpush

@section('content')
{{-- Filters --}}
<div class="card border-0 shadow-sm mb-3 filter-card">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-tag me-1"></i> Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_stock">En stock</option>
                    <option value="en_service">En service</option>
                    <option value="en_reparation">En réparation</option>
                    <option value="perdu">Perdu</option>
                    <option value="reforme">Réformé</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1"><i class="bi bi-heart me-1"></i> État Physique</label>
                <select class="form-select form-select-sm" id="filter-etat">
                    <option value="">Tous les états</option>
                    <option value="bon">Bon</option>
                    <option value="passable">Passable</option>
                    <option value="mauvais">Mauvais</option>
                    <option value="avarie">Avarié</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btn-reset-filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Data Table --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-list-stars me-2"></i>Suivi des Équipements</h6>
    </div>
    <div class="card-body p-0">
        <table id="etats-table"
               data-toggle="table"
               data-url="{{ route('parc-info.analyse.etats.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-click-to-select="true"
               data-page-list="[10,25,50,100]"
               data-page-size="10"
               data-query-params="etatsQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="code_inventaire" data-sortable="true" class="fw-bold text-primary">Code Inventaire</th>
                    <th data-field="numero_serie" data-sortable="true">N° Série</th>
                    <th data-field="marque_libelle" data-sortable="true" data-field-sort="marque">Marque</th>
                    <th data-field="modele" data-sortable="true">Modèle</th>
                    <th data-field="statut" data-sortable="true" data-formatter="statutBadgeFormatter">Statut</th>
                    <th data-field="etat" data-sortable="true" data-formatter="etatBadgeFormatter">État Physique</th>
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
    window.etatsQueryParams = function(params) {
        return {
            limit: params.limit,
            offset: params.offset,
            search: params.search,
            sort: params.sort,
            order: params.order,
            statut: $('#filter-statut').val(),
            etat: $('#filter-etat').val()
        };
    };

    window.statutBadgeFormatter = function(value, row) {
        const classes = {
            'en_stock': 'bg-secondary',
            'en_service': 'bg-success',
            'en_reparation': 'bg-warning text-dark',
            'perdu': 'bg-danger',
            'reforme': 'bg-dark'
        };
        return `<span class="badge ${classes[value] || 'bg-light'}">${row.statut_label}</span>`;
    };

    window.etatBadgeFormatter = function(value, row) {
        const classes = {
            'bon': 'bg-success-subtle text-success border border-success-subtle',
            'passable': 'bg-info-subtle text-info border border-info-subtle',
            'mauvais': 'bg-warning-subtle text-warning border border-warning-subtle',
            'avarie': 'bg-danger-subtle text-danger border border-danger-subtle'
        };
        return `<span class="badge ${classes[value] || 'bg-light'} px-2.5 py-1 text-uppercase">${row.etat_label}</span>`;
    };

    document.addEventListener('DOMContentLoaded', function() {
        const $table = $('#etats-table');

        $('#btn-apply-filters').on('click', function() {
            $table.bootstrapTable('refresh');
        });

        $('#btn-reset-filters').on('click', function() {
            $('#filter-statut, #filter-etat').val('');
            $table.bootstrapTable('refresh');
        });
    });
</script>
@endpush
