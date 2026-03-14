@extends('core::layouts.master')

@section('header', 'Gestion des Directions')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation</li>
    <li class="breadcrumb-item active" aria-current="page">Directions</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des directions</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4">
                <label for="filter_site_id" class="form-label">Filtrer par Site</label>
                <select class="form-select" id="filter_site_id">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div id="toolbar">
            @can('cores.organisation.directions.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('cores.organisation.directions.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('cores.organisation.directions.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        
        <table id="directions-table"
               data-toggle="table"
               data-url="{{ route('organisation.directions.data') }}"
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
                    <th data-field="site.libelle" data-sortable="true">Site</th>
                    <th data-field="responsable.name" data-sortable="true">Responsable</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('organisation::organisation.directions._modal')
@stop

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    function statutFormatter(value) {
        return value 
            ? '<span class="badge bg-success">Actif</span>' 
            : '<span class="badge bg-danger">Inactif</span>';
    }
</script>

<script type="module" src="{{ asset('js/modules/organisation/directions/index.js') }}?v={{ time() }}"></script>
@endpush
