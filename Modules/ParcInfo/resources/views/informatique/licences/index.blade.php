@extends('parcinfo::layouts.master')

@section('header', 'Gestion des Licences')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Licences</li>
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
                <div class="rounded-3 bg-primary bg-opacity-10 p-3"><i class="bi bi-file-lock fs-4 text-primary"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Licences Actives</div>
                    <div class="fw-bold fs-4" id="kpi-total">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-danger bg-opacity-10 p-3"><i class="bi bi-exclamation-circle fs-4 text-danger"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Expirant dans 30j</div>
                    <div class="fw-bold fs-4 text-danger" id="kpi-expirant">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-warning bg-opacity-10 p-3"><i class="bi bi-exclamation-triangle fs-4 text-warning"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Surexploitées</div>
                    <div class="fw-bold fs-4 text-warning" id="kpi-surexploitees">—</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-3 bg-success bg-opacity-10 p-3"><i class="bi bi-coin fs-4 text-success"></i></div>
                <div>
                    <div class="text-muted small fw-semibold text-uppercase" style="font-size:.7rem;letter-spacing:.5px">Budget Annuel</div>
                    <div class="fw-bold fs-4 text-success" id="kpi-budget">—</div>
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
                <label class="form-label small fw-semibold mb-1">Logiciel</label>
                <select class="form-select form-select-sm" id="filter-logiciel">
                    <option value="">Tous les logiciels</option>
                    @foreach($logiciels as $l)
                        <option value="{{ $l->id }}">{{ $l->nom }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="expire">Expiré</option>
                    <option value="en_renouvellement">En renouvellement</option>
                    <option value="suspendu">Suspendu</option>
                </select>
            </div>
            <div class="col-md-3">
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
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold">Liste des Licences</h6>
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
        <table id="licences-table"
               data-toggle="table"
               data-url="{{ route('parc-info.licences.data') }}"
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
               data-query-params="licencesQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="logiciel" data-sortable="true" data-formatter="logicielFormatter">Logiciel</th>
                    <th data-field="contrat">Clé / Contrat</th>
                    <th data-field="expiration" data-sortable="true" data-formatter="expirationFormatter">Expiration</th>
                    <th data-field="postes" class="text-center">Postes</th>
                    <th data-field="utilisation" data-formatter="utilisationFormatter">Utilisation</th>
                    <th data-field="cout">Coût Total</th>
                    <th data-field="status_label">Statut</th>
                    <th data-field="id" data-formatter="actionsFormatter">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('parcinfo::informatique.licences._modal')
@include('parcinfo::shared._modal_fournisseur')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/licences/index.js') }}?v={{ time() }}"></script>
@endpush
