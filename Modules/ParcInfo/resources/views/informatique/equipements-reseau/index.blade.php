@extends('parcinfo::layouts.master')

@section('header', 'Équipements Réseau')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Informatique</a></li>
    <li class="breadcrumb-item active">Équipements Réseau</li>
@endsection

@section('content')
<div class="container-fluid mb-4">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Équipements</h6>
                            <h2 class="mb-0 fw-bold" id="kpi-total">0</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-router fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">En service</h6>
                            <h2 class="mb-0 fw-bold" id="kpi-service">0</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-check-circle fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">En réparation</h6>
                            <h2 class="mb-0 fw-bold" id="kpi-reparation">0</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-tools fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-secondary text-white h-100 border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">En stock</h6>
                            <h2 class="mb-0 fw-bold" id="kpi-stock">0</h2>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-archive fs-3"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted">Site</label>
                    <select id="filter-site" class="form-select form-select-sm">
                        <option value="">Tous les sites</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Direction</label>
                    <select id="filter-direction" class="form-select form-select-sm">
                        <option value="">Toutes les directions</option>
                        @foreach($directions as $dir)
                            <option value="{{ $dir->id }}">{{ $dir->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Type d'équipement</label>
                    <select id="filter-type" class="form-select form-select-sm">
                        <option value="">Tous les types</option>
                        @foreach($typesReseau as $type)
                            <option value="{{ $type->id }}">{{ $type->libelle }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Statut</label>
                    <select id="filter-statut" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="en_service">En service</option>
                        <option value="en_stock">En stock</option>
                        <option value="en_reparation">En réparation</option>
                        <option value="perdu">Perdu</option>
                        <option value="reforme">Réformé</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Vitesse des ports</label>
                    <select id="filter-vitesse" class="form-select form-select-sm">
                        <option value="">Toutes vitesses</option>
                        <option value="100Mbps">100 Mbps</option>
                        <option value="1Gbps">1 Gbps</option>
                        <option value="10Gbps">10 Gbps</option>
                        <option value="25Gbps">25 Gbps</option>
                        <option value="40Gbps">40 Gbps</option>
                        <option value="100Gbps">100 Gbps</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button id="btn-apply-filters" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-search"></i> Filtrer</button>
                    <button id="btn-reset-filters" class="btn btn-sm btn-light"><i class="bi bi-x-circle"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm pb-3">
        <div class="card-body p-0">
            <div id="toolbar" class="px-3 pt-3">
                <button id="btn-add" class="btn btn-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-plus-lg me-1"></i> Nouvel équipement
                </button>
                <button id="btn-edit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 ms-1" disabled>
                    <i class="bi bi-pencil me-1"></i> Modifier
                </button>
                <button id="btn-delete" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-1" disabled>
                    <i class="bi bi-trash me-1"></i> Supprimer
                </button>
            </div>

            <table id="equipements-reseau-table"
                   data-toggle="table"
                   data-url="{{ route('parc-info.equipements-reseau.data') }}"
                   data-pagination="true"
                   data-side-pagination="server"
                   data-search="true"
                   data-show-refresh="true"
                   data-show-columns="true"
                   data-toolbar="#toolbar"
                   data-click-to-select="true"
                   data-single-select="true"
                   data-id-field="id"
                   data-page-list="[10, 25, 50, 100]"
                   data-page-size="25"
                   data-query-params="equipementsReseauQueryParams"
                   class="table table-hover table-borderless table-striped-columns align-middle">
                <thead class="table-light">
                    <tr>
                        <th data-field="state" data-radio="true"></th>
                        <th data-field="code_inventaire" data-formatter="codeFormatter" data-sortable="true">Code Inventaire</th>
                        <th data-field="marque_modele" data-sortable="true">Marque & Modèle</th>
                        <th data-field="type_reseau" data-sortable="true">Type Équipement</th>
                        <th data-field="nombre_ports" data-formatter="nombrePortsFormatter" data-sortable="true">Ports</th>
                        <th data-field="vitesse_port" data-formatter="vitesseFormatter" data-sortable="true">Vitesse</th>
                        <th data-field="adresse_ip_management" data-sortable="true">IP Gestion</th>
                        <th data-field="statut" data-formatter="statutFormatter" data-sortable="true">Statut</th>
                        <th data-field="affectation">Affectation</th>
                        <th data-field="id" data-formatter="actionsFormatter" data-events="actionsEvents" data-align="right">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('parcinfo::informatique.equipements-reseau._wizard')
@include('parcinfo::informatique.ordinateurs._selection_modals')

@endsection

@push('scripts')
<script type="module" src="{{ asset('js/modules/parc-info/equipements-reseau/index.js') }}?v={{ time() }}"></script>
<script src="{{ asset('js/modules/parc-info/ordinateurs/selection_modals.js') }}?v={{ time() }}"></script>
@endpush
