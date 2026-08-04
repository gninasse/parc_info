@extends('stock::layouts.master')

@section('header', 'Sorties')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item active" aria-current="page">Sorties</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-uppercase text-muted" for="filter-statut">Statut</label>
                <select id="filter-statut" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    <option value="BROUILLON">Brouillon</option>
                    <option value="POINTAGE">Pointage en cours</option>
                    <option value="VALIDE">Validé</option>
                    <option value="ANNULE">Annulé</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-uppercase text-muted" for="filter-magasin">Magasin</label>
                <select id="filter-magasin" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}">{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-uppercase text-muted">Période</label>
                <div class="input-group input-group-sm">
                    <input type="date" id="filter-du" class="form-control">
                    <span class="input-group-text">→</span>
                    <input type="date" id="filter-au" class="form-control">
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="bi bi-box-arrow-up me-2 text-primary"></i>Bons de sortie</h6>
    </div>
    <div class="card-body p-0">
        <div id="toolbar">
            @can('stock.sorties.store')
            <a href="{{ route('stock.sorties.create') }}" id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </a>
            @endcan
            @can('stock.sorties.index')
            <button id="btn-show" class="btn btn-secondary btn-sm" disabled data-bs-toggle="tooltip" title="Voir">
                <i class="fas fa-eye"></i>
            </button>
            @endcan
            @can('stock.sorties.update')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('stock.sorties.destroy')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
            @can('stock.sorties.store')
            <button id="btn-imprimer" class="btn btn-outline-primary btn-sm" disabled data-bs-toggle="tooltip" title="Imprimer (bons validés)">
                <i class="bi bi-printer"></i>
            </button>
            @endcan
        </div>

        <table id="sorties-table"
               data-toggle="table"
               data-url="{{ route('stock.sorties.data') }}"
               data-pagination="true" data-side-pagination="server" data-search="true"
               data-show-refresh="true" data-show-columns="true" data-toolbar="#toolbar"
               data-click-to-select="true" data-single-select="true" data-id-field="id"
               data-page-list="[10, 25, 50, 100]" data-page-size="10" data-locale="fr-FR"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="numero_affiche" data-formatter="numeroFormatter" data-sortable="true" data-sort-name="numero">Numéro</th>
                    <th data-field="date_document" data-sortable="true" data-sort-name="date_document">Date</th>
                    <th data-field="magasin">Magasin</th>
                    <th data-field="beneficiaire" data-formatter="beneficiaireFormatter">Bénéficiaire</th>
                    <th data-field="remis_a">Remis à</th>
                    <th data-field="nb_lignes" data-align="center">Lignes</th>
                    <th data-field="statut" data-formatter="documentStatutFormatter" data-align="center">Statut</th>
                    <th data-field="cree_par">Créé par</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@include('stock::shared._modal_pdf')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/stock/sorties/index.js') }}?v={{ time() }}"></script>
@endpush
