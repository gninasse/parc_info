@extends('stock::layouts.master')

@section('title', 'Transferts Inter-Magasins')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-exchange-alt me-2 text-primary"></i>Transferts Inter-Magasins
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transferts</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('stock.transferts.create')
                <button type="button" class="btn btn-primary btn-sm rounded-1" id="btn-add-transfert">
                    <i class="fas fa-plus me-1"></i> Nouveau transfert
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
                        <option value="EN_ATTENTE" selected>En attente de validation</option>
                        <option value="VALIDE">Validés</option>
                        <option value="REJETE">Rejetés</option>
                        <option value="ANNULE">Annulés</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filter-source">
                        <option value="">Tous les magasins sources</option>
                        @foreach($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select form-select-sm" id="filter-dest">
                        <option value="">Tous les magasins destinations</option>
                        @foreach($magasins as $m)
                            <option value="{{ $m->id }}">{{ $m->nom }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="card-body">
            <!-- Toolbar Bootstrap Table -->
            <div id="transferts-toolbar" class="d-flex gap-1">
                <button class="btn btn-light btn-sm border rounded-1" id="btn-view-transfert" disabled>
                    <i class="fas fa-eye me-1"></i> Consulter
                </button>
                @can('stock.transferts.admin')
                    <button class="btn btn-success btn-sm text-white rounded-1" id="btn-approve-transfert" disabled>
                        <i class="fas fa-check me-1"></i> Valider
                    </button>
                    <button class="btn btn-danger btn-sm text-white rounded-1" id="btn-reject-transfert" disabled>
                        <i class="fas fa-times me-1"></i> Rejeter
                    </button>
                @endcan
                @can('stock.transferts.create')
                    <button class="btn btn-outline-danger btn-sm rounded-1" id="btn-cancel-transfert" disabled>
                        <i class="fas fa-ban me-1"></i> Annuler
                    </button>
                @endcan
            </div>

            <!-- Table -->
            <table id="transferts-table"
                   data-toggle="table"
                   data-url="{{ route('stock.transferts.data') }}"
                   data-side-pagination="server"
                   data-pagination="true"
                   data-search="true"
                   data-toolbar="#transferts-toolbar"
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
                        <th data-field="numero_transfert" data-sortable="true" data-halign="center" data-align="center">N° Transfert</th>
                        <th data-field="magasin_source">Magasin Source</th>
                        <th data-field="magasin_destination">Magasin Destination</th>
                        <th data-field="article">Article</th>
                        <th data-field="quantite" data-sortable="true" data-align="right">Quantité</th>
                        <th data-field="statut" data-sortable="true" data-formatter="transferStatutFormatter" data-halign="center" data-align="center">Statut</th>
                        <th data-field="created_by">Demandeur</th>
                        <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter" data-align="center">Date demande</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('stock::transferts._modal')

@endsection

@push('scripts')
<script type="module" src="{{ asset('js/modules/stock/transferts/index.js') }}?v={{ time() }}"></script>
@endpush
