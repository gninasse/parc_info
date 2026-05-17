@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Fournisseurs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Fournisseurs</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Liste des Fournisseurs & Prestataires</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button id="btn-toggle-status" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-toggle-on"></i>
            </button>
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <table id="fournisseurs-table"
               data-toggle="table"
               data-url="{{ route('parc-info.fournisseurs.data') }}"
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
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true" data-formatter="codeFormatter">Code</th>
                    <th data-field="nom" data-sortable="true">Nom de l'entreprise</th>
                    <th data-field="type">Type</th>
                    <th data-field="email">Email</th>
                    <th data-field="telephone">Téléphone</th>
                    <th data-field="status_label">Statut</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.fournisseurs._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/fournisseurs/index.js') }}?v={{ time() }}"></script>
@endpush
