@extends('achat::layouts.master')

@section('title', 'Catalogue des articles - Achat')
@section('header', 'Catalogue des articles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Catalogue des articles</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- ── FILTRES ─────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1 mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-type">Type d'article</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    @foreach($typesArticles as $code => $libelle)
                        <option value="{{ $code }}">{{ $libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-marque">Marque</label>
                <select class="form-select form-select-sm" id="filter-marque">
                    <option value="">Toutes les marques</option>
                    @foreach($marques as $marque)
                        <option value="{{ $marque->id }}">{{ $marque->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-categorie">Catégorie d'équipement</label>
                <select class="form-select form-select-sm" id="filter-categorie">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $categorie)
                        <option value="{{ $categorie->id }}">{{ $categorie->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-actif">Statut</label>
                <select class="form-select form-select-sm" id="filter-actif">
                    <option value="">Tous</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── TABLE ───────────────────────────────────────────────────────────── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-primary"></i>Liste des articles</h6>
    </div>
    <div class="card-body p-0">

        {{-- DESIGN.md : barre d'outils strictement en icônes, avec infobulle. --}}
        <div id="toolbar" class="d-flex gap-1">
            @can('achat.articles.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1"
                    data-bs-toggle="tooltip" title="Créer un article">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('achat.articles.edit')
            <button id="btn-edit" class="btn btn-sm btn-info text-white rounded-1" disabled
                    data-bs-toggle="tooltip" title="Modifier l'article sélectionné">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('achat.articles.create')
            <button id="btn-duplicate" class="btn btn-sm btn-secondary rounded-1" disabled
                    data-bs-toggle="tooltip" title="Dupliquer l'article sélectionné">
                <i class="fas fa-clone"></i>
            </button>
            @endcan
            @can('achat.articles.edit')
            <button id="btn-toggle" class="btn btn-sm btn-warning text-dark rounded-1" disabled
                    data-bs-toggle="tooltip" title="Activer ou désactiver">
                <i class="fas fa-toggle-on"></i>
            </button>
            @endcan
            @can('achat.articles.delete')
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled
                    data-bs-toggle="tooltip" title="Supprimer l'article sélectionné">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('achat.articles.data') }}"
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
                    <th data-field="code_article" data-sortable="true" class="fw-semibold">Code</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="type_article" data-sortable="true" data-formatter="typeArticleFormatter">Type</th>
                    <th data-field="reference_constructeur" data-sortable="true">Réf. constructeur</th>
                    <th data-field="marque" data-sortable="true">Marque</th>
                    <th data-field="categorie" data-sortable="true">Catégorie</th>
                    <th data-field="prix_indicatif" data-sortable="true" data-formatter="prixFormatter" class="text-end">Prix indicatif</th>
                    <th data-field="taux_tva" data-sortable="true" data-formatter="tauxFormatter" class="text-center">TVA</th>
                    <th data-field="stock_actuel" data-sortable="true" class="text-center">Stock</th>
                    <th data-field="seuil_alerte" data-sortable="true" class="text-center">Seuil</th>
                    <th data-field="actif" data-sortable="true" data-formatter="actifFormatter" class="text-center">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('achat::articles._modal')

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/articles/index.js') }}?v={{ time() }}"></script>
@endpush
