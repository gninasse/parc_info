@extends('stock::layouts.master')

@section('header', $sortie ? 'Bon de sortie — '.$sortie->numero_affiche : 'Nouveau bon de sortie')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.sorties.index') }}">Sorties</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $sortie ? $sortie->numero_affiche : 'Nouveau' }}</li>
@endsection

@push('css')
<style>
    .btn-ajouter-ligne { border: 2px dashed var(--bs-border-color); width: 100%; }
    .btn-ajouter-ligne:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
    .barre-collante { position: sticky; bottom: 0; z-index: 100; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
    .pilule-motif.active { box-shadow: 0 0 0 2px var(--bs-primary); }
    .carte-beneficiaire { cursor: pointer; border: 2px solid var(--bs-border-color); border-radius: .5rem; transition: border-color .15s; }
    .carte-beneficiaire:hover { border-color: var(--bs-primary); }
    .carte-beneficiaire.selectionnee { border-color: var(--bs-primary); background: rgba(13,110,253,.04); }
</style>
@endpush

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 1, 'etapes' => ['Quantités', 'Pointage des unités', 'Validation']])

<form id="sortie-form" novalidate data-sortie-id="{{ $sortie?->id ?? '' }}">

{{-- En-tête (UX §4.2) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">En-tête</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="s-magasin">Magasin <span class="text-danger">*</span></label>
                <select class="form-select" id="s-magasin" name="magasin_id">
                    <option value=""></option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected($sortie?->magasin_id === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="s-date">Date <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="s-date" name="date_document"
                       value="{{ $sortie?->date_document?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label d-block">Motif <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-2 mb-2" id="pilules-motif">
                    @foreach($motifs as $cle => $libelle)
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill pilule-motif @if(($sortie?->motif_type) === $cle) active @endif" data-motif="{{ $cle }}">
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="motif_type" id="s-motif-type" value="{{ $sortie?->motif_type }}">
                {{-- « Urgence hors ouverture » déplie la date/heure réelle de remise --}}
                <div class="mt-2 @if(($sortie?->motif_type) !== 'urgence_hors_ouverture') d-none @endif" id="bloc-urgence">
                    <label class="form-label small" for="s-remise-reelle">
                        Date/heure réelle de remise <span class="text-danger">*</span>
                        <i class="bi bi-info-circle text-muted" data-bs-toggle="popover" data-bs-trigger="hover focus"
                           data-bs-content="Remise faite hors ouverture : indiquez quand elle a réellement eu lieu."></i>
                    </label>
                    <input type="datetime-local" class="form-control form-control-sm" id="s-remise-reelle" name="remise_reelle_le"
                           value="{{ $sortie?->remise_reelle_le?->format('Y-m-d\TH:i') }}">
                </div>
                <textarea class="form-control mt-2 @if(($sortie?->motif_type) !== 'autre') d-none @endif"
                          id="s-motif-texte" name="motif_texte" rows="2"
                          placeholder="Texte requis pour le motif « Autre »">{{ $sortie?->motif_texte }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Bénéficiaire (6 cartes — D5) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">Bénéficiaire <span class="text-danger">*</span></h6></div>
    <div class="card-body">
        <div class="row g-2" id="cartes-beneficiaire">
            @foreach(['direction' => 'bi-diagram-3', 'service' => 'bi-people', 'unite' => 'bi-person-workspace', 'poste' => 'bi-pc-display', 'local' => 'bi-door-closed', 'employe' => 'bi-person'] as $type => $icone)
                <div class="col-6 col-md-2">
                    <div class="carte-beneficiaire p-2 text-center h-100 @if(($sortie?->beneficiaire_type) === $type) selectionnee @endif" data-type="{{ $type }}">
                        <i class="bi {{ $icone }} d-block fs-4"></i>
                        <span class="small">{{ \Modules\Stock\Models\Sortie::typesBeneficiaire()[$type] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
        <input type="hidden" name="beneficiaire_type" id="s-beneficiaire-type" value="{{ $sortie?->beneficiaire_type }}">
        @foreach(['direction', 'service', 'unite', 'poste', 'local', 'employe'] as $type)
            <input type="hidden" name="beneficiaire_{{ $type }}_id" id="s-beneficiaire-{{ $type }}"
                   value="{{ $sortie?->{'beneficiaire_'.$type.'_id'} }}">
        @endforeach
        <div class="mt-2 small" id="beneficiaire-choisi">
            @if($sortie?->beneficiaire_libelle)
                <span class="badge bg-primary-subtle text-primary-emphasis">{{ $sortie->beneficiaire_libelle }}</span>
            @endif
        </div>

        <hr>
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="s-remis-a-nom">Remis à (nom)</label>
                <input type="text" class="form-control" id="s-remis-a-nom" name="remis_a_nom"
                       value="{{ $sortie?->remis_a_nom }}" placeholder="Requis si des équipements sortent">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="s-remis-a-employe">Remis à (employé Grh, optionnel)</label>
                <select class="form-select" id="s-remis-a-employe" name="remis_a_employe_id"
                        data-selection="{{ $sortie?->remis_a_employe_id }}"
                        data-selection-libelle="{{ $sortie?->remisAEmploye ? trim($sortie->remisAEmploye->prenom.' '.$sortie->remisAEmploye->nom) : '' }}"></select>
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
        @if($sortie)
            {{-- Scan express (D17) : un scan = ligne « modèle × 1 » créée et pré-pointée --}}
            <div class="mb-3" id="bloc-scan-express">
                @include('stock::shared._scan_field', [
                    'id' => 'scan-express',
                    'placeholder' => 'Scannez un n° de série pour ajouter l\'unité directement…',
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
                        <th style="width:220px;">Disponible</th>
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
            Les lignes « modèle × N » (nature E) auront leurs unités précises pointées à l'étape suivante ;
            le scan express les pré-pointe directement.
        </p>
    </div>
</div>

{{-- Barre collante (matrice UX §10) --}}
<div class="barre-collante py-2 px-3 d-flex flex-wrap align-items-center gap-2">
    <div class="flex-grow-1 small" id="recap-barre">—</div>
    <button type="submit" class="btn btn-outline-primary" id="btn-enregistrer">
        <i class="fas fa-save me-1"></i>Enregistrer le brouillon
    </button>
    @if($sortie)
        @can('stock.sorties.destroy')
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

@include('stock::shared._selection_modals._tous')
@include('stock::shared._selecteur_article')
@endsection

@push('js')
@php
    $lignesInitiales = $sortie?->lignes->map(fn ($l) => [
        'article_id' => $l->article_id,
        'quantite' => (float) $l->quantite,
        'emplacement_local_id' => $l->emplacement_local_id,
        'article' => $l->article?->only(['id', 'code', 'nom', 'nature', 'unite_stock']),
        'pre_pointes' => $l->tampons()->count(),
    ])->values() ?? collect();
@endphp
<script>
    window.SORTIE = @json($sortie?->only(['id', 'statut']));
    window.LIGNES_INITIALES = @json($lignesInitiales);
</script>
<script type="module" src="{{ asset('js/modules/stock/sorties/form.js') }}?v={{ time() }}"></script>
@endpush
