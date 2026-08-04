@extends('stock::layouts.master')

@section('header', 'Bon d\'entrée — '.$entree->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.entrees.index') }}">Entrées</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $entree->numero_affiche }}</li>
@endsection

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 3])

{{-- En-tête de fiche (UX §3.4) : dates DISTINCTES livraison / validation --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 font-monospace">{{ $entree->numero }}</h5>
                <span class="badge bg-success">Validé</span>
                <span class="badge bg-light text-dark">{{ $entree->nature === 'retour' ? 'Retour' : 'Livraison' }}</span>
            </div>
            <div class="text-muted small mt-1">
                Livré le {{ $entree->date_document?->format('d/m/Y') }}
                — Validé le {{ $entree->valide_le?->format('d/m/Y H:i') }}
                @if($entree->valideur) par {{ $entree->valideur->name }} @endif
                <span class="mx-2">·</span>
                <i class="bi bi-shop me-1"></i>{{ $entree->magasin?->libelle }}
                @if($entree->fournisseur)
                    <span class="mx-2">·</span>
                    <i class="bi bi-truck me-1"></i>{{ $entree->fournisseur->raison_sociale }}
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('stock.entrees.store')
            {{-- Deux modèles d'impression : valorisation / fiche des équipements --}}
            <div class="btn-group">
                <a href="{{ route('stock.entrees.pdf', $entree->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-printer me-1"></i>Imprimer le bon
                </a>
                <button type="button" class="btn btn-outline-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false">
                    <span class="visually-hidden">Choisir le modèle d'impression</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" target="_blank" rel="noopener"
                           href="{{ route('stock.entrees.pdf', ['id' => $entree->id, 'modele' => 'articles']) }}">
                            <i class="bi bi-list-columns me-2"></i>Bon de réception
                            <div class="small text-muted">Articles, quantités, coûts et total</div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item @if($unites->isEmpty()) disabled @endif" target="_blank" rel="noopener"
                           href="{{ route('stock.entrees.pdf', ['id' => $entree->id, 'modele' => 'equipements']) }}">
                            <i class="bi bi-upc-scan me-2"></i>Fiche des équipements
                            <div class="small text-muted">
                                @if($unites->isEmpty())
                                    Aucun équipement sur ce bon
                                @else
                                    Codes d'inventaire, modèles et n° de série
                                @endif
                            </div>
                        </a>
                    </li>
                </ul>
            </div>
            @endcan
            <a href="{{ route('stock.entrees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
</div>

@php
    $quantitatives = $entree->lignes->filter(fn ($l) => $l->article_id !== null && $l->article?->nature !== 'equipement');
    $modeles = $entree->lignes->filter(fn ($l) => $l->article_id !== null && $l->article?->nature === 'equipement');
    $rattachements = $entree->lignes->whereNotNull('equipement_id');
@endphp

{{-- Lignes articles (lecture seule) --}}
@if($quantitatives->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Articles</h6></div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Article</th>
                    <th class="text-end">Quantité</th>
                    <th class="text-end">Coût unitaire</th>
                    <th class="text-end">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quantitatives as $ligne)
                    <tr>
                        <td><span class="font-monospace small text-muted">{{ $ligne->article->code }}</span> {{ $ligne->article->nom }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }} {{ $ligne->article->unite_stock }}</td>
                        <td class="text-end">
                            {{ $ligne->cout_unitaire !== null ? number_format($ligne->cout_unitaire, 0, ',', ' ').' FCFA' : '—' }}
                            @if(\Modules\Stock\Support\EcartCout::estSuspect($ligne->cout_unitaire !== null ? (float) $ligne->cout_unitaire : null, $ligne->article->prix_indicatif !== null ? (float) $ligne->article->prix_indicatif : null))
                                <span class="text-warning" data-bs-toggle="tooltip"
                                      title="Coût éloigné du prix indicatif ({{ number_format($ligne->article->prix_indicatif, 0, ',', ' ') }})">⚠</span>
                            @endif
                        </td>
                        <td class="text-end">{{ $ligne->cout_unitaire !== null ? number_format($ligne->quantite * $ligne->cout_unitaire, 0, ',', ' ').' FCFA' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Équipements : n° de série créés, liens ParcInfo --}}
@if($modeles->isNotEmpty() || $rattachements->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0">Équipements ({{ $unites->count() }} unité(s))</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Code inventaire</th>
                    <th>Modèle</th>
                    <th>N° de série</th>
                    <th>État</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($unites as $unite)
                    <tr>
                        <td class="font-monospace">{{ $unite['code_inventaire'] }}</td>
                        <td>{{ $unite['modele'] }}</td>
                        <td class="font-monospace">{{ $unite['numero_serie'] }}</td>
                        <td>{{ ucfirst($unite['etat'] ?? '—') }}</td>
                        <td class="text-end">
                            @if($unite['url_fiche'])
                                <a href="{{ $unite['url_fiche'] }}" class="small">Fiche ParcInfo →</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Observation imprimée --}}
@if($entree->observation_type || $entree->observation)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold">Observation</h6>
        @if($entree->observation_type)
            <span class="badge bg-light text-dark border">{{ config('stock.motifs_observation_entree')[$entree->observation_type] ?? $entree->observation_type }}</span>
        @endif
        @if($entree->observation)
            <p class="mb-0 mt-2">{{ $entree->observation }}</p>
        @endif
    </div>
</div>
@endif

{{-- Cartouche d'audit (S9 / amendement n°19) --}}
<div class="card border-0 shadow-sm">
    <div class="card-body py-2 small text-muted d-flex flex-wrap justify-content-between">
        <span>
            <strong>{{ $entree->numero }}</strong>
            — Créé par {{ $entree->createur?->name ?? '—' }} le {{ $entree->created_at?->format('d/m/Y H:i') }}
            · Validé par {{ $entree->valideur?->name ?? '—' }} le {{ $entree->valide_le?->format('d/m/Y H:i') }}
        </span>
        <em>Document non modifiable — corrections par contre-mouvement</em>
    </div>
</div>

@endsection
