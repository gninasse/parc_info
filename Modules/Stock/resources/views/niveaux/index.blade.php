@extends('stock::layouts.master')

@section('header', 'État des stocks')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item active" aria-current="page">État des stocks</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    /* Ligne sous inventaire : grisée + cadenas (UX §2) */
    tr.ligne-sous-inventaire { opacity: .55; }
</style>
@endpush

@section('content')

{{-- Carte filtres (UX §2) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small text-uppercase text-muted" for="filter-magasin">Magasin</label>
                <select id="filter-magasin" class="form-select form-select-sm">
                    <option value="">Tous les magasins</option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected($magasinPreselectionne === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-uppercase text-muted" for="filter-nature">Nature</label>
                <select id="filter-nature" class="form-select form-select-sm">
                    <option value="">Toutes</option>
                    <option value="consommable">C — Consommables</option>
                    <option value="piece">P — Pièces</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-uppercase text-muted" for="filter-categorie">Catégorie</label>
                <select id="filter-categorie" class="form-select form-select-sm">
                    <option value="">Toutes les catégories</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-uppercase text-muted d-block">Statut d'alerte</label>
                {{-- Segmented control : icône + texte, jamais la couleur seule (S7) --}}
                <div class="btn-group btn-group-sm w-100" role="group" aria-label="Statut d'alerte" id="filter-statut">
                    <input type="radio" class="btn-check" name="statut" id="statut-tous" value="" checked>
                    <label class="btn btn-outline-secondary" for="statut-tous">Tous</label>
                    <input type="radio" class="btn-check" name="statut" id="statut-ok" value="OK" @checked($statutPreselectionne === 'OK')>
                    <label class="btn btn-outline-success" for="statut-ok">✓ OK</label>
                    <input type="radio" class="btn-check" name="statut" id="statut-sous-seuil" value="SOUS_SEUIL" @checked($statutPreselectionne === 'SOUS_SEUIL')>
                    <label class="btn btn-outline-warning" for="statut-sous-seuil">⚠ Sous seuil</label>
                    <input type="radio" class="btn-check" name="statut" id="statut-rupture" value="RUPTURE" @checked($statutPreselectionne === 'RUPTURE')>
                    <label class="btn btn-outline-danger" for="statut-rupture">⛔ Rupture</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="bi bi-clipboard-data me-2 text-primary"></i>État des stocks</h6>
        <div class="d-flex gap-2">
            @can('stock.niveaux.seuil')
            <button id="btn-seuil-article" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-sliders me-1"></i>Seuil sur un article
            </button>
            @endcan
            <div class="btn-group">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i>Exporter
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item export-lien" href="#" data-format="csv">CSV</a></li>
                    <li><a class="dropdown-item export-lien" href="#" data-format="xlsx">Excel</a></li>
                    <li><a class="dropdown-item export-lien" href="#" data-format="pdf">PDF</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body p-0">
        <table id="niveaux-table"
               data-toggle="table"
               data-url="{{ route('stock.niveaux.data') }}"
               data-pagination="true"
               data-side-pagination="server"
               data-search="true"
               data-show-refresh="true"
               data-show-columns="true"
               data-page-list="[10, 25, 50, 100]"
               data-page-size="10"
               data-locale="fr-FR"
               data-row-attributes="niveauRowAttributes"
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="nature" data-formatter="natureBadgeFormatter" data-align="center">Nature</th>
                    <th data-field="article_nom" data-formatter="articleFormatter" data-sortable="true" data-sort-name="article">Article</th>
                    <th data-field="magasin" data-sortable="true" data-sort-name="magasin">Magasin</th>
                    <th data-field="quantite" data-sortable="true" data-align="end">Quantité</th>
                    <th data-field="unite">Unité</th>
                    <th data-field="seuil_effectif" data-formatter="seuilEffectifFormatter" data-sortable="true" data-sort-name="seuil_effectif">Seuil effectif</th>
                    <th data-field="statut" data-formatter="niveauStatutFormatter" data-align="center">Statut</th>
                    <th data-field="valeur" data-formatter="fcfaFormatter" data-sortable="true" data-sort-name="valeur" data-align="end">Valeur</th>
                    <th data-field="actions" data-formatter="niveauActionsFormatter" data-align="center">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

@include('stock::niveaux._modal_seuil')
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>window.PEUT_AJUSTER_SEUIL = @json(auth()->user()->can('stock.niveaux.seuil'));</script>
<script type="module" src="{{ asset('js/modules/stock/niveaux/index.js') }}?v={{ time() }}"></script>
@endpush
