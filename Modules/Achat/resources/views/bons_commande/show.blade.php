@extends('achat::layouts.master')

@section('title', "Bon de Commande {$bonCommande->numero_commande} - Achat")
@section('header', "Bon de Commande : {$bonCommande->numero_commande}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de Commande</a></li>
    <li class="breadcrumb-item active">{{ $bonCommande->numero_commande }}</li>
@endsection

@section('content')
@php
    // Formatter local
    function priceFormat($val) {
        return number_format($val, 0, ',', ' ') . ' FCFA';
    }
@endphp

<div class="row g-3">
    <div class="col-lg-12">
        
        {{-- ── EN-TÊTE DÉTAIL DU BC ── --}}
        <div class="card border-1 rounded-1 mb-3 shadow-sm bg-white">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <h4 class="mb-0 fw-bold text-dark">{{ $bonCommande->numero_commande }}</h4>
                            @if($bonCommande->statut === 'brouillon')
                                <span class="badge bg-secondary px-2 py-1"><i class="fas fa-edit me-1"></i>Brouillon</span>
                            @elseif($bonCommande->statut === 'valide')
                                <span class="badge bg-primary px-2 py-1"><i class="fas fa-check-circle me-1"></i>Validé</span>
                            @elseif($bonCommande->statut === 'partiel')
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-truck-loading me-1"></i>Livré Partiel</span>
                            @elseif($bonCommande->statut === 'livre')
                                <span class="badge bg-success px-2 py-1"><i class="fas fa-truck me-1"></i>Livré Complet</span>
                            @elseif($bonCommande->statut === 'annule')
                                <span class="badge bg-danger px-2 py-1"><i class="fas fa-times-circle me-1"></i>Annulé</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            <span class="me-3"><i class="fas fa-truck me-1"></i>Fournisseur : <strong class="text-dark">{{ $bonCommande->fournisseur->nom }}</strong></span>
                            <span class="me-3"><i class="fas fa-calendar-alt me-1"></i>Date : <strong class="text-dark">{{ $bonCommande->date_commande->format('d/m/Y') }}</strong></span>
                            <span><i class="fas fa-coins me-1"></i>Montant total : <strong class="text-primary">{{ priceFormat($bonCommande->montant_total) }}</strong></span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-sm btn-outline-secondary rounded-1 px-3">
                            <i class="fas fa-arrow-left me-1"></i>Retour
                        </a>
                        
                        @if($bonCommande->statut === 'brouillon')
                            @can('achat.bons_commande.edit')
                            <a href="{{ route('achat.bons-commande.edit', $bonCommande->id) }}" class="btn btn-sm btn-outline-primary rounded-1 px-3">
                                <i class="fas fa-edit me-1"></i>Modifier
                            </a>
                            <button type="button" id="btn-valider-bc" class="btn btn-sm btn-success text-white rounded-1 px-3" data-id="{{ $bonCommande->id }}">
                                <i class="fas fa-check me-1"></i>Valider la commande
                            </button>
                            <button type="button" id="btn-annuler-bc" class="btn btn-sm btn-warning text-white rounded-1 px-3" data-id="{{ $bonCommande->id }}">
                                <i class="fas fa-ban me-1"></i>Annuler
                            </button>
                            @endcan
                        @endif

                        @if($bonCommande->statut === 'valide')
                            @can('achat.bordereaux.create')
                            <a href="{{ route('achat.bordereaux.create', ['bon_de_commande_id' => $bonCommande->id]) }}" class="btn btn-sm btn-success text-white rounded-1 px-3">
                                <i class="fas fa-plus me-1"></i>Créer Bordereau de Livraison
                            </a>
                            @endcan
                        @endif

                        @if($bonCommande->statut === 'brouillon' || $bonCommande->statut === 'valide' || $bonCommande->statut === 'partiel' || $bonCommande->statut === 'livre')
                            <a href="{{ route('achat.bons-commande.imprimer', $bonCommande->id) }}" target="_blank" class="btn btn-sm btn-info text-white rounded-1 px-3">
                                <i class="fas fa-print me-1"></i>Imprimer / PDF
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── TAB NAV DE NAVIGATION ── --}}
        <ul class="nav nav-tabs rounded-1 border-bottom-0 bg-white px-3 pt-2 shadow-sm" id="bcTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-bold small text-muted px-3 py-2 border-0" id="fiche-tab" data-bs-toggle="tab" data-bs-target="#fiche" type="button" role="tab" aria-controls="fiche" aria-selected="true">
                    <i class="fas fa-file-invoice me-1"></i>Fiche BC
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="lignes-tab" data-bs-toggle="tab" data-bs-target="#lignes" type="button" role="tab" aria-controls="lignes" aria-selected="false">
                    <i class="fas fa-list me-1"></i>Lignes de commande
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="bordereaux-tab" data-bs-toggle="tab" data-bs-target="#bordereaux" type="button" role="tab" aria-controls="bordereaux" aria-selected="false">
                    <i class="fas fa-truck me-1"></i>Bordereaux associés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="equipements-tab" data-bs-toggle="tab" data-bs-target="#equipements" type="button" role="tab" aria-controls="equipements" aria-selected="false">
                    <i class="fas fa-laptop me-1"></i>Équipements intégrés
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                    <i class="fas fa-paperclip me-1"></i>Documents joints <span class="badge bg-light text-dark border ms-1" id="doc-count-badge">{{ $bonCommande->documents->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="historique-tab" data-bs-toggle="tab" data-bs-target="#historique" type="button" role="tab" aria-controls="historique" aria-selected="false">
                    <i class="fas fa-history me-1"></i>Historique
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-bold small text-muted px-3 py-2 border-0" id="journal-tab" data-bs-toggle="tab" data-bs-target="#journal" type="button" role="tab" aria-controls="journal" aria-selected="false">
                    <i class="fas fa-terminal me-1"></i>Journal système
                </button>
            </li>
        </ul>

        {{-- ── CONTENU DES ONGLETS ── --}}
        <div class="tab-content shadow-sm rounded-bottom bg-white p-4 border-top" id="bcTabsContent" style="margin-top: -1px;">
            
            {{-- TAB 1: FICHE BC --}}
            <div class="tab-pane fade show active" id="fiche" role="tabpanel" aria-labelledby="fiche-tab">
                <div class="row g-3">
                    {{-- Informations Générales --}}
                    <div class="col-md-4">
                        <div class="card border-1 rounded-1 h-100">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-info-circle me-1"></i>Informations Générales</h6>
                            </div>
                            <div class="card-body p-3">
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 40%;">N° Commande :</td>
                                        <td class="fw-bold text-dark font-monospace">{{ $bonCommande->numero_commande }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Fournisseur :</td>
                                        <td class="fw-semibold text-dark">{{ $bonCommande->fournisseur->nom }} ({{ $bonCommande->fournisseur->code }})</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Date Commande :</td>
                                        <td>{{ $bonCommande->date_commande->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Statut :</td>
                                        <td>
                                            <span class="badge bg-light text-dark border">{{ ucfirst($bonCommande->statut) }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Validé par :</td>
                                        <td>{{ $bonCommande->validateur ? $bonCommande->validateur->name : '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Date de validation :</td>
                                        <td>{{ $bonCommande->date_validation ? $bonCommande->date_validation->format('d/m/Y H:i') : '-' }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td colspan="2" class="pt-2">
                                            <div class="small fw-semibold text-muted mb-1">Observations :</div>
                                            <div class="bg-light p-2 rounded small text-dark" style="min-height: 50px;">
                                                {{ $bonCommande->commentaire ?: 'Aucune observation enregistrée.' }}
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Tableau des Lignes --}}
                    <div class="col-md-8">
                        <div class="card border-1 rounded-1 h-100">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-list me-1"></i>Lignes du Bon de Commande</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.85rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Article</th>
                                                <th>Catégorie</th>
                                                <th class="text-center">Qté Comm.</th>
                                                <th class="text-end">Prix Unit. HT</th>
                                                <th class="text-end">Montant HT</th>
                                                <th class="text-center">Qté Livrée</th>
                                                <th class="text-center">Reste</th>
                                                <th class="text-center">Statut Ligne</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php $totalHt = 0; @endphp
                                            @foreach($bonCommande->lignesCommande as $l)
                                                @php
                                                    $lineTotal = $l->quantite * $l->prix_unitaire;
                                                    $totalHt += $lineTotal;
                                                    $reste = $l->quantite - $l->quantite_livree;
                                                    
                                                    // Statut de ligne
                                                    if ($l->quantite_livree == 0) {
                                                        $badgeLigne = '<span class="badge bg-secondary">En attente</span>';
                                                    } elseif ($reste > 0) {
                                                        $badgeLigne = '<span class="badge bg-warning text-dark">Partiel</span>';
                                                    } else {
                                                        $badgeLigne = '<span class="badge bg-success">Livré</span>';
                                                    }
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold text-dark">{{ $l->article->designation }}</div>
                                                        <div class="small text-muted font-monospace">{{ $l->article->code_article }}</div>
                                                    </td>
                                                    <td><span class="badge bg-light text-dark border">{{ config("achat.types_articles.{$l->article->type_article}", $l->article->type_article) }}</span></td>
                                                    <td class="text-center">{{ $l->quantite }}</td>
                                                    <td class="text-end font-monospace">{{ number_format($l->prix_unitaire, 0, ',', ' ') }}</td>
                                                    <td class="text-end font-monospace fw-semibold">{{ number_format($lineTotal, 0, ',', ' ') }}</td>
                                                    <td class="text-center font-bold text-primary">{{ $l->quantite_livree }}</td>
                                                    <td class="text-center font-bold text-danger">{{ $reste }}</td>
                                                    <td class="text-center">{!! $badgeLigne !!}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-light border-0 p-3">
                                <div class="row g-2 justify-content-end text-end" style="font-size: 0.9rem;">
                                    <div class="col-md-4 offset-md-8">
                                        <div class="d-flex justify-content-between py-1 border-bottom">
                                            <span class="text-muted">Sous-total HT :</span>
                                            <span class="fw-semibold text-dark">{{ priceFormat($totalHt) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-1 border-bottom">
                                            <span class="text-muted">TVA (18%) :</span>
                                            <span class="fw-semibold text-dark">{{ priceFormat($totalHt * 0.18) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between py-2">
                                            <span class="fw-bold text-dark fs-6">Montant TTC :</span>
                                            <span class="fw-bold text-primary fs-5">{{ priceFormat($totalHt * 1.18) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 2: LIGNES DE COMMANDE DÉDIÉES --}}
            <div class="tab-pane fade" id="lignes" role="tabpanel" aria-labelledby="lignes-tab">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-list-ul me-1"></i>Spécifications détaillées des lignes d'articles</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-striped mb-0 small align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Désignation de l'Article</th>
                                    <th>Type</th>
                                    <th>Mesure / Unité</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Prix Indicatif</th>
                                    <th class="text-end">Prix Négocié</th>
                                    <th class="text-center">Taux TVA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($bonCommande->lignesCommande as $l)
                                    <tr>
                                        <td><span class="badge bg-secondary font-monospace">{{ $l->article->code_article }}</span></td>
                                        <td class="fw-bold text-dark">{{ $l->article->designation }}</td>
                                        <td>{{ config("achat.types_articles.{$l->article->type_article}", $l->article->type_article) }}</td>
                                        <td>{{ $l->article->unite_mesure ?? 'Unité' }}</td>
                                        <td class="text-center font-semibold">{{ $l->quantite }}</td>
                                        <td class="text-end text-muted font-monospace">{{ priceFormat($l->article->prix_indicatif) }}</td>
                                        <td class="text-end text-dark font-monospace fw-bold">{{ priceFormat($l->prix_unitaire) }}</td>
                                        <td class="text-center font-monospace">{{ $l->article->taux_tva ?? 18 }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 3: BORDEREAUX ASSOCIÉS --}}
            <div class="tab-pane fade" id="bordereaux" role="tabpanel" aria-labelledby="bordereaux-tab">
                <div class="row g-3">
                    @forelse($bonCommande->bordereauxLivraison as $bl)
                        @php
                            $totalRecu = $bl->lignesLivraison->sum('quantite_livree');
                            $badgeBl = match($bl->statut) {
                                'brouillon' => '<span class="badge bg-secondary">Brouillon</span>',
                                'wizard' => '<span class="badge bg-warning text-dark">En intégration</span>',
                                'valide' => '<span class="badge bg-success">Validé & Intégré</span>',
                                default => $bl->statut
                            };
                        @endphp
                        <div class="col-md-4">
                            <div class="card border-1 rounded-1 shadow-sm">
                                <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark font-monospace small"><i class="fas fa-file-invoice me-1 text-success"></i>{{ $bl->numero_livraison }}</span>
                                    {!! $badgeBl !!}
                                </div>
                                <div class="card-body p-3 small">
                                    <div class="mb-2"><strong>Réf. Physique :</strong> {{ $bl->ref_bordereau_physique ?: '-' }}</div>
                                    <div class="mb-2"><strong>Date de livraison :</strong> {{ $bl->date_livraison->format('d/m/Y') }}</div>
                                    <div class="mb-3"><strong>Unités livrées :</strong> <span class="badge bg-primary fs-7">{{ $totalRecu }} unité(s)</span></div>
                                    <a href="{{ route('achat.bordereaux.show', $bl->id) }}" class="btn btn-xs btn-outline-success w-100 rounded-1">
                                        Voir les détails <i class="fas fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border text-center p-5 rounded-1">
                                <i class="fas fa-truck-loading fa-3x text-muted mb-3 d-block"></i>
                                <span class="text-muted fw-semibold">Aucun bordereau de livraison associé à ce bon de commande.</span>
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- TAB 4: HISTORIQUE --}}
            <div class="tab-pane fade" id="historique" role="tabpanel" aria-labelledby="historique-tab">
                <div class="timeline p-3" style="max-height: 400px; overflow-y: auto;">
                    {{-- Timeline dynamique --}}
                    
                    {{-- 1. Validation --}}
                    @if($bonCommande->statut !== 'brouillon' && $bonCommande->date_validation)
                        <div class="time-label mb-2">
                            <span class="bg-success text-white small px-2 py-1 rounded">{{ $bonCommande->date_validation->format('d/m/Y') }}</span>
                        </div>
                        <div class="mb-3 ms-4 position-relative">
                            <i class="fas fa-check-circle bg-success text-white rounded-circle position-absolute" style="left: -33px; top: 2px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"></i>
                            <div class="timeline-item bg-light p-2 px-3 rounded">
                                <span class="time float-end text-muted small"><i class="fas fa-clock me-1"></i>{{ $bonCommande->date_validation->format('H:i') }}</span>
                                <h6 class="timeline-header fw-bold small text-dark mb-1">Validation du Bon de Commande</h6>
                                <div class="timeline-body text-muted small">
                                    Le bon de commande a été validé et verrouillé par <strong>{{ $bonCommande->validateur ? $bonCommande->validateur->name : 'Système' }}</strong>.
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 2. Livraisons --}}
                    @foreach($bonCommande->bordereauxLivraison as $bl)
                        @if($bl->statut === 'valide')
                            <div class="time-label mb-2">
                                <span class="bg-primary text-white small px-2 py-1 rounded">{{ $bl->date_livraison->format('d/m/Y') }}</span>
                            </div>
                            <div class="mb-3 ms-4 position-relative">
                                <i class="fas fa-truck bg-primary text-white rounded-circle position-absolute" style="left: -33px; top: 2px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"></i>
                                <div class="timeline-item bg-light p-2 px-3 rounded">
                                    <span class="time float-end text-muted small"><i class="fas fa-clock me-1"></i>{{ $bl->created_at->format('H:i') }}</span>
                                    <h6 class="timeline-header fw-bold small text-dark mb-1">Réception & Livraison : {{ $bl->numero_livraison }}</h6>
                                    <div class="timeline-body text-muted small">
                                        Réception des articles enregistrée sous le bordereau physique <strong>{{ $bl->ref_bordereau_physique }}</strong> par <strong>{{ $bl->createur ? $bl->createur->name : 'Système' }}</strong>.
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- 3. Création --}}
                    <div class="time-label mb-2">
                        <span class="bg-secondary text-white small px-2 py-1 rounded">{{ $bonCommande->created_at->format('d/m/Y') }}</span>
                    </div>
                    <div class="mb-3 ms-4 position-relative">
                        <i class="fas fa-plus-circle bg-secondary text-white rounded-circle position-absolute" style="left: -33px; top: 2px; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;"></i>
                        <div class="timeline-item bg-light p-2 px-3 rounded">
                            <span class="time float-end text-muted small"><i class="fas fa-clock me-1"></i>{{ $bonCommande->created_at->format('H:i') }}</span>
                            <h6 class="timeline-header fw-bold small text-dark mb-1">Création du Bon de Commande</h6>
                            <div class="timeline-body text-muted small">
                                Bon de commande créé à l'état initial brouillon par <strong>{{ $bonCommande->creator ? $bonCommande->creator->name : 'Système' }}</strong>.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 5: JOURNAL SYSTEME --}}
            <div class="tab-pane fade" id="journal" role="tabpanel" aria-labelledby="journal-tab">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-terminal me-1"></i>Logs système pour audits</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-striped mb-0 small text-muted">
                            <thead class="table-light">
                                <tr>
                                    <th>Date & Heure</th>
                                    <th>Utilisateur</th>
                                    <th>Action</th>
                                    <th>Détails techniques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $bonCommande->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="fw-bold text-dark">{{ $bonCommande->creator ? $bonCommande->creator->name : 'Système' }}</td>
                                    <td><span class="badge bg-secondary">CREATION</span></td>
                                    <td>Initialisation de l'enregistrement avec statut brouillon et montant {{ priceFormat($bonCommande->montant_total) }}</td>
                                </tr>
                                @if($bonCommande->created_at != $bonCommande->updated_at)
                                <tr>
                                    <td>{{ $bonCommande->updated_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="fw-bold text-dark">{{ $bonCommande->updater ? $bonCommande->updater->name : 'Système' }}</td>
                                    <td><span class="badge bg-info text-white">UPDATE</span></td>
                                    <td>Mise à jour des informations de l'entête ou des lignes</td>
                                </tr>
                                @endif
                                @if($bonCommande->statut !== 'brouillon' && $bonCommande->date_validation)
                                <tr>
                                    <td>{{ $bonCommande->date_validation->format('d/m/Y H:i:s') }}</td>
                                    <td class="fw-bold text-dark">{{ $bonCommande->validateur ? $bonCommande->validateur->name : 'Système' }}</td>
                                    <td><span class="badge bg-success">VALIDATION</span></td>
                                    <td>Validation et transition définitive de l'état du BC</td>
                                </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TAB 6: EQUIPEMENTS INTEGRES --}}
            <div class="tab-pane fade" id="equipements" role="tabpanel" aria-labelledby="equipements-tab">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-laptop me-1"></i>Équipements générés à partir des livraisons de ce BC</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code Inventaire</th>
                                        <th>Catégorie</th>
                                        <th>Marque & Modèle</th>
                                        <th>N° Série</th>
                                        <th>Statut</th>
                                        <th>État</th>
                                        <th class="text-end" style="width: 15%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($equipements as $eq)
                                        <tr>
                                            <td class="fw-bold text-dark font-monospace">{{ $eq->code_inventaire }}</td>
                                            <td>
                                                <span class="text-muted">
                                                    <i class="bi {{ $eq->categorie->icone }} me-1"></i>{{ $eq->categorie->libelle }}
                                                </span>
                                            </td>
                                            <td class="fw-semibold">{{ $eq->marque?->libelle }} {{ $eq->modele }}</td>
                                            <td class="text-muted">{{ $eq->numero_serie ?: '-' }}</td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ str_replace('_', ' ', $eq->statut) }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ ucfirst($eq->etat) }}</span>
                                            </td>
                                            <td class="text-end">
                                                <a href="{{ $eq->detail_route }}" target="_blank" class="btn btn-xs btn-outline-primary" title="Voir la fiche">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('parc-info.equipements.imprimer-etiquette', $eq->id) }}" target="_blank" class="btn btn-xs btn-outline-secondary" title="Imprimer l'étiquette">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-info-circle me-1"></i> Aucun équipement n'a été généré pour ce bon de commande.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TAB 7: DOCUMENTS JOINTS --}}
            <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                <div class="row g-3">
                    {{-- Upload Form --}}
                    <div class="col-md-4">
                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-upload me-1"></i>Ajouter un Document</h6>
                            </div>
                            <div class="card-body p-3">
                                <form id="form-upload-document" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="documentable_type" value="bon_commande">
                                    <input type="hidden" name="documentable_id" value="{{ $bonCommande->id }}">
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Nom du document (Optionnel)</label>
                                        <input type="text" name="nom" class="form-control form-control-sm" placeholder="Ex: Contrat signé, Devis...">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Fichier <span class="text-danger">*</span></label>
                                        <input type="file" name="document" class="form-control form-control-sm" required>
                                        <div class="form-text small" style="font-size: 0.75rem;">PDF, Images, Word, Excel. Max 10 Mo.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Notes / Description</label>
                                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Informations complémentaires..."></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-sm btn-primary w-100 rounded-1">
                                        <i class="fas fa-plus-circle me-1"></i> Téléverser le document
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Documents List --}}
                    <div class="col-md-8">
                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-light border-0 py-2">
                                <h6 class="mb-0 fw-bold small text-dark"><i class="fas fa-folder-open me-1"></i>Liste des documents joints</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 small" id="table-bc-documents">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nom du Fichier</th>
                                                <th>Notes</th>
                                                <th>Taille</th>
                                                <th>Ajouté par</th>
                                                <th class="text-end" style="width: 15%;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($bonCommande->documents as $doc)
                                                <tr id="doc-row-{{ $doc->id }}">
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            @if(str_contains($doc->type_mime, 'pdf'))
                                                                <i class="far fa-file-pdf text-danger fs-5"></i>
                                                            @elseif(str_contains($doc->type_mime, 'image'))
                                                                <i class="far fa-file-image text-success fs-5"></i>
                                                            @else
                                                                <i class="far fa-file text-primary fs-5"></i>
                                                            @endif
                                                            <div>
                                                                <div class="fw-bold text-dark">{{ $doc->nom }}</div>
                                                                <div class="text-muted" style="font-size: 0.75rem;">Ajouté le {{ $doc->created_at->format('d/m/Y H:i') }}</div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted">{{ $doc->notes ?: '-' }}</td>
                                                    <td class="text-muted">{{ number_format($doc->taille / 1024, 1) }} Ko</td>
                                                    <td>{{ $doc->createur ? $doc->createur->name : 'Système' }}</td>
                                                    <td class="text-end">
                                                        <a href="{{ route('achat.documents.telecharger', $doc->id) }}" class="btn btn-xs btn-outline-primary" title="Télécharger">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-xs btn-outline-danger btn-delete-doc" data-id="{{ $doc->id }}" title="Supprimer">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr class="empty-docs-row">
                                                    <td colspan="5" class="text-center py-4 text-muted">
                                                        <i class="fas fa-info-circle me-1"></i> Aucun document joint pour ce bon de commande.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── PIED DE PAGE DE LA FICHE DÉTAIL ── --}}
        <div class="card border-1 rounded-1 mt-3 bg-light shadow-sm">
            <div class="card-body py-2 px-3" style="font-size: 0.8rem;">
                <div class="row g-2 text-muted">
                    <div class="col-md-4">
                        <i class="fas fa-user-edit me-1"></i><strong>Créé par :</strong> {{ $bonCommande->createur ? $bonCommande->createur->name : 'Système' }}
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-clock me-1"></i><strong>Le :</strong> {{ $bonCommande->created_at->format('d/m/Y à H:i') }}
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-sync me-1"></i><strong>Dernière modif :</strong> {{ $bonCommande->updated_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@push('js')
<script>
    // Validation du BC
    $('#btn-valider-bc').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Valider cette commande ?',
            text: 'Cette action verrouille la commande et permet de créer des bordereaux de livraison.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Oui, valider'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.bons-commande.valider', id),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Validé !', res.message, 'success').then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la validation', 'error');
                    }
                });
            }
        });
    });

    // Annulation du BC
    $('#btn-annuler-bc').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Annuler cette commande ?',
            text: 'Voulez-vous vraiment annuler ce bon de commande ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Oui, annuler'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.bons-commande.annuler', id),
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Annulé !', res.message, 'success').then(() => {
                                window.location.reload();
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'annulation', 'error');
                    }
                });
            }
        });
    });

    // Soumission du formulaire d'upload de document
    $('#form-upload-document').on('submit', function(e) {
        e.preventDefault();
        
        let formData = new FormData(this);
        
        $.ajax({
            url: "{{ route('achat.documents.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    Swal.fire('Succès !', res.message, 'success');
                    
                    // Increment count badge
                    let badge = $('#doc-count-badge');
                    let count = parseInt(badge.text()) || 0;
                    badge.text(count + 1);
                    
                    // Append document row
                    let doc = res.document;
                    let fileIcon = '';
                    if (doc.type_mime.includes('pdf')) {
                        fileIcon = '<i class="far fa-file-pdf text-danger fs-5"></i>';
                    } else if (doc.type_mime.includes('image')) {
                        fileIcon = '<i class="far fa-file-image text-success fs-5"></i>';
                    } else {
                        fileIcon = '<i class="far fa-file text-primary fs-5"></i>';
                    }
                    
                    let newRow = `
                        <tr id="doc-row-${doc.id}">
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    ${fileIcon}
                                    <div>
                                        <div class="fw-bold text-dark">${doc.nom}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">Ajouté le ${doc.date}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted">${$('#form-upload-document textarea[name="notes"]').val() || '-'}</td>
                            <td class="text-muted">${(doc.taille / 1024).toFixed(1)} Ko</td>
                            <td>{{ auth()->user()->name }}</td>
                            <td class="text-end">
                                <a href="${route('achat.documents.telecharger', doc.id)}" class="btn btn-xs btn-outline-primary" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                                <button type="button" class="btn btn-xs btn-outline-danger btn-delete-doc" data-id="${doc.id}" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                    
                    // Remove empty row if exists
                    $('.empty-docs-row').remove();
                    $('#table-bc-documents tbody').append(newRow);
                    
                    // Clear form
                    $('#form-upload-document')[0].reset();
                }
            },
            error: function(xhr) {
                Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de l\'upload du document', 'error');
            }
        });
    });

    // Suppression d'un document
    $(document).on('click', '.btn-delete-doc', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Supprimer ce document ?',
            text: 'Voulez-vous vraiment supprimer ce document joint ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Oui, supprimer'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: route('achat.documents.destroy', id),
                    method: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Supprimé !', res.message, 'success');
                            
                            // Decrement count badge
                            let badge = $('#doc-count-badge');
                            let count = parseInt(badge.text()) || 0;
                            if (count > 0) badge.text(count - 1);
                            
                            // Remove row
                            $(`#doc-row-${id}`).remove();
                            
                            // If table is empty, show empty message
                            if ($('#table-bc-documents tbody tr').length === 0) {
                                $('#table-bc-documents tbody').append(`
                                    <tr class="empty-docs-row">
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle me-1"></i> Aucun document joint pour ce bon de commande.
                                        </td>
                                    </tr>
                                `);
                            }
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Erreur', xhr.responseJSON?.message || 'Erreur lors de la suppression', 'error');
                    }
                });
            }
        });
    });
</script>
@endpush
