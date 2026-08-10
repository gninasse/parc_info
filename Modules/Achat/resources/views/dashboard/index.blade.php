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
    /*
        Zone du graphique — hauteur FIXÉE, et c'est indispensable.

        Chart.js en `responsive: true` + `maintainAspectRatio: false` calcule
        la taille du canvas d'après celle de son PARENT. Si le parent n'a pas
        de hauteur propre, il prend celle de son contenu — donc celle du
        canvas : le canvas grandit, le parent grandit, le canvas grandit
        encore. La boucle ne converge jamais et fige l'onglet.

        Le `position: relative` fait partie du correctif : sans lui, le canvas
        absolu de Chart.js se dimensionnerait par rapport à un ancêtre plus
        haut dans l'arbre, et la hauteur imposée ici ne servirait à rien.

        Même convention que `Modules/Stock/.../statistiques` (.zone-graphique).
    */
    .zone-graphique { position: relative; height: 300px; width: 100%; }
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

    {{-- En cours de réception : lecture croisée du Stock. La carte est
         MASQUÉE si la lecture échoue (null) — annoncer zéro dirait « rien
         en cours », ce qui serait un mensonge (dégradation partielle). --}}
    @if($kpis['en_cours_reception'] !== null)
        <div class="col-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100 kpi position-relative">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center gap-2 mb-1 text-info">
                        <i class="bi bi-truck"></i>
                        <span class="small text-uppercase text-muted">En cours de réception</span>
                    </div>
                    <div class="fs-3 fw-bold">{{ number_format($kpis['en_cours_reception'], 0, ',', ' ') }}</div>
                    <div class="small text-muted">bon(s) d'entrée en saisie au magasin</div>
                    @if(Route::has('stock.entrees.index'))
                        <a href="{{ route('stock.entrees.index') }}"
                           class="stretched-link" aria-label="Voir les bons d'entrée du magasin"></a>
                    @endif
                </div>
            </div>
        </div>
    @endif

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
    {{-- ── Z2 : dépenses engagées, 12 mois glissants ──────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <h6 class="mb-0 fw-bold">
                <i class="bi bi-graph-up me-2 text-primary"></i>Dépenses engagées — 12 mois glissants
            </h6>

            {{-- Bascule HT/TTC : par défaut HT (la dépense négociée) --}}
            <div class="btn-group btn-group-sm" role="group" aria-label="Base de montant">
                <input type="radio" class="btn-check" name="base-montant" id="base-ht" value="ht" checked autocomplete="off">
                <label class="btn btn-outline-secondary" for="base-ht">HT</label>
                <input type="radio" class="btn-check" name="base-montant" id="base-ttc" value="ttc" autocomplete="off">
                <label class="btn btn-outline-secondary" for="base-ttc">TTC</label>
            </div>
        </div>
        <div class="card-body">
            {{-- Les régularisations en sont exclues (elles documentent le
                 passé) : le graphique décrit l'activité, pas le rattrapage.

                 Le canvas DOIT rester dans .zone-graphique : c'est ce
                 conteneur à hauteur fixée qui empêche la boucle de
                 redimensionnement de Chart.js (voir la CSS en tête de vue). --}}
            <div class="zone-graphique">
                <canvas id="graphique-evolution"
                        data-evolution='@json($evolution)'
                        aria-label="Dépenses engagées par mois sur douze mois"></canvas>
            </div>

            {{-- Équivalent textuel : un graphique seul n'est pas accessible. --}}
            <details class="mt-2">
                <summary class="small text-muted">Voir les chiffres du graphique</summary>
                <table class="table table-sm mt-2 mb-0">
                    <thead class="table-light">
                        <tr><th>Mois</th><th class="text-end">Bons</th><th class="text-end">Montant HT</th><th class="text-end">Montant TTC</th></tr>
                    </thead>
                    <tbody>
                        @foreach($evolution as $mois)
                            <tr>
                                <td>{{ $mois['libelle'] }}</td>
                                <td class="text-end">{{ $mois['nombre'] }}</td>
                                <td class="text-end">{{ number_format($mois['montant_ht'], 0, ',', ' ') }}</td>
                                <td class="text-end">{{ number_format($mois['montant_ttc'], 0, ',', ' ') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </details>
        </div>
    </div>

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
            <div class="card border-0 shadow-sm mb-3">
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

            {{-- ── Z4 : les 10 derniers événements (journal réel) ────────── --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Derniers événements</h6>
                </div>
                <div class="card-body pt-2">
                    @forelse($evenements as $evenement)
                        <div class="d-flex gap-2 py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <span class="badge bg-{{ $evenement['couleur'] }} align-self-start">
                                <i class="bi {{ $evenement['icone'] }}"></i>
                            </span>
                            <div class="flex-grow-1 small">
                                @if($evenement['url'])
                                    <a href="{{ $evenement['url'] }}" class="text-decoration-none">{{ $evenement['phrase'] }}</a>
                                @else
                                    {{ $evenement['phrase'] }}
                                @endif
                                <div class="text-muted">
                                    {{ $evenement['auteur'] ?? '—' }} ·
                                    {{ $evenement['quand']?->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small text-center py-3 mb-0">
                            Aucune activité récente sur le module.
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endif

@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script type="module" src="{{ asset('js/modules/achat/dashboard/index.js') }}?v={{ time() }}"></script>
@endpush
