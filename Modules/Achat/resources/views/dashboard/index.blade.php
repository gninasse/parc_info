@extends('achat::layouts.master')

@section('header', 'Tableau de bord')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Achat</li>
    <li class="breadcrumb-item active" aria-current="page">Tableau de bord</li>
@endsection

@push('css')
<style>
    .kpi { border-left: 4px solid transparent; }
    .kpi-action { border-left-color: #dc3545; }
    .kpi-vigilance { border-left-color: #fd7e14; }
    .kpi a.stretched-link { text-decoration: none; }
    /* Dette d'intérim : orange hachuré (SPEC_UX §0.2) */
    .kpi-interim {
        border-left-color: #fd7e14;
        background-image: repeating-linear-gradient(45deg, transparent, transparent 8px, rgba(253,126,20,.06) 8px, rgba(253,126,20,.06) 16px);
    }
</style>
@endpush

@section('content')

@php
    // Un module vide n'a rien à raconter : on affiche l'encart pédagogique
    // plutôt que six zéros alignés (EV-01).
    $moduleVide = $kpis['bons_ouverts'] === 0
        && ($kpis['a_valider'] ?? 0) === 0
        && (float) $kpis['engage_du_mois'] === 0.0;
@endphp

{{-- ── Z1 : les cartes KPI (SPEC_UX A-01) ─────────────────────────────── --}}
<div class="row g-3 mb-3">
    {{-- « À valider » : carte masquée à qui ne détient pas le visa --}}
    @if($kpis['a_valider'] !== null)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 kpi {{ $kpis['a_valider'] > 0 ? 'kpi-action' : '' }} position-relative">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1 {{ $kpis['a_valider'] > 0 ? 'text-danger' : 'text-muted' }}">
                        <i class="bi bi-pen"></i>
                        <span class="small text-uppercase text-muted">À valider</span>
                    </div>
                    <div class="fs-3 fw-bold">{{ number_format($kpis['a_valider'], 0, ',', ' ') }}</div>
                    <div class="small text-muted">bon(s) en attente de visa</div>
                    @if(Route::has('achat.bons-commande.index'))
                        <a href="{{ route('achat.bons-commande.index', ['statut' => 'SOUMIS']) }}"
                           class="stretched-link" aria-label="Voir les bons à valider"></a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1 text-success">
                    <i class="bi bi-cash-stack"></i>
                    <span class="small text-uppercase text-muted">Engagé du mois</span>
                </div>
                {{-- Tout montant est qualifié HT ou TTC, sans exception (§0.4) --}}
                <div class="fs-4 fw-bold">
                    {{ number_format($kpis['engage_du_mois'], 0, ',', ' ') }}
                    <span class="fs-6 fw-normal">FCFA HT</span>
                </div>
                <div class="small text-muted">hors régularisations</div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi position-relative">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1 text-primary">
                    <i class="bi bi-folder2-open"></i>
                    <span class="small text-uppercase text-muted">BC ouverts</span>
                </div>
                <div class="fs-3 fw-bold">{{ number_format($kpis['bons_ouverts'], 0, ',', ' ') }}</div>
                <div class="small text-muted">validés ou partiellement livrés</div>
                @if(Route::has('achat.bons-commande.index'))
                    <a href="{{ route('achat.bons-commande.index', ['statut' => 'OUVERTS']) }}"
                       class="stretched-link" aria-label="Voir les bons ouverts"></a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 kpi {{ $kpis['reliquats_anciens'] > 0 ? 'kpi-vigilance' : '' }} position-relative">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-2 mb-1 {{ $kpis['reliquats_anciens'] > 0 ? 'text-warning' : 'text-muted' }}">
                    <i class="bi bi-hourglass-split"></i>
                    <span class="small text-uppercase text-muted">Reliquats &gt; {{ $delaiAlerteReliquat }} j</span>
                </div>
                <div class="fs-3 fw-bold">{{ number_format($kpis['reliquats_anciens'], 0, ',', ' ') }}</div>
                <div class="small text-muted">ligne(s) non soldée(s)</div>
                @if(Route::has('achat.reliquats.index'))
                    <a href="{{ route('achat.reliquats.index', ['age' => $delaiAlerteReliquat]) }}"
                       class="stretched-link" aria-label="Voir les reliquats anciens"></a>
                @endif
            </div>
        </div>
    </div>

    {{-- Dette d'intérim : carte MASQUÉE à zéro — l'objectif est qu'elle
         disparaisse, pas qu'elle s'affiche éternellement (UX2-12) --}}
    @if($kpis['dette_interim'] > 0)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 kpi kpi-interim position-relative">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1 text-warning">
                        <i class="bi bi-clock-history"></i>
                        <span class="small text-uppercase text-muted">Dette d'intérim</span>
                    </div>
                    <div class="fs-3 fw-bold">{{ number_format($kpis['dette_interim'], 0, ',', ' ') }}</div>
                    <div class="small text-muted">équipement(s) sans commande d'origine</div>
                </div>
            </div>
        </div>
    @endif
</div>

@if($moduleVide)
    {{-- EV-01 — module vide : on enseigne le circuit plutôt que d'afficher du vide --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-cart-plus fs-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold">Aucun bon de commande pour l'instant.</h5>
            <p class="text-muted mb-4">
                Le circuit en une phrase :
                <strong>Catalogue → bon de commande → visa → réception au magasin → parc</strong>.
            </p>
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                @can('achat.bons_commande.store')
                    @if(Route::has('achat.bons-commande.create'))
                        <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i>Créer le premier BC
                        </a>
                    @endif
                @endcan
                @if(Route::has('catalogue.articles.index'))
                    <a href="{{ route('catalogue.articles.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-journal-bookmark me-1"></i>Explorer le Catalogue
                    </a>
                @endif
            </div>
        </div>
    </div>
@else
    <div class="row g-3">
        {{-- ── Z3 : reliquats les plus anciens ────────────────────────── --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-hourglass-split me-2 text-warning"></i>Reliquats les plus anciens
                    </h6>
                    @if(Route::has('achat.reliquats.index'))
                        <a href="{{ route('achat.reliquats.index') }}" class="small">Voir tous les reliquats →</a>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Bon</th>
                                <th>Article</th>
                                <th>Fournisseur</th>
                                <th class="text-end">Reste</th>
                                <th class="text-center">Âge</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reliquatsAnciens as $ligne)
                                <tr>
                                    <td class="font-monospace small">{{ $ligne['numero'] }}</td>
                                    <td>{{ $ligne['designation'] }}</td>
                                    <td class="small">{{ $ligne['fournisseur'] }}</td>
                                    <td class="text-end">
                                        {{ rtrim(rtrim(number_format($ligne['reste'], 2, ',', ' '), '0'), ',') }}
                                    </td>
                                    <td class="text-center">
                                        {{-- Icône + texte, jamais la couleur seule (accessibilité) --}}
                                        @php
                                            $ancien = $ligne['age_jours'] > $delaiAlerteReliquat;
                                        @endphp
                                        <span class="badge {{ $ancien ? 'bg-warning text-dark' : 'bg-secondary-subtle text-secondary-emphasis' }}">
                                            {{ $ancien ? '⚠ ' : '' }}{{ $ligne['age_jours'] }} j
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-check-circle text-success me-1"></i>
                                        Aucun reliquat — toutes les commandes validées sont soldées.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- ── Z5 : actions rapides ───────────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-lightning-charge me-2 text-primary"></i>Actions rapides</h6>
                </div>
                <div class="card-body d-grid gap-2">
                    @can('achat.bons_commande.store')
                        @if(Route::has('achat.bons-commande.create'))
                            <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-primary">
                                <i class="bi bi-plus-lg me-1"></i>Nouveau bon de commande
                            </a>
                        @endif
                    @endcan
                    @if(Route::has('achat.bons-commande.index'))
                        <a href="{{ route('achat.bons-commande.index', ['auteur' => 'moi', 'statut' => 'BROUILLON']) }}"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-pencil-square me-1"></i>Mes brouillons
                        </a>
                    @endif
                    @can('achat.rapports.view')
                        @if(Route::has('achat.rapports.index'))
                            <a href="{{ route('achat.rapports.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-graph-up me-1"></i>Rapports
                            </a>
                        @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
