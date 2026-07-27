@extends('stock::layouts.master')

@section('title', 'Magasins - Stocks')
@section('header', 'Gestion des magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Magasins</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-store me-2 text-primary"></i>Liste des magasins</h6>
    </div>
    <div class="card-body p-0">

        {{-- DESIGN.md : barre d'outils strictement en icônes, avec infobulle. --}}
        <div id="toolbar" class="d-flex gap-1">
            @can('stock.magasins.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Créer un magasin">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('stock.magasins.edit')
            <button id="btn-edit" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Modifier le magasin sélectionné">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('stock.magasins.admin')
            <button id="btn-responsables" class="btn btn-sm btn-secondary rounded-1" disabled
                    data-bs-toggle="tooltip" title="Gérer les responsables">
                <i class="fas fa-users"></i>
            </button>
            <button id="btn-toggle" class="btn btn-sm btn-warning text-dark rounded-1" disabled
                    data-bs-toggle="tooltip" title="Activer ou désactiver">
                <i class="fas fa-toggle-on"></i>
            </button>
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Supprimer le magasin sélectionné">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="magasins-table"
               data-toggle="table"
               data-url="{{ route('stock.magasins.data') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true" class="fw-semibold">Code</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="responsable_principal" data-formatter="responsableFormatter">Responsable principal</th>
                    <th data-field="articles_count" data-sortable="true" class="text-center">Articles</th>
                    <th data-field="statut" data-sortable="true" data-formatter="magasinStatutFormatter" class="text-center">Statut</th>
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
<script src="{{ asset('js/modules/stock/magasins/index.js') }}?v={{ time() }}"></script>
@endpush
