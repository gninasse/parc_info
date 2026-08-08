@extends('stock::layouts.master')

@section('header', 'Fiche magasin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.magasins.index') }}">Magasins</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $magasin->code }}</li>
@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('plugins/bootstrap-table/bootstrap-table.min.css') }}">
@endpush

@section('content')

{{-- En-tête de fiche (UX §9) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
            <i class="bi bi-shop fs-4 text-primary"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">{{ $magasin->libelle }}</h5>
                <span class="badge bg-light text-dark font-monospace">{{ $magasin->code }}</span>
                @if($magasin->est_actif)
                    <span class="badge bg-success"><i class="bi bi-check-lg me-1"></i>Actif</span>
                @else
                    <span class="badge bg-danger"><i class="bi bi-x-lg me-1"></i>Inactif</span>
                @endif
            </div>
            <div class="text-muted small mt-1">
                <i class="bi bi-geo-alt me-1"></i>{{ $magasin->site?->libelle ?? '—' }}
                <span class="mx-2">·</span>
                <i class="bi bi-door-closed me-1"></i>{{ $magasin->local?->libelle ?? 'Aucun local' }}
                <span class="mx-2">·</span>
                <i class="bi bi-person me-1"></i>{{ $magasin->responsable ? trim($magasin->responsable->prenom.' '.$magasin->responsable->nom) : 'Aucun responsable' }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('stock.magasins.update')
            <button id="btn-edit" class="btn btn-outline-primary btn-sm"><i class="fas fa-edit me-1"></i>Modifier</button>
            @endcan
            @can('stock.magasins.toggle-status')
            <button id="btn-toggle" class="btn btn-outline-warning btn-sm"
                    @if($magasin->est_actif && $motifsBlocage !== [])
                    data-bs-toggle="tooltip" title="Magasin garni : contient {{ implode(' et ', $motifsBlocage) }}"
                    @endif>
                <i class="fas fa-power-off me-1"></i>{{ $magasin->est_actif ? 'Désactiver' : 'Activer' }}
            </button>
            @endcan
            <a href="{{ route('stock.magasins.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Retour</a>
        </div>
    </div>
</div>

{{-- 3 mini-KPI --}}
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="bi bi-boxes text-primary"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $kpis['nb_references'] }}</div>
                    <div class="text-muted small text-uppercase">Références en stock</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-dark bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="bi bi-pc-display text-dark"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ $kpis['nb_equipements'] }}</div>
                    <div class="text-muted small text-uppercase">Équipements présents</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;">
                    <i class="bi bi-cash-stack text-success"></i>
                </div>
                <div>
                    <div class="fs-4 fw-bold">{{ number_format($kpis['valeur_estimee'], 0, ',', ' ') }} <span class="fs-6 fw-normal">FCFA</span></div>
                    <div class="text-muted small text-uppercase">Valeur estimée</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Onglets : État des stocks / Équipements présents / Derniers mouvements --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#onglet-stocks" type="button">État des stocks</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-equipements" type="button">Équipements présents</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-mouvements" type="button">Derniers mouvements</button></li>
        </ul>
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="onglet-stocks">
            <table id="table-stocks"
                   data-url="{{ route('stock.niveaux.data', ['magasin_id' => $magasin->id]) }}"
                   data-pagination="true" data-side-pagination="server" data-page-size="10" data-locale="fr-FR"
                   class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th data-field="nature" data-formatter="natureBadgeFormatter" data-align="center">Nature</th>
                        <th data-field="article_nom" data-formatter="articleFormatter">Article</th>
                        <th data-field="quantite" data-align="end">Quantité</th>
                        <th data-field="unite">Unité</th>
                        <th data-field="seuil_effectif" data-formatter="seuilEffectifFormatter">Seuil effectif</th>
                        <th data-field="statut" data-formatter="niveauStatutFormatter" data-align="center">Statut</th>
                        <th data-field="valeur" data-formatter="fcfaFormatter" data-align="end">Valeur</th>
                    </tr>
                </thead>
            </table>
            <div class="text-end mt-2">
                <a href="{{ route('stock.niveaux.index', ['magasin_id' => $magasin->id]) }}" class="small">Voir l'état des stocks complet →</a>
            </div>
        </div>
        <div class="tab-pane fade" id="onglet-equipements">
            <table id="table-equipements"
                   data-url="{{ route('stock.magasins.equipements-data', $magasin->id) }}"
                   data-pagination="true" data-side-pagination="server" data-page-size="10" data-locale="fr-FR"
                   class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th data-field="code_inventaire">Code</th>
                        <th data-field="modele">Modèle</th>
                        <th data-field="numero_serie" data-formatter="monospaceFormatter">N° de série</th>
                        <th data-field="statut">Statut</th>
                        <th data-field="date_rattachement">Rattaché le</th>
                    </tr>
                </thead>
            </table>
        </div>
        <div class="tab-pane fade" id="onglet-mouvements">
            <table id="table-mouvements"
                   data-url="{{ route('stock.magasins.mouvements-data', $magasin->id) }}"
                   data-pagination="true" data-side-pagination="server" data-page-size="10" data-locale="fr-FR"
                   class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th data-field="date">Date</th>
                        <th data-field="type" data-formatter="typeMouvementFormatter" data-align="center">Type</th>
                        <th data-field="article">Article / Équipement</th>
                        <th data-field="quantite_signee" data-align="end">Qté</th>
                        <th data-field="par">Par</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@include('stock::magasins._modal')
@include('stock::shared._erreurs_formulaire', ['id' => 'erreurs-hors-modale'])
@endsection

@push('js')
<script src="{{ asset('plugins/bootstrap-table/bootstrap-table.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap-table/locale/bootstrap-table-fr-FR.min.js') }}"></script>
<script>window.MAGASIN_ID = {{ $magasin->id }};</script>
<script type="module" src="{{ asset('js/modules/stock/magasins/show.js') }}?v={{ time() }}"></script>
@endpush
