@extends('grh::layouts.master')

@section('header', 'Gestion des employés')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">GRH - Dossiers employés</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- Filtres --}}
<div class="card mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">Filtres de recherche</h5>
    </div>
    <div class="card-body">
        <form id="filter-form" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="filter-direction" class="form-label">Direction</label>
                <select class="form-select" id="filter-direction" name="direction_id">
                    <option value="">Toutes les directions</option>
                    @foreach($directions as $dir)
                        <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-service" class="form-label">Service</label>
                <select class="form-select" id="filter-service" name="service_id">
                    <option value="">Tous les services</option>
                    @foreach($services as $srv)
                        <option value="{{ $srv->id }}">{{ $srv->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter-unite" class="form-label">Unité</label>
                <select class="form-select" id="filter-unite" name="unite_id">
                    <option value="">Toutes les unités</option>
                    @foreach($unites as $unt)
                        <option value="{{ $unt->id }}">{{ $unt->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter-status" class="form-label">Statut</label>
                <select class="form-select" id="filter-status" name="est_actif">
                    <option value="">Tous</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="button" id="btn-reset-filters" class="btn btn-outline-secondary w-100"
                        data-bs-toggle="tooltip" title="Réinitialiser les filtres">
                    <i class="fas fa-undo"></i>
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Table des employés --}}
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Liste des employés</h3>
    </div>
    <div class="card-body">

        <div id="toolbar">
            <button id="btn-add-employe" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter un employé">
                <i class="fas fa-user-plus"></i>
            </button>
            <button id="btn-edit-employe" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Voir / Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button id="btn-activate-employe" class="btn btn-success" disabled data-bs-toggle="tooltip" title="Activer">
                <i class="fas fa-check"></i>
            </button>
            <button id="btn-deactivate-employe" class="btn btn-secondary" disabled data-bs-toggle="tooltip" title="Désactiver">
                <i class="fas fa-ban"></i>
            </button>
        </div>

        <table id="employes-table"
               data-toggle="table"
               data-url="{{ route('grh.employes.data') }}"
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
               data-query-params="queryParams">
            <thead>
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="matricule" data-sortable="true">Matricule</th>
                    <th data-field="full_name" data-sortable="true">Nom Complet</th>
                    <th data-field="poste" data-sortable="true">Poste</th>
                    <th data-field="niveau" data-sortable="true">Niveau</th>
                    <th data-field="rattachement" data-sortable="true">Rattachement</th>
                    <th data-field="est_actif" data-sortable="true" data-formatter="statusFormatter">Statut</th>
                    <th data-field="created_at" data-sortable="true">Date d'enregistrement</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('grh::employes._modal')

@stop

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
    window.grhRoutes = {
        store: "{{ route('grh.employes.store') }}",
        toggle: (id) => `/grh/employes/${id}/toggle-status`,
        show: (id) => `/grh/employes/${id}`
    };
</script>
<script type="module" src="{{ asset('js/modules/grh/employes/index.js') }}"></script>
@endpush
