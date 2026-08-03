@extends('stock::layouts.master')

@section('header', 'Magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item active" aria-current="page">Magasins</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-shop me-2 text-primary"></i>Magasins (un par site — D2)</h6>
        <div style="width: 180px;">
            <select class="form-select form-select-sm" id="filter-statut">
                <option value="">Tous les statuts</option>
                <option value="actif">Actifs</option>
                <option value="inactif">Inactifs</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">

        <div id="toolbar">
            @can('stock.magasins.store')
            <button id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('stock.magasins.index')
            <button id="btn-show" class="btn btn-secondary btn-sm" disabled data-bs-toggle="tooltip" title="Voir la fiche">
                <i class="fas fa-eye"></i>
            </button>
            @endcan
            @can('stock.magasins.update')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('stock.magasins.toggle-status')
            <button id="btn-toggle" class="btn btn-warning btn-sm" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
            @can('stock.magasins.destroy')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
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
               data-locale="fr-FR"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="site">Site</th>
                    <th data-field="local">Local</th>
                    <th data-field="responsable">Responsable</th>
                    <th data-field="nb_references" data-sortable="true" data-align="center">Nb références</th>
                    <th data-field="est_actif" data-sortable="true" data-formatter="statutFormatter" data-align="center">Statut</th>
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
