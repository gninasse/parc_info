@extends('stock::layouts.master')

@section('title', 'Sorties de stock - Stocks')
@section('header', 'Sorties de stock')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Sorties</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-magasin">Magasin</label>
                <select class="form-select form-select-sm" id="filter-magasin">
                    <option value="">Tous les magasins</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-type">Type</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous</option>
                    <option value="SORTIE">Sortie</option>
                    <option value="REGULARISATION_MOINS">Régularisation −</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" for="filter-date-debut">Du</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-debut">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" for="filter-date-fin">Au</label>
                <input type="date" class="form-control form-control-sm" id="filter-date-fin">
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-arrow-circle-up me-2 text-primary"></i>Journal des sorties</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('stock.sorties.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Enregistrer une sortie">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('stock.sorties.admin')
            <button id="btn-regul" class="btn btn-sm btn-warning text-dark rounded-1"
                    data-bs-toggle="tooltip" title="Régularisation négative (admin)">
                <i class="fas fa-scale-unbalanced"></i>
            </button>
            @endcan
            <button id="btn-show" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Voir le détail">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <table id="sorties-table"
               data-toggle="table"
               data-url="{{ route('stock.sorties.data') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero_mouvement" data-sortable="true" class="fw-semibold">Numéro</th>
                    <th data-field="type_mouvement" data-formatter="typeMouvementFormatter" class="text-center">Type</th>
                    <th data-field="article">Article</th>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="quantite" data-sortable="true" class="text-end">Quantité</th>
                    <th data-field="cout_unitaire" data-formatter="prixFormatter" class="text-end">Coût FIFO</th>
                    <th data-field="cible">Affectation</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateHeureFormatter" class="text-end">Date</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::sorties._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/stock/sorties/index.js') }}?v={{ time() }}"></script>
@endpush
