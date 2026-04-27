@extends('core::layouts.master')

@section('header', 'Gestion des Services')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Organisation</li>
    <li class="breadcrumb-item active" aria-current="page">Services</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des services</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-3">
                <label for="filter_site_id" class="form-label">Filtrer par Site</label>
                <select class="form-select" id="filter_site_id">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_direction_id" class="form-label">Filtrer par Direction</label>
                <select class="form-select" id="filter_direction_id" disabled>
                    <option value="">Toutes les directions</option>
                </select>
            </div>
        </div>
        
        <div id="toolbar">
            @can('cores.organisation.services.store')
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('cores.organisation.services.update')
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('cores.organisation.services.destroy')
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>
        
        <table id="services-table"
               data-toggle="table"
               data-url="{{ route('organisation.services.data') }}"
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
                    <th data-field="type_service" data-sortable="true" data-formatter="typeServiceFormatter">Type</th>
                    <th data-field="site.libelle" data-sortable="true">Site</th>
                    <th data-field="direction.libelle" data-sortable="true">Direction</th>
                    <th data-field="chef_service.full_name" data-sortable="true">Chef Service</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statutFormatter">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('organisation::organisation.services._modal')
@stop

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    window.serviceRoutes = {
        directionsBySite: "{{ route('organisation.services.directions-by-site', ['siteId' => ':siteId']) }}",
        chefsService: "{{ route('organisation.services.chefs-service') }}",
        data: "{{ route('organisation.services.data') }}"
    };

    function statutFormatter(value) {
        return value 
            ? '<span class="badge bg-success">Actif</span>' 
            : '<span class="badge bg-danger">Inactif</span>';
    }

    function typeServiceFormatter(value) {
        const types = {
            'administratif': '<span class="badge bg-secondary">Administratif</span>',
            'clinique': '<span class="badge bg-primary">Clinique</span>',
            'medico_technique': '<span class="badge bg-info">Médico-Technique</span>'
        };
        return types[value] || value;
    }
</script>

<script type="module" src="{{ asset('js/modules/organisation/services/index.js') }}"></script>
@endpush
