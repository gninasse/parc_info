@extends('parcinfo::layouts.master')

@section('header', 'Centre d\'impression d\'étiquettes')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('parc-info.dashboard') }}">Parc Info</a></li>
    <li class="breadcrumb-item active">Impression d'étiquettes</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .filter-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background-color: #fff;
    }
    .table-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        background-color: #fff;
        overflow: hidden;
    }
</style>
@endpush

@section('content')

{{-- ── Header Info ── --}}
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-3 py-3" role="alert">
            <div class="fs-3"><i class="bi bi-info-circle-fill"></i></div>
            <div>
                <h6 class="alert-heading mb-1 fw-bold">Centre d'impression d'étiquettes</h6>
                <p class="mb-0 small opacity-75">
                    Sélectionnez plusieurs équipements, toutes catégories confondues, pour générer et imprimer leurs étiquettes d'inventaire par lot (optimisé pour les imprimantes thermiques 80mm x 50mm).
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ── Filtres de recherche ── --}}
<div class="card filter-card mb-4">
    <div class="card-body py-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Catégorie d'équipement</label>
                <select class="form-select" id="filter-categorie">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Site géographique</label>
                <select class="form-select" id="filter-site">
                    <option value="">Tous les sites</option>
                    @foreach($sites as $s)
                        <option value="{{ $s->id }}">{{ $s->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Direction / Service</label>
                <select class="form-select" id="filter-direction">
                    <option value="">Toutes les directions</option>
                    @foreach($directions as $d)
                        <option value="{{ $d->id }}">{{ $d->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Statut de l'actif</label>
                <select class="form-select" id="filter-statut">
                    <option value="">Tous les statuts</option>
                    <option value="en_service">En service</option>
                    <option value="en_stock">En stock</option>
                    <option value="en_stock_magasin">En magasin</option>
                    <option value="en_stock_dsi">Stock DSI</option>
                    <option value="en_reparation">En réparation</option>
                    <option value="perdu">Perdu / Volé</option>
                    <option value="reforme">Réformé</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-1" id="btn-apply-filters">
                    <i class="bi bi-funnel-fill"></i> Filtrer
                </button>
                <button class="btn btn-outline-secondary w-100 py-2 d-flex align-items-center justify-content-center" id="btn-reset-filters" title="Réinitialiser">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── Tableau des équipements ── --}}
<div class="card table-card">
    <div class="card-header bg-white border-0 py-3 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 fw-bold text-secondary"><i class="bi bi-list-check me-2"></i>Équipements du parc</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar" class="d-flex align-items-center gap-2">
            <button id="btn-print-selected" class="btn btn-primary d-flex align-items-center gap-2 py-2 px-3 shadow-sm" disabled>
                <i class="bi bi-printer-fill fs-5"></i> 
                <span>Imprimer la sélection (<strong id="selected-count">0</strong>)</span>
            </button>
        </div>
        
        <table id="equipements-print-table"
               data-toggle="table"
               data-url="{{ route('parc-info.equipements.etiquettes-data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-toolbar="#toolbar"
               data-click-to-select="true"
               data-single-select="false"
               data-id-field="id"
               data-page-list="[10,25,50,100]"
               data-page-size="25"
               data-query-params="printQueryParams"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-checkbox="true"></th>
                    <th data-field="code_inventaire" data-sortable="true" data-formatter="codePrintFormatter">Code Inventaire</th>
                    <th data-field="categorie_libelle" data-sortable="true">Catégorie</th>
                    <th data-field="marque_modele" data-sortable="true">Marque & Modèle</th>
                    <th data-field="numero_serie" data-sortable="true">N° Série</th>
                    <th data-field="statut" data-formatter="statutPrintFormatter">Statut</th>
                    <th data-field="affectation">Affectation actuelle</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@endsection

@push('js')
<script>
    window.printCenterConfig = {
        dataUrl: '{{ route('parc-info.equipements.etiquettes-data') }}',
        printUrl: '{{ route('parc-info.equipements.imprimer-etiquettes') }}'
    };
</script>
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/parc-info/centre-impression.js') }}?v={{ time() }}"></script>
@endpush
