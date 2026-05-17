@extends('parcinfo::layouts.master')

@section('header', 'Stock Consommables')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Consommables</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('plugins/select2/css/select2-bootstrap-5-theme.min.css') }}">
@endpush

@section('content')

{{-- ── KPI Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-box-seam fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Articles</div>
                    <div class="fw-bold fs-4" id="kpi-total">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3"><i class="bi bi-exclamation-octagon fs-4 text-danger"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Rupture</div>
                    <div class="fw-bold fs-4 text-danger" id="kpi-rupture">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3"><i class="bi bi-currency-euro fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Valeur Stock</div>
                    <div class="fw-bold fs-4 text-success" id="kpi-valeur">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-info bg-opacity-10 p-3"><i class="bi bi-arrow-left-right fs-4 text-info"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Mouvements (Mois)</div>
                    <div class="fw-bold fs-4 text-info" id="kpi-mouvements">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtres ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Type d'article</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}">{{ $t->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Statut Stock</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les niveaux</option>
                    <option value="rupture">En rupture (≤ Min)</option>
                    <option value="alerte">Alerte (Bas)</option>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btn-reset-filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Catalogue des Consommables</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            <button id="btn-add" class="btn btn-primary" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            <button id="btn-edit" class="btn btn-info" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            <button id="btn-toggle-status" class="btn btn-warning" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-toggle-on"></i>
            </button>
            <button id="btn-delete" class="btn btn-danger" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <table id="consommables-table"
               data-toggle="table"
               data-url="{{ route('parc-info.consommables.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="true"
               data-id-field="id"
               data-page-list="[10,25,50,100]"
               data-page-size="25"
               data-query-params="consommablesQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true" data-formatter="codeFormatter">Code</th>
                    <th data-field="nom" data-sortable="true">Désignation</th>
                    <th data-field="type">Type</th>
                    <th data-field="stock_actuel" class="text-center" data-formatter="stockFormatter">Stock</th>
                    <th data-field="seuil" class="text-center">Min/Max</th>
                    <th data-field="valeur" class="text-end">Valeur</th>
                    <th data-field="status_label" data-formatter="statusFormatter">Statut</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.consommables._modal')
@include('parcinfo::shared._modal_fournisseur')
@include('parcinfo::shared._modal_type_consommable')
@include('parcinfo::shared._modal_selection_equipement')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/consommables/index.js') }}?v={{ time() }}"></script>
@endpush
