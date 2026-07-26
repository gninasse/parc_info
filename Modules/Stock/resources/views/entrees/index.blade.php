@extends('stock::layouts.master')

@section('title', 'Bons d\'Entrée de Stock')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header Page -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-dark">
                <i class="fas fa-arrow-circle-left me-2 text-success"></i>Bons d'Entrée de Stock
            </h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stock</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Entrées</li>
                </ol>
            </nav>
        </div>
        <div>
            @can('stock.entrees.create')
                <button type="button" class="btn btn-success btn-sm rounded-1 text-white" id="btn-add-entree">
                    <i class="fas fa-plus me-1"></i> Nouveau bon d'entrée (Manuel)
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
            <div id="entrees-toolbar" class="d-flex gap-1">
                @can('stock.entrees.admin')
                    <button class="btn btn-outline-danger btn-sm rounded-1" id="btn-delete-entree" disabled>
                        <i class="fas fa-trash me-1"></i> Supprimer (Manuel & < 24h)
                    </button>
                @endcan
            </div>

            <!-- Table -->
            <table id="entrees-table"
                   data-toggle="table"
                   data-url="{{ route('stock.entrees.data') }}"
                   data-side-pagination="server"
                   data-pagination="true"
                   data-search="true"
                   data-toolbar="#entrees-toolbar"
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
                        <th data-field="magasin">Magasin</th>
                        <th data-field="article">Article</th>
                        <th data-field="quantite" data-sortable="true" data-align="right">Quantité</th>
                        <th data-field="cout_unitaire" data-sortable="true" data-align="right">Coût Unitaire</th>
                        <th data-field="cout_total" data-align="right">Valeur Totale</th>
                        <th data-field="type_origine" data-halign="center" data-align="center">Origine</th>
                        <th data-field="reference_document">Réf. Document</th>
                        <th data-field="motif">Motif / Commentaire</th>
                        <th data-field="created_by">Enregistré par</th>
                        <th data-field="created_at" data-sortable="true" data-formatter="dateFormatter" data-align="center">Date</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('stock::entrees._modal')

@endsection

@push('scripts')
<script type="module" src="{{ asset('js/modules/stock/entrees/index.js') }}?v={{ time() }}"></script>
@endpush
