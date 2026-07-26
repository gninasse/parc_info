@extends('stock::layouts.master')

@section('title', 'Gestion des Magasins - Stock')
@section('header', 'Magasins Logiques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
    <li class="breadcrumb-item active">Magasins</li>
@endsection

@section('push_css')
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

{{-- Filtres externes --}}
<div class="card card-filter mb-3 rounded-1 shadow-sm">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-type">Type de Magasin</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    <option value="TECHNIQUE">Technique</option>
                    <option value="CONSOMMABLE">Consommable</option>
                    <option value="REBUT">Rebut</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-status">Statut</label>
                <select class="form-select form-select-sm" id="filter-status">
                    <option value="">Tous les statuts</option>
                    <option value="true">Actif</option>
                    <option value="false">Inactif</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-1 rounded-1 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-warehouse me-2 text-primary"></i>Liste des magasins logiques</h6>
    </div>
    <div class="card-body p-0">

        {{-- Toolbar --}}
        <div id="toolbar" class="d-flex gap-1">
            @can('stock.magasins.create')
            <button id="btn-add" class="btn btn-primary btn-sm rounded-1" data-bs-toggle="tooltip" title="Ajouter un magasin">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('stock.magasins.edit')
            <button id="btn-edit" class="btn btn-info text-white btn-sm rounded-1" disabled data-bs-toggle="tooltip" title="Modifier le magasin sélectionné">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('stock.magasins.admin')
            <button id="btn-manage-responsibles" class="btn btn-warning text-white btn-sm rounded-1" disabled data-bs-toggle="tooltip" title="Gérer les responsables">
                <i class="fas fa-user-shield"></i> Responsables
            </button>
            <button id="btn-manage-rights" class="btn btn-secondary btn-sm rounded-1" disabled data-bs-toggle="tooltip" title="Gérer les droits d'accès">
                <i class="fas fa-lock"></i> Droits
            </button>
            @endcan
            @can('stock.magasins.edit')
            <button id="btn-delete" class="btn btn-danger btn-sm rounded-1" disabled data-bs-toggle="tooltip" title="Supprimer le magasin sélectionné">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="magasins-table"
               data-toggle="table"
               data-url="{{ route('stock.magasins.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="10"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="id" data-sortable="true">ID</th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="nom" data-sortable="true">Libellé</th>
                    <th data-field="type" data-sortable="true">Type</th>
                    <th data-field="est_actif" data-formatter="statutFormatter">Actif</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::magasins._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/stock/magasins/index.js') }}?v={{ time() }}"></script>
@endpush
