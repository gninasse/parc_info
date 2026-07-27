@extends('stock::layouts.master')

@section('title', 'Stock par article - Stocks')
@section('header', 'Stock par article')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Stock par article</li>
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
                        <option value="{{ $magasin->id }}">
                            {{ $magasin->code }} — {{ $magasin->libelle }}{{ $magasin->statut === 'inactif' ? ' (inactif)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-alerte">Niveau d'alerte</label>
                <select class="form-select form-select-sm" id="filter-alerte">
                    <option value="">Tous</option>
                    @foreach($statutsAlerte as $code => $statut)
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
        <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>Niveaux de stock</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            @can('stock.articles.admin')
            <button id="btn-init" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Initialiser un article en stock">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            <button id="btn-detail" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Détail par magasin et lots FIFO">
                <i class="fas fa-eye"></i>
            </button>
        </div>

        <table id="articles-table"
               data-toggle="table"
               data-url="{{ route('stock.articles.data') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="article_id"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="25"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code_article" data-sortable="true" class="fw-semibold">Code</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="marque">Marque</th>
                    <th data-field="unite_mesure" class="text-center">Unité</th>
                    <th data-field="quantite" data-sortable="true" class="text-end">Quantité</th>
                    <th data-field="seuil_alerte" class="text-center">Seuil</th>
                    <th data-field="statut_alerte" data-formatter="statutAlerteFormatter" class="text-center">Alerte</th>
                    <th data-field="valeur_fifo" data-sortable="true" data-formatter="prixFormatter" class="text-end">Valeur FIFO</th>
                    <th data-field="derniere_entree_at" data-formatter="dateHeureFormatter" class="text-end">Dernière entrée</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::articles._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/stock/articles/index.js') }}?v={{ time() }}"></script>
@endpush
