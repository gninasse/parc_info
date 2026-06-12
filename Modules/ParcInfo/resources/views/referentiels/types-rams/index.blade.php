@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Types de RAM')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Référentiels</li>
    <li class="breadcrumb-item active">Types de RAM</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Liste des Types de RAM</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            @can('parc-info.referentiels.types-rams.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('parc-info.referentiels.types-rams.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('parc-info.referentiels.types-rams.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('parc-info.referentiels.types-rams.data') }}"
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
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter">Date création</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::referentiels.types-rams._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/referentiels/types-rams.js') }}?v={{ time() }}"></script>
@endpush