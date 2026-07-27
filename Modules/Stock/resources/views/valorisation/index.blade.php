@extends('stock::layouts.master')

@section('title', 'Valorisation - Stocks')
@section('header', 'Valorisation du stock (FIFO)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('stock.dashboard.index') }}">Stocks</a></li>
    <li class="breadcrumb-item active">Valorisation</li>
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
        </div>
    </div>
</div>

{{-- ── VALORISATION ACTUELLE ───────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-coins me-2 text-primary"></i>Valorisation actuelle</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar" class="d-flex gap-1">
            <a href="{{ route('stock.valorisation.pdf') }}" id="btn-pdf" target="_blank"
               class="btn btn-sm btn-secondary rounded-1"
               data-bs-toggle="tooltip" title="Exporter en PDF">
                <i class="fas fa-file-pdf"></i>
            </a>
            @can('stock.valorisation.admin')
            <button id="btn-snapshot" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Créer un snapshot manuel">
                <i class="fas fa-camera"></i>
            </button>
            <button id="btn-recalculer" class="btn btn-sm btn-warning text-dark rounded-1"
                    data-bs-toggle="tooltip" title="Recalculer les projections depuis les lots FIFO">
                <i class="fas fa-rotate"></i>
            </button>
            @endcan
        </div>

        <table id="valorisation-table"
               data-toggle="table"
               data-url="{{ route('stock.valorisation.data') }}"
               data-side-pagination="server"
               data-pagination="false"
               data-search="false"
               data-show-refresh="true"
               data-toolbar="#toolbar"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="code_article" class="fw-semibold">Code</th>
                    <th data-field="article">Article</th>
                    <th data-field="quantite" class="text-end">Quantité</th>
                    <th data-field="cout_moyen" data-formatter="prixFormatter" class="text-end">Coût moyen</th>
                    <th data-field="valeur_fifo" data-formatter="prixFormatter" class="text-end">Valeur FIFO</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- ── SNAPSHOTS ───────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-camera me-2 text-primary"></i>Snapshots (immuables)</h6>
    </div>
    <div class="card-body p-0">
        <table id="snapshots-table"
               data-toggle="table"
               data-url="{{ route('stock.valorisation.snapshots') }}"
               data-side-pagination="server"
               data-pagination="true"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-size="10"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="reference" class="fw-semibold font-monospace">Référence</th>
                    <th data-field="type" data-formatter="typeSnapshotFormatter" class="text-center">Type</th>
                    <th data-field="date_snapshot" data-formatter="dateHeureFormatter" class="text-end">Date</th>
                    <th data-field="valeur_totale_globale" data-formatter="prixFormatter" class="text-end">Valeur totale</th>
                    <th data-field="createur">Créé par</th>
                    <th data-field="id" data-formatter="detailSnapshotFormatter" class="text-end">Détail</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- ── MODAL DÉTAIL SNAPSHOT ───────────────────────────────────────────── --}}
<div class="modal fade" id="snapshotModal" tabindex="-1" aria-labelledby="snapshotModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-1 rounded-1">
            <div class="modal-header bg-info bg-opacity-10 py-2">
                <h6 class="modal-title fw-bold" id="snapshotModalLabel">
                    <i class="fas fa-camera me-2 text-info"></i>Snapshot <span id="snapshot-reference" class="font-monospace"></span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Magasin</th>
                                <th>Code</th>
                                <th>Article</th>
                                <th class="text-end">Quantité</th>
                                <th class="text-end">Coût moyen</th>
                                <th class="text-end">Valeur FIFO</th>
                            </tr>
                        </thead>
                        <tbody id="snapshot-lignes"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/stock/valorisation/index.js') }}?v={{ time() }}"></script>
@endpush
