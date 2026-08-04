@extends('stock::layouts.master')

@section('header', $transfert ? 'Transfert — '.$transfert->numero_affiche : 'Nouveau transfert')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.transferts.index') }}">Transferts</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $transfert ? $transfert->numero_affiche : 'Nouveau' }}</li>
@endsection

@push('css')
<style>
    .btn-ajouter-ligne { border: 2px dashed var(--bs-border-color); width: 100%; }
    .btn-ajouter-ligne:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
    .barre-collante { position: sticky; bottom: 0; z-index: 100; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
    .fleche-transfert { font-size: 1.8rem; color: var(--bs-primary); }
</style>
@endpush

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 1, 'etapes' => ['Quantités', 'Pointage des unités', 'Validation']])

<form id="transfert-form" novalidate data-transfert-id="{{ $transfert?->id ?? '' }}">

{{-- En-tête (UX §5.2) : Source → ⇄ → Cible --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">En-tête</h6></div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="t-source">Magasin source <span class="text-danger">*</span></label>
                <select class="form-select" id="t-source" name="magasin_source_id">
                    <option value=""></option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected(($transfert?->magasin_source_id ?? $sourcePreremplie) === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 text-center">
                <span class="fleche-transfert" aria-hidden="true">⇄</span>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="t-cible">Magasin cible <span class="text-danger">*</span></label>
                <select class="form-select" id="t-cible" name="magasin_cible_id">
                    <option value=""></option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected($transfert?->magasin_cible_id === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
                {{-- Contrôle immédiat « identique à la source » --}}
                <div class="invalid-feedback d-block d-none" id="erreur-cible">Le magasin cible est identique à la source.</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="t-date">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="t-date" name="date_document"
                       value="{{ $transfert?->date_document?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="t-transporte-nom">
                    Transporté par (nom)
                    <i class="bi bi-info-circle text-muted" data-bs-toggle="popover" data-bs-trigger="hover focus"
                       data-bs-content="Imprimé sur le bon si renseigné."></i>
                </label>
                <input type="text" class="form-control" id="t-transporte-nom" name="transporte_par_nom"
                       value="{{ $transfert?->transporte_par_nom }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="t-transporte-employe">Transporté par (employé Grh, optionnel)</label>
                <select class="form-select" id="t-transporte-employe" name="transporte_par_employe_id"
                        data-selection="{{ $transfert?->transporte_par_employe_id }}"
                        data-selection-libelle="{{ $transfert?->transporteParEmploye ? trim($transfert->transporteParEmploye->prenom.' '.$transfert->transporteParEmploye->nom) : '' }}"></select>
            </div>
        </div>
    </div>
</div>

{{-- Lignes du bon — regroupées en une seule table (articles et équipements) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <h6 class="fw-bold mb-0">Lignes du bon (<span id="compteur-lignes">0</span>)</h6>
    </div>
    <div class="card-body">
        @if($transfert)
            {{-- Scan express borné aux unités de la SOURCE (D17) --}}
            <div class="mb-3" id="bloc-scan-express">
                @include('stock::shared._scan_field', [
                    'id' => 'scan-express',
                    'placeholder' => 'Scannez un n° de série du magasin source…',
                ])
            </div>
        @else
            <p class="text-muted small">Enregistrez d'abord le brouillon pour activer le scan express.</p>
        @endif

        <div class="table-responsive">
            <table class="table align-middle" id="table-lignes">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;" class="text-center">Nature</th>
                        <th style="min-width:280px;">Article</th>
                        <th style="width:120px;">Quantité <span class="text-danger">*</span></th>
                        <th style="width:220px;">Disponible source</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <button type="button" class="btn btn-ajouter-ligne py-2" id="btn-ajouter-article">
            <i class="fas fa-plus me-1"></i> Ajouter un article
        </button>
        <p class="text-muted small mt-2 mb-0">
            Les lignes « modèle × N » (nature E) auront leurs unités précises — celles du magasin source —
            pointées à l'étape suivante ; le scan express les pré-pointe directement.
        </p>
    </div>
</div>

{{-- Barre collante — note fixe d'atomicité (texte UX §5.2) --}}
<div class="barre-collante py-2 px-3 d-flex flex-wrap align-items-center gap-2">
    <div class="flex-grow-1 small">
        <span id="recap-barre">—</span>
        <span class="text-muted d-block">
            <i class="bi bi-shield-check me-1"></i>La sortie du magasin source et l'entrée dans le magasin cible seront enregistrées ensemble, ou pas du tout.
        </span>
    </div>
    <button type="submit" class="btn btn-outline-primary" id="btn-enregistrer">
        <i class="fas fa-save me-1"></i>Enregistrer le brouillon
    </button>
    @if($transfert)
        @can('stock.transferts.destroy')
        <button type="button" class="btn btn-outline-danger" id="btn-supprimer">
            <i class="fas fa-trash me-1"></i>Supprimer
        </button>
        @endcan
    @endif
    <button type="button" class="btn btn-primary d-none" id="btn-pointage">
        <i class="bi bi-upc-scan me-1"></i>Pointer les numéros de série
    </button>
    <button type="button" class="btn btn-primary d-none" id="btn-valider">
        <i class="bi bi-check-lg me-1"></i>Valider
    </button>
</div>

</form>

@include('stock::shared._selecteur_article')
@endsection

@push('js')
@php
    $lignesInitiales = $transfert?->lignes->map(fn ($l) => [
        'article_id' => $l->article_id,
        'quantite' => (float) $l->quantite,
        'article' => $l->article?->only(['id', 'code', 'nom', 'nature', 'unite_stock']),
        'pre_pointes' => $l->tampons()->count(),
    ])->values() ?? collect();
@endphp
<script>
    window.TRANSFERT = @json($transfert?->only(['id', 'statut']));
    window.LIGNES_INITIALES = @json($lignesInitiales);
</script>
<script type="module" src="{{ asset('js/modules/stock/transferts/form.js') }}?v={{ time() }}"></script>
@endpush
