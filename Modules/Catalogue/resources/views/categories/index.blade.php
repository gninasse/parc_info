@extends('core::layouts.master')

@section('header', 'Catégories du catalogue')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Catalogue</li>
    <li class="breadcrumb-item active" aria-current="page">Catégories</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-sitemap me-2 text-primary"></i>Arborescence des catégories</h6>
        <div style="width: 180px;">
            <select class="form-select form-select-sm" id="filter-statut">
                <option value="">Tous les statuts</option>
                <option value="1">Actives</option>
                <option value="0">Inactives</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">

        <div id="toolbar">
            @can('catalogue.categories.store')
            <button id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('catalogue.categories.update')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('catalogue.categories.toggle-status')
            <button id="btn-toggle" class="btn btn-warning btn-sm" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
            @can('catalogue.categories.destroy')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="categories-table"
               data-toggle="table"
               data-url="{{ route('catalogue.categories.data') }}"
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
                    <th data-field="libelle" data-sortable="true" data-formatter="libelleFormatter" data-escape="false">Libellé</th>
                    <th data-field="parent_libelle">Parent</th>
                    <th data-field="nb_articles" data-sortable="true" data-align="center">Articles</th>
                    <th data-field="est_actif" data-sortable="true" data-formatter="statutFormatter" data-align="center">Statut</th>
                    <th data-field="created_at" data-sortable="true">Créée le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('catalogue::categories._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/catalogue/categories/index.js') }}?v={{ time() }}"></script>
@endpush
