@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Catégories d\'Équipement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Référentiels</li>
    <li class="breadcrumb-item active">Catégories d'équipement</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold">Liste des Catégories d'Équipement</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            @can('parc-info.referentiels.categories.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus me-1"></i> Ajouter une catégorie
            </button>
            @endcan
            @can('parc-info.referentiels.categories.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('parc-info.referentiels.categories.index')
            <button id="btn-view" class="btn btn-success" disabled data-bs-toggle="tooltip" title="Configurer les champs">
                <i class="fas fa-cog me-1"></i> Configurer
            </button>
            @endcan
            @can('parc-info.referentiels.categories.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('parc-info.referentiels.categories.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10,25,50,100]"
               data-page-size="10"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="id" data-sortable="true">ID</th>
                    <th data-field="icone" data-formatter="iconeFormatter">Icône</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="champs_count" data-sortable="true">Champs configurés</th>
                    <th data-field="equipements_count" data-sortable="true">Équipements réels</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter">Date création</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::referentiels.categories._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.iconeFormatter = function (value) {
        return `<span class="badge bg-light text-dark p-2 border"><i class="bi ${value || 'bi-cpu'} fs-5"></i></span>`;
    };
</script>
<script type="module" src="{{ asset('js/modules/parc-info/referentiels/categories.js') }}?v={{ time() }}"></script>
@endpush
