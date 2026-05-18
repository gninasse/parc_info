@extends('parcinfo::layouts.master')

@section('header', 'Mobiles & Tablettes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Mobiles & Tablettes</li>
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
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-phone-vibrate fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Total Mobiles</div>
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
            <div class="col-md-2">
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
                <label class="form-label small fw-semibold mb-1">Type de mobile</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    @foreach($typesMobiles as $t)
                        <option value="{{ $t->id }}">{{ $t->libelle }}</option>
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
            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" id="btn-apply-filters">
                    <i class="bi bi-funnel me-1"></i> Filtrer
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="btn-reset-filters" title="Réinitialiser">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Liste du parc Mobiles & Tablettes</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar" class="ms-3">
            <button id="btn-add" class="btn btn-primary btn-sm px-3 shadow-none">
                <i class="bi bi-plus-lg me-1"></i> Ajouter
            </button>
            <button id="btn-edit" class="btn btn-outline-info btn-sm shadow-none" disabled>
                <i class="bi bi-eye me-1"></i> Détails
            </button>
            <button id="btn-delete" class="btn btn-outline-danger btn-sm shadow-none" disabled>
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <table id="mobiles-table"
               data-toggle="table"
               data-url="{{ route('parc-info.mobiles.data') }}"
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
               data-query-params="mobilesQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code_inventaire" data-sortable="true" data-formatter="codeFormatter">Code Inventaire</th>
                    <th data-field="marque_modele" data-sortable="true">Marque & Modèle</th>
                    <th data-field="type_mobile" data-sortable="true">Type</th>
                    <th data-field="imei_1" data-sortable="true">IMEI 1</th>
                    <th data-field="num_tel_associe" data-sortable="true">N° Tél</th>
                    <th data-field="statut" data-formatter="statutFormatter" data-sortable="true">Statut</th>
                    <th data-field="affectation">Affectation</th>
                    <th data-field="id" data-formatter="actionsFormatter" data-events="actionsEvents">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.mobiles._wizard')
@include('parcinfo::informatique.ordinateurs._selection_modals')
@endsection

@push('js')
<script>
    window.routePrefix = "parc-info.mobiles";
</script>
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/mobiles/index.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
@endpush
