@extends('stock::layouts.master')

@section('title', 'Transferts - Stocks')
@section('header', 'Transferts inter-magasins')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Transferts</li>
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
                <label class="form-label small fw-semibold mb-1" for="filter-source">Magasin source</label>
                <select class="form-select form-select-sm" id="filter-source">
                    <option value="">Tous</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-destination">Magasin destination</label>
                <select class="form-select form-select-sm" id="filter-destination">
                    <option value="">Tous</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->code }} — {{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous</option>
                    @foreach($statuts as $code => $statut)
                        <option value="{{ $code }}">{{ $statut['label'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-exchange-alt me-2 text-primary"></i>Liste des transferts</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('stock.transferts.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Créer un transfert">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            <button id="btn-show" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Voir le détail">
                <i class="fas fa-eye"></i>
            </button>
            @can('stock.transferts.valider')
            <button id="btn-valider" class="btn btn-sm btn-success rounded-1" disabled
                    data-bs-toggle="tooltip" title="Valider le transfert">
                <i class="fas fa-check"></i>
            </button>
            <button id="btn-rejeter" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Rejeter le transfert">
                <i class="fas fa-ban"></i>
            </button>
            @endcan
            <button id="btn-annuler" class="btn btn-sm btn-secondary rounded-1" disabled
                    data-bs-toggle="tooltip" title="Annuler le transfert (créateur ou admin)">
                <i class="fas fa-xmark"></i>
            </button>
        </div>

        <table id="transferts-table"
               data-toggle="table"
               data-url="{{ route('stock.transferts.data') }}"
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
                    <th data-field="numero_transfert" data-sortable="true" class="fw-semibold">Numéro</th>
                    <th data-field="article">Article</th>
                    <th data-field="source">Source</th>
                    <th data-field="destination">Destination</th>
                    <th data-field="quantite" data-sortable="true" class="text-end">Quantité</th>
                    <th data-field="statut" data-formatter="statutTransfertFormatter" class="text-center">Statut</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateHeureFormatter" class="text-end">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::transferts._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/stock/transferts/index.js') }}?v={{ time() }}"></script>
@endpush
