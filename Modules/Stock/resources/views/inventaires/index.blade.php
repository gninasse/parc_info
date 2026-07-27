@extends('stock::layouts.master')

@section('title', 'Inventaires - Stocks')
@section('header', 'Inventaires physiques')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Inventaires</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1" for="filter-magasin">Magasin</label>
                <select class="form-select form-select-sm" id="filter-magasin">
                    <option value="">Tous les magasins</option>
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
        <h6 class="mb-0 fw-bold"><i class="fas fa-clipboard-check me-2 text-primary"></i>Campagnes d'inventaire</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('stock.inventaires.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Ouvrir un inventaire">
                <i class="fas fa-plus"></i>
            </button>
            <button id="btn-saisir" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Saisir les comptages">
                <i class="fas fa-pen"></i>
            </button>
            @endcan
            @can('stock.inventaires.admin')
            <button id="btn-valider" class="btn btn-sm btn-success rounded-1" disabled
                    data-bs-toggle="tooltip" title="Valider et régulariser les écarts">
                <i class="fas fa-check"></i>
            </button>
            <button id="btn-annuler" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Annuler l'inventaire (admin)">
                <i class="fas fa-ban"></i>
            </button>
            @endcan
        </div>

        <table id="inventaires-table"
               data-toggle="table"
               data-url="{{ route('stock.inventaires.data') }}"
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
                    <th data-field="numero_inventaire" data-sortable="true" class="fw-semibold">Numéro</th>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="date_inventaire" data-formatter="dateFormatter" class="text-center">Date</th>
                    <th data-field="nombre_articles" class="text-center">Articles</th>
                    <th data-field="nombre_ecarts" class="text-center">Écarts</th>
                    <th data-field="statut" data-formatter="statutInventaireFormatter" class="text-center">Statut</th>
                    <th data-field="created_at" data-sortable="true" data-formatter="dateHeureFormatter" class="text-end">Créé le</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::inventaires._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/stock/inventaires/index.js') }}?v={{ time() }}"></script>
@endpush
