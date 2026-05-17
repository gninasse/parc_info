@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Logiciels')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Logiciels</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}">
@endpush

@section('content')

{{-- ── Filtres ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Éditeur</label>
                <select class="form-select form-select-sm" id="filter-editeur">
                    <option value="">Tous les éditeurs</option>
                    @foreach($editeurs as $e)
                        <option value="{{ $e->id }}">{{ $e->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Type de licence</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    @foreach($typesLicences as $t)
                        <option value="{{ $t->id }}">{{ $t->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Appliquer
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btn-reset-filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Liste du Catalogue Logiciel</h6>
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
        <table id="logiciels-table"
               data-toggle="table"
               data-url="{{ route('parc-info.logiciels.data') }}"
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
               data-query-params="logicielsQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true" data-formatter="codeFormatter">Code</th>
                    <th data-field="nom" data-sortable="true">Désignation</th>
                    <th data-field="editeur" data-sortable="true">Éditeur</th>
                    <th data-field="type_licence">Type Licence</th>
                    <th data-field="categorie">Catégorie</th>
                    <th data-field="nb_licences" class="text-center">Licences</th>
                    <th data-field="status_label" data-formatter="statusFormatter">Statut</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.logiciels._modal')
@include('parcinfo::shared._modal_editeur')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/logiciels/index.js') }}?v={{ time() }}"></script>
@endpush
