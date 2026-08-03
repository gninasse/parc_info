@extends('stock::layouts.master')

@section('header', $entree ? 'Bon d\'entrée — '.$entree->numero_affiche : 'Nouveau bon d\'entrée')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.entrees.index') }}">Entrées</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $entree ? $entree->numero_affiche : 'Nouveau' }}</li>
@endsection

@push('css')
<style>
    .btn-ajouter-ligne { border: 2px dashed var(--bs-border-color); width: 100%; }
    .btn-ajouter-ligne:hover { border-color: var(--bs-primary); color: var(--bs-primary); }
    .barre-collante { position: sticky; bottom: 0; z-index: 100; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
    .pilule-motif.active { box-shadow: 0 0 0 2px var(--bs-primary); }
    .chip-rattachement { display: inline-flex; align-items: center; gap: .35rem; }
</style>
@endpush

@section('content')

{{-- Fil d'étapes ①②③ (S12) --}}
@include('stock::shared._stepper', ['etapeCourante' => 1])

<form id="entree-form" novalidate
      data-entree-id="{{ $entree?->id ?? '' }}"
      data-seuil-alerte-cout="{{ config('stock.seuil_alerte_cout', 0.20) }}">

{{-- Carte En-tête (UX §3.2) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0"><h6 class="fw-bold mb-0">En-tête</h6></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label" for="e-magasin">Magasin <span class="text-danger">*</span></label>
                <select class="form-select" id="e-magasin" name="magasin_id" @if($magasins->count() === 1) data-preselection="{{ $magasins->first()->id }}" @endif>
                    <option value=""></option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected(($entree?->magasin_id ?? ($magasins->count() === 1 ? $magasins->first()->id : null)) === $magasin->id)>{{ $magasin->libelle }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="e-date">
                    Date de livraison <span class="text-danger">*</span>
                    <i class="bi bi-info-circle text-muted" data-bs-toggle="popover" data-bs-trigger="hover focus"
                       data-bs-content="Date du camion — la date de validation sera enregistrée séparément."></i>
                </label>
                <input type="date" class="form-control" id="e-date" name="date_document"
                       value="{{ $entree?->date_document?->format('Y-m-d') ?? now()->format('Y-m-d') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="e-fournisseur">Fournisseur</label>
                <select class="form-select" id="e-fournisseur" name="fournisseur_id">
                    <option value=""></option>
                    @foreach($fournisseurs as $fournisseur)
                        <option value="{{ $fournisseur->id }}" @selected($entree?->fournisseur_id === $fournisseur->id)>{{ $fournisseur->raison_sociale }}</option>
                    @endforeach
                </select>
                <div class="form-text">Référentiel du module Catalogue</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="e-reference">Référence externe</label>
                <input type="text" class="form-control" id="e-reference" name="reference_externe"
                       placeholder="N° de BL ou de commande" value="{{ $entree?->reference_externe }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="e-nature">Nature</label>
                <select class="form-select" id="e-nature" name="nature">
                    <option value="livraison" @selected(($entree?->nature ?? 'livraison') === 'livraison')>Livraison</option>
                    <option value="retour" @selected($entree?->nature === 'retour')>Retour</option>
                </select>
            </div>
            <div class="col-md-9">
                <label class="form-label d-block">
                    Observation
                    <i class="bi bi-info-circle text-muted" data-bs-toggle="popover" data-bs-trigger="hover focus"
                       data-bs-content="L'observation typée est imprimée sur le bon."></i>
                </label>
                {{-- Pilules de motifs types depuis la config (un clic dans 90 % des cas) --}}
                <div class="d-flex flex-wrap gap-2 mb-2" id="pilules-observation">
                    @foreach(config('stock.motifs_observation_entree') as $cle => $libelle)
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill pilule-motif @if(($entree?->observation_type) === $cle) active @endif" data-motif="{{ $cle }}">
                            {{ $libelle }}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="observation_type" id="e-observation-type" value="{{ $entree?->observation_type }}">
                <textarea class="form-control @if(($entree?->observation_type) !== 'autre' && blank($entree?->observation)) d-none @endif"
                          id="e-observation" name="observation" rows="2"
                          placeholder="Texte requis pour le motif « Autre »">{{ $entree?->observation }}</textarea>
            </div>
        </div>
    </div>
</div>

{{-- Carte à 2 onglets : Articles / Équipements --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0">
        <ul class="nav nav-tabs card-header-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#onglet-articles" type="button">Articles (<span id="compteur-articles">0</span>)</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#onglet-equipements" type="button">Équipements (<span id="compteur-equipements">0</span>)</button></li>
        </ul>
    </div>
    <div class="card-body tab-content">
        <div class="tab-pane fade show active" id="onglet-articles">
            <div class="table-responsive">
                <table class="table align-middle" id="table-lignes-articles">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:280px;">Article</th>
                            <th style="width:120px;">Quantité <span class="text-danger">*</span></th>
                            <th style="width:200px;">Coût unitaire FCFA</th>
                            <th style="width:140px;" class="text-end">Sous-total</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-ajouter-ligne py-2" id="btn-ajouter-article">
                <i class="fas fa-plus me-1"></i> Ajouter une ligne
            </button>
            <div class="alert alert-light border mt-3 mb-0 small">
                <i class="bi bi-info-circle me-1"></i>
                L'article n'existe pas ? <a href="{{ route('catalogue.articles.index') }}" target="_blank" rel="noopener">Créez-le dans le module Catalogue →</a>
                (le brouillon attend sans rien perdre — D7)
            </div>
        </div>
        <div class="tab-pane fade" id="onglet-equipements">
            <h6 class="fw-bold">Équipements commandés (modèle × N)</h6>
            <div class="table-responsive">
                <table class="table align-middle" id="table-lignes-modeles">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:280px;">Article (nature E)</th>
                            <th style="width:120px;">Quantité N <span class="text-danger">*</span></th>
                            <th style="width:200px;">Coût unitaire FCFA</th>
                            <th style="width:50px;"></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-ajouter-ligne py-2 mb-1" id="btn-ajouter-modele">
                <i class="fas fa-plus me-1"></i> Ajouter une ligne modèle × N
            </button>
            <p class="text-muted small">Les numéros de série seront saisis à l'étape suivante.</p>

            <hr>

            <h6 class="fw-bold">Rattachement d'unités existantes</h6>
            <button type="button" class="btn btn-outline-primary btn-sm" id="btn-choisir-unites">
                <i class="bi bi-upc-scan me-1"></i>Choisir des équipements…
            </button>
            <div class="d-flex flex-wrap gap-2 mt-2" id="chips-rattachements"></div>
        </div>
    </div>
</div>

{{-- Barre collante (matrice UX §10) --}}
<div class="barre-collante py-2 px-3 d-flex flex-wrap align-items-center gap-2">
    <div class="flex-grow-1 small" id="recap-barre">—</div>
    <button type="submit" class="btn btn-outline-primary" id="btn-enregistrer">
        <i class="fas fa-save me-1"></i>Enregistrer le brouillon
    </button>
    @if($entree)
        @can('stock.entrees.destroy')
        <button type="button" class="btn btn-outline-danger" id="btn-supprimer">
            <i class="fas fa-trash me-1"></i>Supprimer
        </button>
        @endcan
    @endif
    {{-- « Saisir les numéros de série » si ≥1 ligne modèle × N, sinon « ✓ Valider » --}}
    <button type="button" class="btn btn-primary d-none" id="btn-referencement">
        <i class="bi bi-upc-scan me-1"></i>Saisir les numéros de série
    </button>
    <button type="button" class="btn btn-primary d-none" id="btn-valider">
        <i class="bi bi-check-lg me-1"></i>Valider
    </button>
</div>

</form>

@include('stock::shared._selecteur_unites')
@endsection

@push('js')
<script>
    window.ENTREE = @json($entree?->only(['id', 'statut']) );
    window.LIGNES_INITIALES = @json($entree?->lignes->map(fn ($l) => [
        'article_id' => $l->article_id,
        'equipement_id' => $l->equipement_id,
        'quantite' => (float) $l->quantite,
        'cout_unitaire' => $l->cout_unitaire !== null ? (float) $l->cout_unitaire : null,
        'article' => $l->article?->only(['id', 'code', 'nom', 'nature', 'prix_indicatif', 'unite_stock']),
        'equipement' => $l->equipement?->only(['id', 'code_inventaire', 'numero_serie', 'modele']),
    ])->values() ?? []);
</script>
<script type="module" src="{{ asset('js/modules/stock/entrees/form.js') }}?v={{ time() }}"></script>
@endpush
