@extends('stock::layouts.master')

@section('header', 'Bon de sortie — '.$sortie->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.sorties.index') }}">Sorties</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $sortie->numero_affiche }}</li>
@endsection

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 3, 'etapes' => ['Quantités', 'Pointage des unités', 'Validation']])

{{-- En-tête (UX §4.4) : numéro, badges, bénéficiaire, Remis à, motif --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 font-monospace">{{ $sortie->numero }}</h5>
                <span class="badge bg-success">Validé</span>
                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $sortie->beneficiaire_libelle ?? $sortie->beneficiaire_type }}</span>
                @if($sortie->remis_a_nom)
                    <span class="badge bg-light text-dark border">Remis à {{ $sortie->remis_a_nom }}</span>
                @endif
            </div>
            <div class="text-muted small mt-1">
                {{ config('stock.motifs_sortie')[$sortie->motif_type] ?? $sortie->motif_type }}
                @if($sortie->remise_reelle_le) — remise réelle le {{ $sortie->remise_reelle_le->format('d/m/Y H:i') }} @endif
                <span class="mx-2">·</span>
                Validé le {{ $sortie->valide_le?->format('d/m/Y H:i') }}
                @if($sortie->valideur) par {{ $sortie->valideur->name }} @endif
                <span class="mx-2">·</span>
                <i class="bi bi-shop me-1"></i>{{ $sortie->magasin?->libelle }}
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('stock.sorties.store')
            <a href="{{ route('stock.sorties.pdf', $sortie->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer me-1"></i>Imprimer le bon
            </a>
            @endcan
            <a href="{{ route('stock.sorties.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
</div>

@php
    $quantitatives = $sortie->lignes->filter(fn ($l) => $l->article?->nature !== 'equipement');
@endphp

@if($quantitatives->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Articles</h6></div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Article</th><th class="text-end">Quantité</th><th>Emplacement</th></tr>
            </thead>
            <tbody>
                @foreach($quantitatives as $ligne)
                    <tr>
                        <td><span class="font-monospace small text-muted">{{ $ligne->article->code }}</span> {{ $ligne->article->nom }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }} {{ $ligne->article->unite_stock }}</td>
                        <td>{{ $ligne->emplacementLocal?->libelle ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($unites->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Équipements sortis ({{ $unites->count() }})</h6></div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Code</th><th>Modèle</th><th>N° de série</th><th>Affectation créée</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($unites as $unite)
                    <tr>
                        <td class="font-monospace">{{ $unite['code_inventaire'] }}</td>
                        <td>{{ $unite['modele'] }}</td>
                        <td class="font-monospace">{{ $unite['numero_serie'] }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $unite['affectation_code'] ?? '—' }}</span></td>
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

@if($sortie->observation)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold">Observation</h6>
        <p class="mb-0">{{ $sortie->observation }}</p>
    </div>
</div>
@endif

{{-- Cartouche d'audit (S9) --}}
<div class="card border-0 shadow-sm">
    <div class="card-body py-2 small text-muted d-flex flex-wrap justify-content-between">
        <span>
            <strong>{{ $sortie->numero }}</strong>
            — Créé par {{ $sortie->createur?->name ?? '—' }} le {{ $sortie->created_at?->format('d/m/Y H:i') }}
            · Validé par {{ $sortie->valideur?->name ?? '—' }} le {{ $sortie->valide_le?->format('d/m/Y H:i') }}
        </span>
        <em>Document non modifiable — corrections par contre-mouvement</em>
    </div>
</div>

@endsection
