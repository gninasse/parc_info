@extends('stock::layouts.master')

@section('header', 'Transfert — '.$transfert->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.transferts.index') }}">Transferts</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $transfert->numero_affiche }}</li>
@endsection

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 3, 'etapes' => ['Quantités', 'Pointage des unités', 'Validation']])

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0 font-monospace">{{ $transfert->numero }}</h5>
                <span class="badge bg-success">Validé</span>
                <span class="badge bg-primary">
                    {{ $transfert->magasinSource?->libelle }} ⇄ {{ $transfert->magasinCible?->libelle }}
                </span>
            </div>
            <div class="text-muted small mt-1">
                Daté du {{ $transfert->date_document?->format('d/m/Y') }}
                — validé le {{ $transfert->valide_le?->format('d/m/Y H:i') }}
                @if($transfert->valideur) par {{ $transfert->valideur->name }} @endif
                @if($transfert->transporte_par_nom)
                    <span class="mx-2">·</span>
                    <i class="bi bi-truck me-1"></i>Transporté par {{ $transfert->transporte_par_nom }}
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('stock.transferts.store')
            <a href="{{ route('stock.transferts.pdf', $transfert->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-printer me-1"></i>Imprimer le bon
            </a>
            @endcan
            <a href="{{ route('stock.transferts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
</div>

@php
    $quantitatives = $transfert->lignes->filter(fn ($l) => $l->article?->nature !== 'equipement');
@endphp

@if($quantitatives->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Articles</h6></div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Article</th><th class="text-end">Quantité</th></tr>
            </thead>
            <tbody>
                @foreach($quantitatives as $ligne)
                    <tr>
                        <td><span class="font-monospace small text-muted">{{ $ligne->article->code }}</span> {{ $ligne->article->nom }}</td>
                        <td class="text-end">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }} {{ $ligne->article->unite_stock }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if($unites->isNotEmpty())
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Unités transférées ({{ $unites->count() }})</h6></div>
    <div class="card-body">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr><th>Code</th><th>Modèle</th><th>N° de série</th><th></th></tr>
            </thead>
            <tbody>
                @foreach($unites as $unite)
                    <tr>
                        <td class="font-monospace">{{ $unite['code_inventaire'] }}</td>
                        <td>{{ $unite['modele'] }}</td>
                        <td class="font-monospace">{{ $unite['numero_serie'] }}</td>
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

{{-- Cartouche d'audit (S9) --}}
<div class="card border-0 shadow-sm">
    <div class="card-body py-2 small text-muted d-flex flex-wrap justify-content-between">
        <span>
            <strong>{{ $transfert->numero }}</strong>
            — Créé par {{ $transfert->createur?->name ?? '—' }} le {{ $transfert->created_at?->format('d/m/Y H:i') }}
            · Validé par {{ $transfert->valideur?->name ?? '—' }} le {{ $transfert->valide_le?->format('d/m/Y H:i') }}
        </span>
        <em>Document non modifiable — corrections par contre-mouvement</em>
    </div>
</div>

@endsection
