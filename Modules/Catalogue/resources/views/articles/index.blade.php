@extends('core::layouts.master')

@section('header', 'Catalogue des articles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Catalogue</li>
    <li class="breadcrumb-item active" aria-current="page">Articles</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    /* Cartes radio de nature (modale) — pattern des cartes du wizard ParcInfo */
    .carte-nature { cursor: pointer; border: 2px solid var(--bs-border-color); border-radius: .5rem; transition: border-color .15s; position: relative; }
    .carte-nature:hover { border-color: var(--bs-primary); }
    .carte-nature.selectionnee { border-color: var(--bs-primary); background: rgba(13,110,253,.04); }
    .carte-nature.selectionnee .coche-nature { display: inline-flex; }
    .coche-nature { display: none; position: absolute; top: .4rem; right: .4rem; color: var(--bs-primary); }
    .carte-nature.verrouillee { cursor: not-allowed; opacity: .65; }
</style>
@endpush

@section('content')

{{-- KPI par nature --}}
<div class="row g-3 mb-3">
    @foreach([
        'consommable' => ['Consommables', 'fas fa-box-open', 'info'],
        'piece' => ['Pièces détachées', 'fas fa-cogs', 'primary'],
        'equipement' => ['Modèles d\'équipements', 'fas fa-desktop', 'dark'],
        'licence' => ['Licences', 'fas fa-key', 'secondary'],
        'prestation' => ['Prestations', 'fas fa-handshake', 'success'],
    ] as $nature => [$libelle, $icone, $couleur])
    <div class="col-md">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-{{ $couleur }} bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="{{ $icone }} text-{{ $couleur }}"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold" id="kpi-{{ $nature }}">{{ $kpis[$nature] }}</div>
                    <div class="text-muted small text-uppercase">{{ $libelle }}</div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Filtres --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" for="filter-nature">Nature</label>
                <select class="form-select form-select-sm" id="filter-nature">
                    <option value="">Toutes</option>
                    <option value="consommable">Consommable</option>
                    <option value="piece">Pièce détachée</option>
                    <option value="equipement">Équipement</option>
                    <option value="licence">Licence</option>
                    <option value="prestation">Prestation</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-categorie">Catégorie</label>
                <select class="form-select form-select-sm" id="filter-categorie">
                    <option value="">Toutes</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" for="filter-sous-categorie">Sous-catégorie</label>
                <select class="form-select form-select-sm" id="filter-sous-categorie" disabled>
                    <option value="">Toutes</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-fournisseur">Fournisseur</label>
                <select class="form-select form-select-sm" id="filter-fournisseur">
                    <option value="">Tous</option>
                    @foreach($fournisseurs as $f)
                        <option value="{{ $f->id }}">{{ $f->raison_sociale }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold mb-1" for="filter-statut">Statut</label>
                <select class="form-select form-select-sm" id="filter-statut">
                    <option value="">Tous</option>
                    <option value="1">Actifs</option>
                    <option value="0">Inactifs</option>
                </select>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3">
        <h6 class="mb-0 fw-bold"><i class="fas fa-boxes me-2 text-primary"></i>Liste des articles</h6>
    </div>
    <div class="card-body p-0">

        <div id="toolbar">
            @can('catalogue.articles.store')
            <button id="btn-add" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="Ajouter">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('catalogue.articles.update')
            <button id="btn-edit" class="btn btn-info btn-sm" disabled data-bs-toggle="tooltip" title="Modifier">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('catalogue.articles.store')
            <button id="btn-duplicate" class="btn btn-secondary btn-sm" disabled data-bs-toggle="tooltip" title="Dupliquer">
                <i class="fas fa-copy"></i>
            </button>
            @endcan
            @can('catalogue.articles.toggle-status')
            <button id="btn-toggle" class="btn btn-warning btn-sm" disabled data-bs-toggle="tooltip" title="Activer/Désactiver">
                <i class="fas fa-power-off"></i>
            </button>
            @endcan
            @can('catalogue.articles.destroy')
            <button id="btn-delete" class="btn btn-danger btn-sm" disabled data-bs-toggle="tooltip" title="Supprimer">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="articles-table"
               data-toggle="table"
               data-url="{{ route('catalogue.articles.data') }}"
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
               data-page-size="10"
               data-locale="fr-FR"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code" data-sortable="true">Code</th>
                    <th data-field="nom" data-sortable="true">Désignation</th>
                    <th data-field="nature" data-formatter="natureBadgeFormatter" data-align="center">Nature</th>
                    <th data-field="categorie_chemin">Catégorie</th>
                    <th data-field="marque_libelle">Marque</th>
                    <th data-field="modele" data-sortable="true">Modèle</th>
                    <th data-field="unite_stock" data-sortable="true">Unité</th>
                    <th data-field="prix_indicatif" data-sortable="true" data-formatter="fcfaFormatter" data-align="end">Prix indicatif</th>
                    <th data-field="seuil_defaut" data-sortable="true" data-formatter="seuilFormatter" data-align="center">Seuil</th>
                    <th data-field="est_actif" data-sortable="true" data-formatter="statutFormatter" data-align="center">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('catalogue::articles._modal')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/catalogue/articles/index.js') }}?v={{ time() }}"></script>
@endpush
