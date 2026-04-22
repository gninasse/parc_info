@extends('parcinfo::layouts.master')

@section('header', 'Ordinateurs Fixes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Ordinateurs Fixes</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── KPI Cards ── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-pc-display fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Total Parc</div>
                    <div class="fw-bold fs-4" id="kpi-total">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3"><i class="bi bi-check-circle fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Service</div>
                    <div class="fw-bold fs-4 text-success" id="kpi-service">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3"><i class="bi bi-tools fs-4 text-warning"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Réparation</div>
                    <div class="fw-bold fs-4 text-warning" id="kpi-reparation">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-secondary bg-opacity-10 p-3"><i class="bi bi-archive fs-4 text-secondary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">En Stock</div>
                    <div class="fw-bold fs-4 text-secondary" id="kpi-stock">—</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtres ── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Site géographique</label>
                <select class="form-select form-select-sm" id="filter-site">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $s)
                        <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Direction</label>
                <select class="form-select form-select-sm" id="filter-direction">
                    <option value="">Toutes les directions</option>
                    @foreach($directions as $d)
                        <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_service">En service</option>
                    <option value="en_stock">En stock</option>
                    <option value="en_reparation">En réparation</option>
                    <option value="perdu">Perdu / Volé</option>
                    <option value="reforme">Réformé</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Appliquer
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary btn-sm w-100" id="btn-reset-filters">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Réinitialiser
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Liste des Ordinateurs Fixes</h6>
        <button class="btn btn-primary btn-sm px-3" id="btn-add">
            <i class="bi bi-plus-lg me-1"></i> Ajouter un ordinateur
        </button>
    </div>
    <div class="card-body p-0">
        <div id="toolbar" class="px-3 pt-2 pb-1 d-flex gap-2">
            <button class="btn btn-sm btn-outline-secondary" id="btn-edit" disabled title="Modifier">
                <i class="bi bi-pencil"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" id="btn-delete" disabled title="Supprimer">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <table id="ordinateurs-table"
               data-toggle="table"
               data-url="{{ route('parc-info.ordinateurs-fixes.data') }}"
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
               data-query-params="ordinateursQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code_inventaire" data-sortable="true" data-formatter="codeFormatter">Code Inventaire</th>
                    <th data-field="marque_modele" data-sortable="true">Marque & Modèle</th>
                    <th data-field="os">OS</th>
                    <th data-field="config">Config Matérielle</th>
                    <th data-field="statut" data-formatter="statutFormatter">Statut</th>
                    <th data-field="affectation">Affectation</th>
                    <th data-field="id" data-formatter="actionsFormatter" data-events="actionsEvents">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.ordinateurs._wizard')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/ordinateurs/index.js') }}?v={{ time() }}"></script>
@endpush
