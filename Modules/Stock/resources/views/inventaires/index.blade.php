@extends('stock::layouts.master')

@section('title', 'Campagnes d\'Inventaire')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-clipboard-list me-2 text-info"></i>Campagnes d'Inventaire
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Inventaires</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('stock.inventaires.create')
                <button type="button" class="btn btn-info btn-sm rounded-1 text-white" id="btn-add-inventaire">
                    <i class="fas fa-plus me-1"></i> Nouvelle campagne
                </button>
            @endcan
        </div>
    </div>

    <!-- Filtres & Table -->
    <div class="card border-0 shadow-sm rounded-1">
        <!-- Filtres externes -->
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filter-statut">
                        <option value="">Tous les statuts</option>
                        <option value="BROUILLON" selected>En cours (Brouillon)</option>
                        <option value="VALIDE">Clôturés & Validés</option>
                        <option value="ANNULE">Annulés</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filter-magasin">
                        <option value="">Tous les magasins</option>
                        @foreach($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Toolbar Bootstrap Table -->
            <div id="inventaires-toolbar" class="d-flex gap-1">
                <button class="btn btn-light btn-sm border rounded-1" id="btn-view-inventaire" disabled>
                    <i class="fas fa-eye me-1"></i> Consulter
                </button>
                @can('stock.inventaires.create')
                    <a class="btn btn-warning btn-sm text-dark rounded-1" id="btn-saisie-inventaire" href="#" disabled>
                        <i class="fas fa-pencil-alt me-1"></i> Saisir comptages
                    </a>
                @endcan
                @can('stock.inventaires.admin')
                    <button class="btn btn-success btn-sm text-white rounded-1" id="btn-approve-inventaire" disabled>
                        <i class="fas fa-check-double me-1"></i> Clôturer & Valider
                    </button>
                @endcan
                @can('stock.inventaires.create')
                    <button class="btn btn-outline-danger btn-sm rounded-1" id="btn-cancel-inventaire" disabled>
                        <i class="fas fa-ban me-1"></i> Annuler
                    </button>
                @endcan
            </div>

            <!-- Table -->
            <table id="inventaires-table"
                   data-toggle="table"
                   data-url="{{ route('stock.inventaires.data') }}"
                   data-side-pagination="server"
                   data-pagination="true"
                   data-search="true"
                   data-toolbar="#inventaires-toolbar"
                   data-show-columns="true"
                   data-show-refresh="true"
                   data-click-to-select="true"
                   data-single-select="true"
                   data-page-size="10"
                   data-page-list="[10, 25, 50]"
                   class="table table-hover align-middle small">
                <thead>
                    <tr>
                        <th data-checkbox="true"></th>
                        <th data-field="numero_inventaire" data-sortable="true" data-halign="center" data-align="center">N° Inventaire</th>
                        <th data-field="magasin">Magasin</th>
                        <th data-field="statut" data-sortable="true" data-formatter="inventaireStatutFormatter" data-halign="center" data-align="center">Statut</th>
                        <th data-field="nombre_articles" data-sortable="true" data-align="right">Nbr. Articles</th>
                        <th data-field="nombre_ecarts" data-sortable="true" data-align="right">Nbr. Écarts</th>
                        <th data-field="created_by">Créé par</th>
                        <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter" data-align="center">Date création</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('stock::inventaires._modal')

@endsection

@push('scripts')
<script type="module" src="{{ asset('js/modules/stock/inventaires/index.js') }}?v={{ time() }}"></script>
@endpush
