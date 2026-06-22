@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Dictionnaires')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Référentiels</li>
    <li class="breadcrumb-item active">Dictionnaires</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Liste des Dictionnaires</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            @can('parc-info.referentiels.dictionnaires.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter un dictionnaire">
                <i class="fas fa-plus me-1"></i> Nouveau
            </button>
            @endcan
            @can('parc-info.referentiels.dictionnaires.update')
            <button id="btn-edit" class="btn btn-info text-white" disabled data-bs-toggle="tooltip" title="Modifier le dictionnaire">
                <i class="fas fa-edit me-1"></i> Modifier
            </button>
            @endcan
            @can('parc-info.referentiels.dictionnaires.manage')
            <button id="btn-manage" class="btn btn-success" disabled data-bs-toggle="tooltip" title="Gérer les valeurs du dictionnaire">
                <i class="fas fa-list me-1"></i> Gérer les valeurs
            </button>
            @endcan
            @can('parc-info.referentiels.dictionnaires.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer le dictionnaire">
                <i class="fas fa-trash me-1"></i> Supprimer
            </button>
            @endcan
        </div>
        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('parc-info.referentiels.dictionnaires.data') }}"
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
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="description" data-sortable="true">Description</th>
                    <th data-field="valeurs_count" data-sortable="true" class="text-center">Nombre de valeurs</th>
                    <th data-field="is_system" data-formatter="systemFormatter" class="text-center">Type</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::referentiels.dictionnaires._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.systemFormatter = function (value) {
        return value
            ? '<span class="badge bg-secondary">Système</span>'
            : '<span class="badge bg-primary">Personnalisé</span>';
    };
</script>
<script type="module" src="{{ asset('js/modules/parc-info/referentiels/dictionnaires.js') }}?v={{ time() }}"></script>
@endpush
