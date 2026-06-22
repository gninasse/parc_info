@extends('parcinfo::layouts.master')

@section('header')
    Gestion des valeurs : {{ $dictionnaire->libelle }}
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item">Référentiels</li>
    <li class="breadcrumb-item"><a href="{{ route('parc-info.referentiels.dictionnaires.index') }}">Dictionnaires</a></li>
    <li class="breadcrumb-item active">{{ $dictionnaire->libelle }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="mb-3">
    <a href="{{ route('parc-info.referentiels.dictionnaires.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Retour aux dictionnaires
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Valeurs pour : {{ $dictionnaire->libelle }}</h6>
        @if($dictionnaire->description)
            <small class="text-muted">{{ $dictionnaire->description }}</small>
        @endif
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            @can('parc-info.referentiels.dictionnaires.manage')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter une valeur">
                <i class="fas fa-plus me-1"></i> Nouvelle valeur
            </button>
            <button id="btn-edit" class="btn btn-info text-white" disabled data-bs-toggle="tooltip" title="Modifier la valeur">
                <i class="fas fa-edit me-1"></i> Modifier
            </button>
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer la valeur">
                <i class="fas fa-trash me-1"></i> Supprimer
            </button>
            @endcan
        </div>
        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('parc-info.referentiels.dictionnaires.valeurs.data', $dictionnaire->code) }}"
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
                    <th data-field="valeur" data-sortable="true">Valeur</th>
                    <th data-field="description" data-sortable="true">Description</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter">Date création</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::referentiels.dictionnaires._modal_values')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.dateFormatter = function (value) {
        if (!value) return '-';
        return new Date(value).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    };
    window.dictCode = "{{ $dictionnaire->code }}";
</script>
<script type="module" src="{{ asset('js/modules/parc-info/referentiels/dictionnaires-values.js') }}?v={{ time() }}"></script>
@endpush
