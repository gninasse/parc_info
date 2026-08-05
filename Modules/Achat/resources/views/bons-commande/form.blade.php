@extends('achat::layouts.master')

@section('header', $bon ? 'Bon de commande — '.$bon->numero_affiche : 'Nouveau bon de commande')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $bon ? $bon->numero_affiche : 'Nouveau' }}</li>
@endsection

@push('css')
<style>
    /* Zone d'ajout : pointillés, comme le « + Ajouter des articles » du Stock */
    .btn-ajouter-ligne { border: 2px dashed var(--bs-border-color); width: 100%; }
    .btn-ajouter-ligne:hover { border-color: var(--bs-primary); color: var(--bs-primary); }

    /* Pied de page collant à compteurs (SPEC_UX §0.4) */
    .barre-collante {
        position: sticky; bottom: 0; z-index: 100;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        box-shadow: 0 -4px 12px rgba(0,0,0,.06);
    }

    .pilule-motif.active { box-shadow: 0 0 0 2px var(--bs-primary); }

    /* Bandeau de régularisation : orange hachuré (SPEC_UX §0.2) */
    .bandeau-regularisation {
        background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(253,126,20,.12) 10px, rgba(253,126,20,.12) 20px);
        border-left: 4px solid #fd7e14;
    }

    /* Pilule de TVA cliquable (UX-04) : redevient un champ au clic */
    .pilule-tva { cursor: pointer; }
    .champ-tva { max-width: 5.5rem; }
    .champ-qte { max-width: 6rem; }
    .champ-prix { max-width: 9rem; }
    #lignes-table td { vertical-align: middle; }
</style>
@endpush

@section('content')

@include('achat::shared._stepper', ['etapeCourante' => 1])

{{-- Bandeau permanent d'état du brouillon (SPEC_UX A-03) --}}
<div class="alert alert-secondary py-2 d-flex align-items-center gap-2" role="status">
    <i class="bi bi-pencil-square"></i>
    <span id="bandeau-brouillon">
        {{ $bon ? $bon->numero_affiche : 'Nouveau brouillon' }} — modifiable, non engageant, sans numéro
    </span>
</div>

@php
    // Les bornes sont extraites en variables simples : une directive Blade
    // inline contenant un accès de tableau dans un attribut HTML fait
    // trébucher l'analyseur (« Unclosed '[' »).
    $intermedeDebut = $intermede['debut'] ?? null;
    $intermedeFin = $intermede['fin'] ?? null;
@endphp

@if($estRegularisation)
    {{-- Mode régularisation : l'utilisateur doit voir en permanence qu'il
         documente une acquisition passée, hors workflow de réception (A15). --}}
    <div class="alert bandeau-regularisation py-2 d-flex align-items-start gap-2" role="alert">
        <i class="bi bi-clock-history mt-1"></i>
        <div>
            <strong>RÉGULARISATION — acquisition de la période d'intérim, hors workflow de réception</strong>
            @if($intermedeDebut)
                <div class="small text-muted">
                    La date du bon doit être comprise entre le
                    {{ \Illuminate\Support\Carbon::parse($intermedeDebut)->format('d/m/Y') }}
                    et {{ $intermedeFin ? \Illuminate\Support\Carbon::parse($intermedeFin)->format('d/m/Y') : 'la mise en service' }}.
                </div>
            @endif
        </div>
    </div>
@endif

{{-- Récapitulatif des erreurs de saisie (422 mappées champ par champ) --}}
<div id="erreurs-formulaire" class="alert alert-danger d-none" role="alert">
    <strong>Corrigez les points suivants :</strong>
    <ul class="mb-0 mt-2" id="erreurs-liste"></ul>
</div>

<form id="bon-form" novalidate
      data-bon-id="{{ $bon?->id ?? '' }}"
      data-updated-at="{{ $bon?->updated_at?->toIso8601String() }}"
      data-seuil-ecart="{{ $seuilEcartPct }}"
      data-url-store="{{ route('achat.bons-commande.store') }}"
      data-url-update="{{ $bon ? route('achat.bons-commande.update', $bon->id) : '' }}"
      data-url-reference-prix="{{ route('achat.bons-commande.reference-prix', ['article' => '__ID__']) }}"
      data-url-articles="{{ route('catalogue.api.articles') }}"
      data-url-liste="{{ route('achat.bons-commande.index') }}">

    <input type="hidden" name="est_regularisation" id="a-regularisation" value="{{ $estRegularisation ? 1 : 0 }}">

    {{-- ── Carte « En-tête » ────────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0">
            <h6 class="fw-bold mb-0">En-tête</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="a-fournisseur">Fournisseur <span class="text-danger">*</span></label>
                    <select class="form-select" id="a-fournisseur" name="fournisseur_id" required>
                        <option value="">— Choisir —</option>
                        @foreach($fournisseurs as $fournisseur)
                            <option value="{{ $fournisseur->id }}" @selected($bon?->fournisseur_id === $fournisseur->id)>
                                {{ $fournisseur->raison_sociale }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text">Référentiel du module Catalogue</div>
                    {{-- Verrouillé dès qu'une ligne existe : changer de
                         fournisseur laisserait des lignes orphelines d'un
                         autre catalogue de prix (SPEC_UX A-03). --}}
                    <div class="form-text text-warning d-none" id="aide-fournisseur-verrouille">
                        <i class="bi bi-lock-fill"></i> Videz les lignes pour changer de fournisseur.
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="a-date">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="a-date" name="date_document" required
                           value="{{ $bon?->date_document?->toDateString() ?? now()->toDateString() }}"
                           @if($estRegularisation && $intermedeDebut) min="{{ $intermedeDebut }}" @endif
                           @if($estRegularisation && $intermedeFin) max="{{ $intermedeFin }}" @endif>
                </div>

                <div class="col-md-3">
                    <label class="form-label" for="a-service">Service demandeur</label>
                    <select class="form-select" id="a-service" name="service_demandeur_id">
                        <option value="">— Aucun —</option>
                        @foreach($services as $service)
                            <option value="{{ $service->id }}" @selected($bon?->service_demandeur_id === $service->id)>
                                {{ $service->libelle }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label" for="a-reference-demande">Réf. demande</label>
                    <input type="text" class="form-control" id="a-reference-demande" name="reference_demande"
                           value="{{ $bon?->reference_demande }}" placeholder="24-A">
                </div>

                <div class="col-12">
                    <label class="form-label d-block">Observation</label>
                    {{-- Pilules de choix rapide : la liste vient des paramètres
                         du module (A-08), pas du code. --}}
                    <div class="d-flex flex-wrap gap-2 mb-2" id="pilules-observation">
                        @foreach($motifs as $cle => $libelle)
                            <button type="button"
                                    class="btn btn-sm btn-outline-secondary rounded-pill pilule-motif @if($bon?->observation_type === $cle) active @endif"
                                    data-motif="{{ $cle }}">{{ $libelle }}</button>
                        @endforeach
                    </div>
                    <input type="hidden" name="observation_type" id="a-observation-type" value="{{ $bon?->observation_type }}">
                    <textarea class="form-control @if($bon?->observation_type !== 'autre' && blank($bon?->observation_texte)) d-none @endif"
                              id="a-observation-texte" name="observation_texte" rows="2"
                              placeholder="Texte requis pour le motif « Autre »">{{ $bon?->observation_texte }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Carte « Lignes du bon » ──────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Lignes du bon (<span id="compteur-lignes">0</span>)</h6>
            {{-- Filtre interne : n'apparaît qu'à partir de 10 lignes (UX3-05) --}}
            <input type="search" class="form-control form-control-sm w-auto d-none" id="filtre-lignes"
                   placeholder="Filtrer les lignes…" aria-label="Filtrer les lignes">
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" id="lignes-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width:3rem;">Nat.</th>
                            <th>Article</th>
                            <th class="text-end" style="width:7rem;">Qté <span class="text-danger">*</span></th>
                            <th class="text-end" style="width:12rem;">Prix négocié HT <span class="text-danger">*</span></th>
                            <th class="text-center" style="width:7rem;">TVA</th>
                            <th class="text-end" style="width:10rem;">Sous-total HT</th>
                            <th style="width:3rem;"></th>
                        </tr>
                    </thead>
                    <tbody id="lignes-corps"></tbody>
                </table>
            </div>

            {{-- État vide des lignes --}}
            <div id="lignes-vides" class="text-center text-muted py-4">
                <i class="bi bi-list-ul fs-3"></i>
                <p class="mb-0 mt-2">Aucune ligne pour l'instant. Ajoutez des articles du Catalogue.</p>
            </div>

            <button type="button" class="btn btn-ajouter-ligne mt-3 py-3" id="btn-ajouter-articles"
                    data-bs-toggle="modal" data-bs-target="#modal-articles">
                <i class="bi bi-plus-lg me-1"></i>Ajouter des articles
            </button>
        </div>
    </div>

    {{-- ── Pied de page collant à compteurs (SPEC_UX §0.4) ──────────────── --}}
    <div class="barre-collante py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="small">
            <span id="pied-lignes">0 ligne(s)</span>
            · <span id="pied-unites">0 unité(s)</span>
            · Total : <strong id="pied-ht">0 FCFA HT</strong>
            · <strong id="pied-ttc">0 FCFA TTC</strong>
            <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="btn-decomposition"
                    aria-label="Décomposition des totaux" title="Décomposition des totaux">
                <i class="bi bi-info-circle"></i>
            </button>
            {{-- Les montants affichés ici sont une PRÉVISUALISATION ; les
                 montants qui font foi sont ceux renvoyés par le serveur à
                 l'enregistrement (IA-1). --}}
            <span class="text-muted ms-2" id="mention-previsualisation">estimation — confirmée à l'enregistrement</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('achat.bons-commande.index') }}" class="btn btn-outline-secondary btn-sm">Annuler</a>
            <button type="button" class="btn btn-primary btn-sm" id="btn-enregistrer">
                <i class="bi bi-save me-1"></i>Enregistrer le brouillon
            </button>
            <button type="button" class="btn btn-success btn-sm" id="btn-continuer" disabled
                    title="Ajoutez au moins une ligne">
                Continuer → Récapitulatif
            </button>
        </div>
    </div>
</form>

@include('achat::bons-commande._modal_articles')
@endsection

@push('js')
@php
    // Libellés de nature construits ici : un littéral de tableau passé
    // directement à @json dans une vue fait trébucher l'analyseur Blade.
    $naturesLibelles = [
        'equipement' => 'Équipement',
        'consommable' => 'Consommable',
        'piece' => 'Pièce',
        'licence' => 'Licence',
    ];
@endphp
<script>
    // Lignes déjà enregistrées, rendues par le serveur avec leurs valeurs
    // FIGÉES : le JavaScript ne les redemande jamais au Catalogue (IA-2).
    window.ACHAT_LIGNES_EXISTANTES = @json($lignesExistantes);
    window.ACHAT_NATURES_LIBELLES = @json($naturesLibelles);
</script>
<script type="module" src="{{ asset('js/modules/achat/bons-commande/form.js') }}?v={{ time() }}"></script>
@endpush
