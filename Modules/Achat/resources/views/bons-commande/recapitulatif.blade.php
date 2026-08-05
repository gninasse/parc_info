@extends('achat::layouts.master')

@section('header', 'Récapitulatif — '.$bon->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item active" aria-current="page">Récapitulatif</li>
@endsection

@push('css')
<style>
    .barre-collante {
        position: sticky; bottom: 0; z-index: 100;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        box-shadow: 0 -4px 12px rgba(0,0,0,.06);
    }
</style>
@endpush

@section('content')

@include('achat::shared._stepper', ['etapeCourante' => 2])

{{-- Le composant partagé, identique à celui du visa (UX2-03). --}}
@include('achat::shared._recapitulatif_bc', ['titre' => 'Récapitulatif du bon'])

@php
    // Extraits en variables simples : un accès de tableau dans une directive
    // inline d'attribut (@disabled, @if) casse l'analyseur Blade.
    $soumettable = $diagnostic['soumettable'] ?? false;
    $diagnosticBouton = $diagnostic['diagnostic_bouton'] ?? null;
@endphp

{{-- Barre collante de l'étape ② (SPEC_UX A-03) --}}
<div class="barre-collante py-2 px-3 d-flex justify-content-between align-items-center gap-2"
     data-bon-id="{{ $bon->id }}"
     data-url-soumettre="{{ route('achat.bons-commande.soumettre', $bon->id) }}"
     data-url-liste="{{ route('achat.bons-commande.index') }}"
     id="barre-recapitulatif">
    <a href="{{ route('achat.bons-commande.edit', $bon->id) }}" class="btn btn-outline-secondary btn-sm">
        ← Retour aux lignes
    </a>

    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted">
            {{ $bon->lignes->count() }} ligne(s) ·
            {{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }} FCFA TTC
        </span>
        {{-- Bouton grisé AVEC son diagnostic quand un blocage subsiste
             (SPEC_UX §0.3) : dire ce qui manque, pas seulement que c'est
             impossible. --}}
        <button type="button" class="btn btn-success btn-sm" id="btn-soumettre"
                @disabled(! $soumettable)
                title="{{ $diagnosticBouton ?? 'Soumettre le bon au visa' }}"
                data-nb-lignes="{{ $bon->lignes->count() }}"
                data-montant-ttc="{{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }}"
                data-fournisseur="{{ $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale }}"
                data-numero="{{ $bon->numero_affiche }}">
            <i class="bi bi-send me-1"></i>Soumettre au visa
        </button>
    </div>
</div>
@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/achat/bons-commande/recapitulatif.js') }}?v={{ time() }}"></script>
@endpush
