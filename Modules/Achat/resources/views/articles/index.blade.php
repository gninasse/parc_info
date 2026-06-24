@extends('achat::layouts.master')

@section('title', 'Catalogue des Articles - Achat')
@section('header', 'Catalogue des Articles')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Catalogue des Articles</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
<style>
    .card-filter {
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
    }
    .modal-section-title {
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--bs-secondary);
        border-bottom: 1px solid var(--bs-border-color);
        margin-bottom: 1rem;
        padding-bottom: 0.25rem;
    }
</style>
@endpush

@section('content')

{{-- ── CARD DE FILTRES RECHERCHE ── --}}
<div class="card card-filter mb-3 rounded-1">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-type">Type d'article</label>
                <select class="form-select form-select-sm" id="filter-type">
                    <option value="">Tous les types</option>
                    @foreach(config('achat.types_articles', [
                        'equipement' => 'Équipement informatique',
                        'consommable' => 'Consommable',
                        'licence' => 'Licence logicielle'
                    ]) as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-marque">Marque</label>
                <select class="form-select form-select-sm" id="filter-marque">
                    <option value="">Toutes les marques</option>
                    @foreach($marques as $m)
                        <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-categorie">Catégorie d'équipement</label>
                <select class="form-select form-select-sm" id="filter-categorie">
                    <option value="">Toutes les catégories</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold mb-1" for="filter-status">Statut</label>
                <select class="form-select form-select-sm" id="filter-status">
                    <option value="">Tous</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
            </div>
        </div>
    </div>
</div>

{{-- ── COMPONENT: TABLE CARD ── --}}
<div class="card border-1 rounded-1">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold"><i class="fas fa-list me-2 text-primary"></i>Liste des Articles</h6>
    </div>
    <div class="card-body p-0">
        {{-- Toolbar avec boutons d'actions icon-only selon DESIGN.md --}}
        <div id="toolbar" class="d-flex gap-1">
            @can('achat.articles.create')
            <button id="btn-add" class="btn btn-sm btn-primary rounded-1" data-bs-toggle="tooltip" title="Créer un nouvel article">
                <i class="fas fa-plus"></i>
            </button>
            @endcan
            @can('achat.articles.edit')
            <button id="btn-edit" class="btn btn-sm btn-info text-white rounded-1" disabled data-bs-toggle="tooltip" title="Modifier l'article sélectionné">
                <i class="fas fa-edit"></i>
            </button>
            @endcan
            @can('achat.articles.create')
            <button id="btn-duplicate" class="btn btn-sm btn-secondary rounded-1" disabled data-bs-toggle="tooltip" title="Dupliquer l'article sélectionné">
                <i class="fas fa-clone"></i>
            </button>
            @endcan
            @can('achat.articles.edit')
            <button id="btn-toggle" class="btn btn-sm btn-warning text-white rounded-1" disabled data-bs-toggle="tooltip" title="Activer / Désactiver">
                <i class="fas fa-toggle-on"></i>
            </button>
            @endcan
            @can('achat.articles.delete')
            <button id="btn-delete" class="btn btn-sm btn-danger rounded-1" disabled data-bs-toggle="tooltip" title="Supprimer l'article sélectionné">
                <i class="fas fa-trash"></i>
            </button>
            @endcan
        </div>

        <table id="items-table"
               data-toggle="table"
               data-url="{{ route('achat.articles.index') }}"
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
               class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th data-field="state" data-radio="true"></th>
                    <th data-field="code_article" data-sortable="true" class="fw-semibold">Code</th>
                    <th data-field="designation" data-sortable="true">Désignation</th>
                    <th data-field="type_article" data-sortable="true" data-formatter="typeFormatter">Type</th>
                    <th data-field="reference_constructeur" data-sortable="true">Réf. Constructeur</th>
                    <th data-field="marque" data-sortable="true">Marque</th>
                    <th data-field="categorie" data-sortable="true">Catégorie</th>
                    <th data-field="prix_indicatif" data-sortable="true" data-formatter="priceFormatter" class="text-end">Prix Indicatif</th>
                    <th data-field="stock_actuel" data-sortable="true" class="text-center">Stock</th>
                    <th data-field="seuil_alerte" data-sortable="true" class="text-center">Seuil Alerte</th>
                    <th data-field="actif" data-sortable="true" data-formatter="statusFormatter" class="text-center">Statut</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

{{-- ── COMPONENT: MODAL FORM ── --}}
<div class="modal fade" id="item-modal" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-2 border-0 shadow-lg">
            <div class="modal-header border-0 bg-light py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalLabel">
                    <span class="text-primary">Nouveau</span> Article
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="item-form" autocomplete="off" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="item-id" name="id">
                
                <div class="modal-body py-2">
                    {{-- Onglets de Navigation --}}
                    <ul class="nav nav-tabs border-bottom mb-3" id="item-modal-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-medium py-2" id="general-tab" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab" aria-controls="tab-general" aria-selected="true">
                                <i class="fas fa-info-circle me-1"></i> Général
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-medium py-2" id="specs-tab" data-bs-toggle="tab" data-bs-target="#tab-specs" type="button" role="tab" aria-controls="tab-specs" aria-selected="false">
                                <i class="fas fa-sliders-h me-1"></i> Caractéristiques & Stocks
                            </button>
                        </li>
                    </ul>

                    {{-- Contenu des Onglets --}}
                    <div class="tab-content">
                        {{-- Onglet 1: Général --}}
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="general-tab">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="type_article">Type d'article <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="type_article" id="type_article" required>
                                        @foreach(config('achat.types_articles', [
                                            'equipement' => 'Équipement informatique',
                                            'consommable' => 'Consommable',
                                            'licence' => 'Licence logicielle'
                                        ]) as $code => $label)
                                            <option value="{{ $code }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="code_article">Code Article <span class="text-muted">(facultatif)</span></label>
                                    <input type="text" class="form-control form-control-sm text-uppercase" name="code_article" id="code_article" placeholder="Génération auto si vide">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="designation">Désignation <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" name="designation" id="designation" required placeholder="Désignation de l'article">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="marque_id">Marque <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="marque_id" id="marque_id" required>
                                        <option value="">Sélectionner une marque</option>
                                        @foreach($marques as $m)
                                            <option value="{{ $m->id }}">{{ $m->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="reference_constructeur">Référence Constructeur</label>
                                    <input type="text" class="form-control form-control-sm" name="reference_constructeur" id="reference_constructeur" placeholder="Ex: Model-XYZ">
                                </div>

                                <div class="col-md-6" id="group-categorie">
                                    <label class="form-label small fw-semibold" for="categorie_equipement_id">Catégorie d'équipement <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" name="categorie_equipement_id" id="categorie_equipement_id">
                                        <option value="">Sélectionner une catégorie</option>
                                        @foreach($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->libelle }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="fournisseur_prefere_id">Fournisseur Préféré</label>
                                    <select class="form-select form-select-sm" name="fournisseur_prefere_id" id="fournisseur_prefere_id">
                                        <option value="">Sélectionner un fournisseur</option>
                                        @foreach($fournisseurs as $f)
                                            <option value="{{ $f->id }}">{{ $f->nom }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold" for="prix_indicatif">Prix Indicatif (FCFA) <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control form-control-sm" name="prix_indicatif" id="prix_indicatif" required placeholder="0">
                                </div>
                            </div>
                        </div>

                        {{-- Onglet 2: Specs & Stocks --}}
                        <div class="tab-pane fade" id="tab-specs" role="tabpanel" aria-labelledby="specs-tab">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="unite_mesure">Unité de mesure</label>
                                    <input type="text" class="form-control form-control-sm" name="unite_mesure" id="unite_mesure" value="Unité" placeholder="Ex: Unité, Boîte, Rouleau">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="taux_tva">Taux TVA (%)</label>
                                    <input type="number" min="0" max="100" class="form-control form-control-sm" name="taux_tva" id="taux_tva" value="18" placeholder="18">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold" for="compte_comptable">Compte Comptable</label>
                                    <input type="text" class="form-control form-control-sm" name="compte_comptable" id="compte_comptable" placeholder="Ex: 601100">
                                </div>

                                <div class="col-md-6 id-none" id="group-seuil-alerte">
                                    <label class="form-label small fw-semibold" for="seuil_alerte">Seuil d'alerte stock</label>
                                    <input type="number" min="0" class="form-control form-control-sm" name="seuil_alerte" id="seuil_alerte" placeholder="Ex: 5">
                                </div>

                                <div class="col-md-6 id-none" id="group-duree-validite">
                                    <label class="form-label small fw-semibold" for="duree_validite_mois">Durée de validité (mois)</label>
                                    <input type="number" min="0" class="form-control form-control-sm" name="duree_validite_mois" id="duree_validite_mois" placeholder="Ex: 12">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="url_fiche_technique">Lien Fiche Technique</label>
                                    <input type="url" class="form-control form-control-sm" name="url_fiche_technique" id="url_fiche_technique" placeholder="https://example.com/fiche.pdf">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="image">Photo de l'article</label>
                                    <input type="file" class="form-control form-control-sm" name="image" id="image" accept="image/*">
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label small fw-semibold" for="description">Description & Spécifications</label>
                                    <textarea class="form-control form-control-sm" name="description" id="description" rows="3" placeholder="Informations complémentaires, détails techniques..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0 bg-light py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-1" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-1" id="btn-save">
                        <i class="fas fa-save me-1"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script src="{{ asset('js/modules/achat/articles/index.js') }}?v={{ time() }}"></script>
@endpush
