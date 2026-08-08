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

{{-- Récapitulatif des erreurs de saisie (UX §0.6) --}}
@include('stock::shared._erreurs_formulaire')

@php
    // Le mode commande n'existe que si le module Achat est installé : sans
    // lui, le bouton, la modale et l'encart n'apparaissent pas (dégradation
    // propre — RACCORDEMENT §6).
    $achatDisponible = \Illuminate\Support\Facades\Schema::hasTable('achat_bons_commande');
@endphp

{{-- Encart bleu du mode « Livraison sur commande » (RACCORDEMENT §2.3) --}}
<div class="alert alert-info d-none align-items-center gap-2 mb-3" id="encart-commande" role="note">
    <i class="bi bi-link-45deg"></i>
    <div class="flex-grow-1">
        Livraison sur <strong id="encart-commande-numero" class="font-monospace"></strong>
        — <span id="encart-commande-fournisseur"></span>
    </div>
    <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary d-none" id="encart-commande-voir">Voir le BC</a>
    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-delier-commande">Délier</button>
</div>

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
                @php
                    // Pré-sélection : bon existant, « ➜ Réceptionner » du tableau de bord, ou périmètre unique
                    $magasinSelectionne = $entree?->magasin_id
                        ?? ($magasinPrerempli ?? ($magasins->count() === 1 ? $magasins->first()->id : null));
                @endphp
                <select class="form-select" id="e-magasin" name="magasin_id">
                    <option value=""></option>
                    @foreach($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected($magasinSelectionne === $magasin->id)>{{ $magasin->libelle }}</option>
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
                <div class="form-text" id="aide-fournisseur">Référentiel du module Catalogue</div>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="e-reference">Référence externe</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="e-reference" name="reference_externe"
                           placeholder="N° de BL ou de commande" value="{{ $entree?->reference_externe }}">
                    @if($achatDisponible)
                        {{-- RACCORDEMENT §2.1 : le bouton du mode commande, adjacent
                             à la référence. Caché sous nature Retour (JS). --}}
                        <button type="button" class="btn btn-outline-primary" id="btn-lier-commande"
                                title="Lier ce bon d'entrée à un bon de commande du module Achat">
                            <i class="bi bi-link-45deg me-1"></i>Lier à une commande…
                        </button>
                    @endif
                </div>
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

{{-- Lignes du bon — regroupées en une seule table (dérogation UX §3.2
     arbitrée : plus d'onglets ; l'article se choisit dans une modale) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0">Lignes du bon (<span id="compteur-lignes">0</span>)</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table align-middle" id="table-lignes">
                <thead class="table-light">
                    <tr>
                        <th style="width:70px;" class="text-center">Nature</th>
                        <th style="min-width:280px;">Article / Unité</th>
                        <th style="width:120px;">Quantité <span class="text-danger">*</span></th>
                        <th style="width:200px;">Coût unitaire FCFA</th>
                        <th style="width:140px;" class="text-end">Sous-total</th>
                        <th style="width:50px;"></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-ajouter-ligne py-2 flex-grow-1" id="btn-ajouter-article">
                <i class="fas fa-plus me-1"></i> Ajouter un article
            </button>
            <button type="button" class="btn btn-ajouter-ligne py-2 flex-grow-1" id="btn-choisir-unites">
                <i class="bi bi-upc-scan me-1"></i> Rattacher des unités existantes…
            </button>
        </div>
        <p class="text-muted small mt-2 mb-0">
            Les lignes « modèle × N » (nature E) auront leurs numéros de série saisis à l'étape suivante ;
            le rattachement raccroche une unité déjà connue de ParcInfo.
        </p>
    </div>
</div>

{{-- Pièces jointes (BL scanné, photo du colis…) — brouillon uniquement --}}
@if($entree)
    @include('stock::shared._pieces_jointes', [
        'typeDocument' => 'entrees',
        'documentId' => $entree->id,
        'modifiable' => $entree->canEdit() || $entree->verrouille(),
    ])
@endif

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
@include('stock::shared._selecteur_article')
@if($achatDisponible)
    @include('stock::shared._selecteur_commande')
@endif
@endsection

@php
    $lignesInitiales = $entree?->lignes->map(fn ($l) => [
        'article_id' => $l->article_id,
        'equipement_id' => $l->equipement_id,
        'quantite' => (float) $l->quantite,
        'cout_unitaire' => $l->cout_unitaire !== null ? (float) $l->cout_unitaire : null,
        'article' => $l->article?->only(['id', 'code', 'nom', 'nature', 'prix_indicatif', 'unite_stock']),
        'equipement' => $l->equipement?->only(['id', 'code_inventaire', 'numero_serie', 'modele']),
    ])->values() ?? collect();
    $entreeJs = $entree?->only(['id', 'statut']);

    // « ➜ Réceptionner » : la ligne de l'article en alerte est posée d'emblée
    if (! $entree && ($articlePrerempli ?? null)) {
        $lignesInitiales = collect([[
            'article_id' => $articlePrerempli['id'],
            'equipement_id' => null,
            'quantite' => 1,
            'cout_unitaire' => $articlePrerempli['prix_indicatif'] !== null ? (float) $articlePrerempli['prix_indicatif'] : null,
            'article' => $articlePrerempli,
            'equipement' => null,
        ]]);
    }
@endphp

@push('js')
<script>
    window.ENTREE = @json($entreeJs);
    window.LIGNES_INITIALES = @json($lignesInitiales);
    window.MODE_COMMANDE = @json($modeCommande ?? null);
    window.ACHAT_DISPONIBLE = @json($achatDisponible);
</script>
<script type="module" src="{{ asset('js/modules/stock/entrees/form.js') }}?v={{ time() }}"></script>
@endpush
