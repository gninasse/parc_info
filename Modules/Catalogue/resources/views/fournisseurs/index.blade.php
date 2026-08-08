@extends('core::layouts.master')

@section('header', 'Fournisseurs')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Catalogue</li>
    <li class="breadcrumb-item active" aria-current="page">Fournisseurs</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- KPI --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="fas fa-truck text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $kpis['actifs'] }}</div>
                    <div class="text-muted small text-uppercase">Fournisseurs actifs</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="fas fa-boxes text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $kpis['avec_articles'] }}</div>
                    <div class="text-muted small text-uppercase">Avec articles au catalogue</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="fas fa-calendar-plus text-info"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $kpis['ajoutes_annee'] }}</div>
                    <div class="text-muted small text-uppercase">Ajoutés cette année</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-truck me-2 text-primary"></i>Liste des fournisseurs</h6>
        <div style="width: 180px;">
            <select class="form-select form-select-sm" id="filter-statut">
                <option value="">Tous les statuts</option>
                <option value="actif">Actifs</option>
                <option value="inactif">Inactifs</option>
            </select>
        </div>
    </div>
    <div class="card-body p-0">

        <div id="toolbar">
            @can('catalogue.fournisseurs.store')
            <button id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('catalogue.fournisseurs.index')
            <button id="btn-show" class="btn btn-secondary btn-sm" disabled data-bs-toggle="tooltip" title="Voir la fiche">
                <i class="fas fa-eye"></i>
            </button>
            @endcan
            @can('catalogue.fournisseurs.update')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('catalogue.fournisseurs.toggle-status')
            <button id="btn-toggle" class="btn btn-warning btn-sm" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
            @can('catalogue.fournisseurs.destroy')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="fournisseurs-table"
               data-toggle="table"
               data-url="{{ route('catalogue.fournisseurs.data') }}"
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
               data-page-size="10"
               data-locale="fr-FR"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="raison_sociale" data-sortable="true">Raison sociale</th>
                    <th data-field="contact" data-sortable="true">Contact</th>
                    <th data-field="telephone">Téléphone</th>
                    <th data-field="email" data-sortable="true">Email</th>
                    <th data-field="nb_articles" data-sortable="true" data-align="center">Articles</th>
                    <th data-field="est_actif" data-sortable="true" data-formatter="statutFormatter" data-align="center">Statut</th>
                    <th data-field="created_at" data-sortable="true">Ajouté le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('catalogue::fournisseurs._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/catalogue/fournisseurs/index.js') }}?v={{ time() }}"></script>
@endpush
