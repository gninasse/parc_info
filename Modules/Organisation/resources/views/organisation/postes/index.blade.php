@extends('core::layouts.master')

@section('header', 'Gestion des Poste de travails')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation</li>
    <li class="breadcrumb-item active" aria-current="page">Poste de travails</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des postes</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3 align-items-end">
            <div class="col-md-3">
                <label for="filter_direction_id" class="form-label">Direction</label>
                <select class="form-select" id="filter_direction_id">
                    <option value="">Toutes les directions</option>
                    @foreach($directions as $direction)
                        <option value="{{ $direction->id }}">{{ $direction->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_service_id" class="form-label">Service</label>
                <select class="form-select" id="filter_service_id">
                    <option value="">Tous les services</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_statut" class="form-label">Statut</label>
                <select class="form-select" id="filter_statut">
                    <option value="">Tous</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                    <option value="en_renovation">En rénovation</option>
                </select>
            </div>
            <div class="col-md-2">
                <button id="btn-filter" class="btn btn-secondary w-100">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </div>
        
        <div id="toolbar">
            @can('cores.organisation.postes.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('cores.organisation.postes.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('cores.organisation.postes.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        
        <table id="postes-table"
               data-toggle="table"
               data-url="{{ route('organisation.postes.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]">
            <thead>
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="libelle" data-sortable="true">Libellé</th>
                    <th data-field="niveau" data-sortable="true">Niveau</th>
                    <th data-field="direction" data-sortable="true">Direction</th>
                    <th data-field="service" data-sortable="true">Service</th>
                    <th data-field="emplacement" data-sortable="true">Emplacement</th>
                    <th data-field="occupant" data-sortable="true">Occupant</th>
                    <th data-field="statut" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('organisation::organisation.postes._modal')
@stop

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function statutFormatter(value) {
        let badges = {
            'actif': 'success',
            'inactif': 'secondary',
            'en_renovation': 'warning',
            'supprime': 'danger'
        };
        let labels = {
            'actif': 'Actif',
            'inactif': 'Inactif',
            'en_renovation': 'En rénovation',
            'supprime': 'Supprimé'
        };
        return `<span class="badge bg-${badges[value] || 'info'}">${labels[value] || value}</span>`;
    }
</script>

<script type="module" src="{{ asset('js/modules/organisation/postes/index.js') }}?v={{ time() }}"></script>
@endpush
