@extends('achat::layouts.master')

@section('title', 'Tableau de bord - Achats')
@section('header', 'Tableau de bord Achats & Approvisionnement')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item active">Tableau de bord</li>
@endsection

@section('content')

{{-- ── INDICATEURS ─────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <x-achat-carte-indicateur
            libelle="Catalogue Articles"
            :valeur="$indicateurs['total_articles']"
            :detail="$indicateurs['alertes_stock'] . ' en alerte de stock'"
            icone="fa-cubes"
            couleur="primary"
            :lien="Route::has('achat.articles.index') ? route('achat.articles.index') : null" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-achat-carte-indicateur
            libelle="Bons de Commande"
            :valeur="$indicateurs['total_bc']"
            :detail="$indicateurs['bc_valide'] . ' validé(s), ' . $indicateurs['bc_brouillon'] . ' brouillon(s)'"
            icone="fa-file-invoice-dollar"
            couleur="success"
            :lien="route('achat.bons-commande.index')" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-achat-carte-indicateur
            libelle="Bordereaux de Livraison"
            :valeur="$totalBl"
            :detail="$blValide . ' intégré(s), ' . $blEnCours . ' en cours'"
            icone="fa-shipping-fast"
            couleur="info"
            :lien="route('achat.bordereaux.index')" />
    </div>
    <div class="col-sm-6 col-xl-3">
        <x-achat-carte-indicateur
            libelle="Alertes Stock"
            :valeur="$indicateurs['alertes_stock']"
            detail="Consommables sous le seuil"
            icone="fa-exclamation-triangle"
            couleur="danger"
            :lien="route('achat.stocks.index')" />
    </div>
</div>

{{-- ── ACCÈS RAPIDES ───────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    @can('achat.bons_commande.create')
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4">
            <div class="card-body">
                <div class="fs-1 text-primary mb-3"><i class="fas fa-plus-circle"></i></div>
                <h6 class="fw-bold">Nouveau bon de commande</h6>
                <p class="small text-muted mb-3">Enregistrer une commande fournisseur et ses articles.</p>
                <a href="{{ route('achat.bons-commande.create') }}" class="btn btn-sm btn-primary rounded-1">Créer un BC</a>
            </div>
        </div>
    </div>
    @endcan

    @can('achat.bordereaux.create')
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4">
            <div class="card-body">
                <div class="fs-1 text-success mb-3"><i class="fas fa-truck-loading"></i></div>
                <h6 class="fw-bold">Enregistrer une livraison</h6>
                <p class="small text-muted mb-3">Saisir une réception rattachée à un bon validé.</p>
                <a href="{{ route('achat.bordereaux.create') }}" class="btn btn-sm btn-success text-white rounded-1">Créer un BL</a>
            </div>
        </div>
    </div>
    @endcan

    @can('achat.articles.view')
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4">
            <div class="card-body">
                <div class="fs-1 text-info mb-3"><i class="fas fa-clipboard-list"></i></div>
                <h6 class="fw-bold">Catalogue des articles</h6>
                <p class="small text-muted mb-3">Référencer équipements, licences et consommables.</p>
                <a href="{{ route('achat.articles.index') }}" class="btn btn-sm btn-info text-white rounded-1">Voir le catalogue</a>
            </div>
        </div>
    </div>
    @endcan

    @can('achat.bordereaux.valider')
    <div class="col-md-6 col-lg-3">
        <div class="card border-1 rounded-1 h-100 text-center py-4">
            <div class="card-body">
                <div class="fs-1 text-warning mb-3"><i class="fas fa-magic"></i></div>
                <h6 class="fw-bold">Assistant d'intégration</h6>
                <p class="small text-muted mb-3">Inventorier le matériel reçu et l'intégrer au parc.</p>
                <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-sm btn-warning text-white rounded-1">Ouvrir la liste</a>
            </div>
        </div>
    </div>
    @endcan
</div>

{{-- ── RELIQUATS ANCIENS (EF-BC-19) ────────────────────────────────────── --}}
@if($reliquatsAnciens->isNotEmpty())
<div class="alert alert-warning border rounded-1 d-flex align-items-start gap-3 mb-4">
    <i class="fas fa-clock fs-4 mt-1"></i>
    <div class="flex-grow-1">
        <h6 class="alert-heading fw-bold mb-1">Reliquats de livraison en attente</h6>
        <p class="small mb-2">
            {{ $reliquatsAnciens->count() }} bon(s) de commande de plus de
            {{ config('achat.reliquat_alerte_jours', 60) }} jours comportent encore des articles non livrés.
        </p>
        <div class="d-flex flex-wrap gap-2">
            @foreach($reliquatsAnciens as $reliquat)
                <a href="{{ route('achat.bons-commande.show', $reliquat) }}"
                   class="btn btn-xs btn-outline-dark rounded-1">
                    {{ $reliquat->numero_commande }}
                    <span class="badge bg-dark ms-1">{{ $reliquat->reste_a_livrer }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- ── GRAPHIQUES ──────────────────────────────────────────────────────── --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-chart-line me-2 text-primary"></i>
                    Évolution de la dépense engagée &mdash; {{ config('achat.rapports.mois_glissants', 12) }} derniers mois
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="chart-depenses-mensuelles"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold">
                    <i class="fas fa-chart-pie me-2 text-primary"></i>Répartition par fournisseur
                </h6>
            </div>
            <div class="card-body">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="chart-fournisseurs"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── ACTIVITÉ RÉCENTE ────────────────────────────────────────────────── --}}
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>Dernières commandes</h6>
                <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-xs btn-outline-secondary rounded-1">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>N° commande</th>
                                <th>Fournisseur</th>
                                <th class="text-end">Montant TTC</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dernieresCommandes as $commande)
                                <tr>
                                    <td>
                                        <a href="{{ route('achat.bons-commande.show', $commande) }}" class="fw-bold">
                                            {{ $commande->numero_commande }}
                                        </a>
                                    </td>
                                    <td>{{ $commande->fournisseur?->nom ?? '-' }}</td>
                                    <td class="text-end fw-semibold text-nowrap">
                                        {{ number_format($commande->montant_ttc, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="text-center">
                                        <x-achat-badge-statut :statut="$commande->statut" type="bc" />
                                    </td>
                                </tr>
                            @empty
                                <x-achat-etat-vide message="Aucune commande enregistrée." :colspan="4" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-1 rounded-1 h-100">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="fas fa-shipping-fast me-2 text-primary"></i>Dernières livraisons</h6>
                <a href="{{ route('achat.bordereaux.index') }}" class="btn btn-xs btn-outline-secondary rounded-1">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>N° livraison</th>
                                <th>BC associé</th>
                                <th>Réf. physique</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dernieresLivraisons as $livraison)
                                <tr>
                                    <td>
                                        <a href="{{ route('achat.bordereaux.show', $livraison) }}" class="fw-bold">
                                            {{ $livraison->numero_livraison }}
                                        </a>
                                    </td>
                                    <td>{{ $livraison->bonCommande?->numero_commande ?? '-' }}</td>
                                    <td class="text-muted">{{ $livraison->ref_bordereau_physique }}</td>
                                    <td class="text-center">
                                        <x-achat-badge-statut :statut="$livraison->statut" type="bl" />
                                    </td>
                                </tr>
                            @empty
                                <x-achat-etat-vide message="Aucune livraison enregistrée." :colspan="4" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('plugins/chartjs/chart.min.js') }}"></script>
<script>
    window.achatDashboard = {
        depensesMensuelles: @json($depensesMensuelles),
        depensesParFournisseur: @json($depensesParFournisseur),
    };
</script>
<script src="{{ asset('js/modules/achat/dashboard.js') }}?v={{ time() }}"></script>
@endpush
