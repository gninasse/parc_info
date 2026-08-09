@extends('achat::layouts.master')

@section('header', 'Rapports')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Rapports</li>
@endsection

@push('css')
<style>
    .carte-rapport { cursor: pointer; transition: border-color .15s; border: 1px solid var(--bs-border-color); }
    .carte-rapport:hover { border-color: var(--bs-primary); }
    .carte-rapport.active { border-color: var(--bs-primary); box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
    .carte-indisponible { opacity: .6; cursor: not-allowed; }
    #apercu table { font-size: .875rem; }
</style>
@endpush

@section('content')

{{-- ── Filtres communs à toutes les cartes ─────────────────────────────── --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small text-uppercase text-muted" for="filter-du">Du</label>
                <input type="date" id="filter-du" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small text-uppercase text-muted" for="filter-au">Au</label>
                <input type="date" id="filter-au" class="form-control form-control-sm">
            </div>
            <div class="col-lg-3">
                <label class="form-label small text-uppercase text-muted" for="filter-fournisseur">Fournisseur</label>
                <select id="filter-fournisseur" class="form-select form-select-sm">
                    <option value="">Tous</option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}">{{ $fournisseur->raison_sociale }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3">
                {{-- UX-17 : DÉCOCHÉE par défaut partout. Les régularisations
                     documentent le passé, elles ne sont pas de l'activité. --}}
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="filter-regularisations">
                    <label class="form-check-label small" for="filter-regularisations">
                        Inclure les régularisations
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Grille de cartes ────────────────────────────────────────────────── --}}
<div class="row g-3 mb-3" id="cartes">
    @foreach([
        'etat-bons' => ['État des bons de commande', 'bi-card-checklist', 'Effectifs et montants par statut'],
        'depenses-fournisseur' => ['Dépenses par fournisseur', 'bi-truck', 'Qui reçoit la dépense de l\'établissement'],
        'depenses-categorie' => ['Dépenses par catégorie', 'bi-tags', 'Calculé sur les lignes, pas sur les bons'],
        'evolution' => ['Évolution sur 12 mois', 'bi-graph-up', 'Mois vides compris, en ordre chronologique'],
        'reliquats' => ['Reliquats', 'bi-hourglass-split', 'L\'état A-06, imprimable'],
        'regularisation' => ['Régularisation de l\'intérim', 'bi-link-45deg', 'Bons de régularisation et dette restante'],
    ] as $cle => [$titre, $icone, $aide])
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 carte-rapport" data-carte="{{ $cle }}">
                <div class="card-body">
                    <h6 class="fw-bold mb-1"><i class="bi {{ $icone }} me-2 text-primary"></i>{{ $titre }}</h6>
                    <p class="small text-muted mb-0">{{ $aide }}</p>
                    @if($cle === 'regularisation' && $detteInterim > 0)
                        <span class="badge bg-warning text-dark mt-2">
                            {{ $detteInterim }} équipement(s) non rattaché(s)
                        </span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    {{-- Carte SIGNAUX : permission dédiée (UX4-09) --}}
    @can('achat.rapports.signaux')
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 carte-rapport border-warning" data-carte="signaux">
                <div class="card-body">
                    <h6 class="fw-bold mb-1">
                        <i class="bi bi-shield-exclamation me-2 text-warning"></i>Signaux
                        <i class="bi bi-lock-fill small text-muted" data-bs-toggle="tooltip"
                           title="Permission dédiée : achat.rapports.signaux"></i>
                    </h6>
                    <p class="small text-muted mb-0">
                        8 indicateurs de vigilance — ils signalent, ils n'accusent pas.
                    </p>
                </div>
            </div>
        </div>
    @endcan

    {{-- UX-18 : l'attente reste VISIBLE plutôt que d'être passée sous silence --}}
    @unless($imputationDisponible)
        <div class="col-md-6 col-lg-4">
            <div class="card h-100 carte-indisponible">
                <div class="card-body">
                    <h6 class="fw-bold mb-1 text-muted"><i class="bi bi-calculator me-2"></i>Imputation comptable</h6>
                    <p class="small text-muted mb-0">
                        Bientôt disponible — en attente du champ « compte comptable »
                        au Catalogue (PRQ-03).
                    </p>
                </div>
            </div>
        </div>
    @endunless
</div>

{{-- ── Aperçu de la carte choisie ──────────────────────────────────────── --}}
<div class="card border-0 shadow-sm d-none" id="bloc-apercu">
    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-bold" id="apercu-titre">—</h6>

        @can('achat.rapports.export')
            <div class="btn-group btn-group-sm" id="exports">
                <button class="btn btn-outline-success" data-format="csv">
                    <i class="fas fa-file-csv me-1"></i>CSV
                </button>
                <button class="btn btn-outline-success" data-format="xlsx">
                    <i class="fas fa-file-excel me-1"></i>XLSX
                </button>
                <button class="btn btn-outline-danger" data-format="pdf">
                    <i class="fas fa-file-pdf me-1"></i>PDF
                </button>
            </div>
        @endcan
    </div>

    <div class="card-body">
        <div class="small text-muted mb-2" id="apercu-filtres"></div>
        <div class="table-responsive" id="apercu"></div>
    </div>
</div>

@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/achat/rapports/index.js') }}?v={{ time() }}"></script>
@endpush
