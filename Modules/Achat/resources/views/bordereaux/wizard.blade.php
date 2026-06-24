@extends('achat::layouts.master')

@section('title', "Assistant d'intégration BL {$bordereau->numero_livraison} - Achat")
@section('header', "Assistant d'intégration : {$bordereau->numero_livraison}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.show', $bordereau->id) }}">{{ $bordereau->numero_livraison }}</a></li>
    <li class="breadcrumb-item active">Wizard</li>
@endsection

@push('css')
<style>
    .stepper-nav .nav-link {
        border-left: 3px solid var(--bs-border-color);
        border-radius: 0;
        text-align: left;
        color: var(--bs-text-muted);
        padding: 0.75rem 1rem;
        background: none;
    }
    .stepper-nav .nav-link.active {
        border-left-color: var(--bs-primary);
        color: var(--bs-primary);
        font-weight: 600;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .stepper-nav .nav-link.completed {
        border-left-color: var(--bs-success);
        color: var(--bs-success);
    }
    .unit-card {
        border: 1px solid var(--bs-border-color);
        background-color: var(--bs-body-bg);
        border-radius: 0.25rem;
    }
    .unit-card-header {
        background-color: var(--bs-light);
        border-bottom: 1px solid var(--bs-border-color);
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')
<div class="row g-3">
    {{-- Stepper Navigation (Gauche) --}}
    <div class="col-md-3">
        <div class="card border-1 rounded-1">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2 text-primary"></i>Étapes d'intégration</h6>
            </div>
            <div class="card-body p-0">
                <div class="nav flex-column stepper-nav" id="wizard-tab" role="tablist" aria-orientation="vertical">
                    @php $stepIndex = 1; @endphp
                    @foreach($lignesWizard as $ligne)
                        @php
                            $saved = $wizardData->get($ligne->article_id);
                            $isCompleted = $saved && $saved->completed;
                        @endphp
                        <button class="nav-link {{ $stepIndex === 1 ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}" 
                                id="tab-btn-{{ $ligne->article_id }}" 
                                data-bs-toggle="pill" 
                                data-bs-target="#tab-pane-{{ $ligne->article_id }}" 
                                type="button" 
                                role="tab" 
                                aria-selected="{{ $stepIndex === 1 ? 'true' : 'false' }}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small fw-semibold text-uppercase" style="font-size: 0.7rem;">Étape {{ $stepIndex }}</div>
                                    <div class="text-truncate" style="max-width: 180px;">{{ $ligne->article->designation }}</div>
                                </div>
                                <span class="step-status-icon">
                                    @if($isCompleted)
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="far fa-circle"></i>
                                    @endif
                                </span>
                            </div>
                        </button>
                        @php $stepIndex++; @endphp
                    @endforeach

                    {{-- Étape de validation finale --}}
                    <button class="nav-link" 
                            id="tab-btn-confirm" 
                            data-bs-toggle="pill" 
                            data-bs-target="#tab-pane-confirm" 
                            type="button" 
                            role="tab" 
                            aria-selected="false">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 0.7rem;">Étape {{ $stepIndex }}</div>
                                <div>Validation Finale</div>
                            </div>
                            <span class="step-status-icon"><i class="fas fa-flag-checkered"></i></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Stepper Content (Droite) --}}
    <div class="col-md-9">
        <div class="tab-content" id="wizard-tabContent">
            @php $stepIndex = 1; @endphp
            @foreach($lignesWizard as $ligne)
                @php
                    $article = $ligne->article;
                    $qty = $ligne->quantite_livree;
                    $saved = $wizardData->get($article->id);
                    $unitesSaved = $saved ? $saved->unites_data : [];
                @endphp
                <div class="tab-pane fade {{ $stepIndex === 1 ? 'show active' : '' }}" 
                     id="tab-pane-{{ $article->id }}" 
                     role="tabpanel" 
                     aria-labelledby="tab-btn-{{ $article->id }}">
                     
                    <form class="form-wizard-step" data-article-id="{{ $article->id }}" data-url="{{ route('achat.bordereaux.wizard.sauvegarder', [$bordereau->id, $article->id]) }}">
                        @csrf
                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">{{ $article->designation }}</h6>
                                    <span class="small text-muted">Saisie d'inventaire pour {{ $qty }} unité(s) livrée(s) (Type: {{ config("achat.types_articles.{$article->type_article}", $article->type_article) }})</span>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                @for($i = 0; $i < $qty; $i++)
                                    @php
                                        $unit = $unitesSaved[$i] ?? [];
                                        $valeurSerie = $unit['numero_serie'] ?? '';
                                        $valeurInventaire = $unit['code_inventaire'] ?? '';
                                        $valeurLicence = $unit['cle_licence'] ?? '';
                                        $valeurActivation = $unit['date_activation'] ?? date('Y-m-d');
                                        $valeurExpiration = $unit['date_expiration'] ?? '';
                                    @endphp
                                    <div class="unit-card mb-3">
                                        <div class="unit-card-header p-2 px-3">
                                            <i class="fas fa-cube me-1 text-primary"></i> Unité #{{ $i + 1 }}
                                        </div>
                                        <div class="p-3">
                                            @if($article->type_article === 'equipement')
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Numéro de Série <span class="text-danger">*</span></label>
                                                        <input type="text" 
                                                               name="unites[{{ $i }}][numero_serie]" 
                                                               class="form-control form-control-sm text-uppercase" 
                                                               value="{{ $valeurSerie }}" 
                                                               placeholder="Saisir le S/N physique" 
                                                               required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Code Inventaire <span class="text-muted">(auto-généré si vide)</span></label>
                                                        <input type="text" 
                                                               name="unites[{{ $i }}][code_inventaire]" 
                                                               class="form-control form-control-sm text-uppercase" 
                                                               value="{{ $valeurInventaire }}" 
                                                               placeholder="Saisir ou laisser vide pour génération">
                                                    </div>

                                                    {{-- Champs spécifiques de la catégorie d'équipement --}}
                                                    @if($article->categorie && $article->categorie->champs->count() > 0)
                                                        <div class="col-12 mt-3">
                                                            <div class="small fw-bold text-muted border-bottom pb-1 mb-2">Caractéristiques Techniques</div>
                                                            <div class="row g-2">
                                                                @foreach($article->categorie->champs as $champ)
                                                                    @php
                                                                        $valeurChamp = $unit['champs_valeurs'][$champ->code] ?? '';
                                                                    @endphp
                                                                    <div class="col-md-4">
                                                                        <label class="form-label small fw-semibold">{{ $champ->libelle }}</label>
                                                                        @if($champ->type_champ === 'select')
                                                                            <select name="unites[{{ $i }}][champs_valeurs][{{ $champ->code }}]" class="form-select form-select-sm">
                                                                                <option value="">Sélectionner...</option>
                                                                                @foreach($champ->options_resolved as $optId => $optVal)
                                                                                    <option value="{{ $optId }}" {{ $valeurChamp == $optId ? 'selected' : '' }}>{{ $optVal }}</option>
                                                                                @endforeach
                                                                            </select>
                                                                        @elseif($champ->type_champ === 'number')
                                                                            <input type="number" name="unites[{{ $i }}][champs_valeurs][{{ $champ->code }}]" class="form-control form-control-sm" value="{{ $valeurChamp }}">
                                                                        @else
                                                                            <input type="text" name="unites[{{ $i }}][champs_valeurs][{{ $champ->code }}]" class="form-control form-control-sm" value="{{ $valeurChamp }}">
                                                                        @endif
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif($article->type_article === 'licence')
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label small fw-bold">Clé de Licence <span class="text-danger">*</span></label>
                                                        <input type="text" 
                                                               name="unites[{{ $i }}][cle_licence]" 
                                                               class="form-control form-control-sm text-uppercase" 
                                                               value="{{ $valeurLicence }}" 
                                                               placeholder="XXXXX-XXXXX-XXXXX-XXXXX-XXXXX" 
                                                               required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Date d'activation <span class="text-danger">*</span></label>
                                                        <input type="date" 
                                                               name="unites[{{ $i }}][date_activation]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $valeurActivation }}" 
                                                               required>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-bold">Date d'expiration</label>
                                                        <input type="date" 
                                                               name="unites[{{ $i }}][date_expiration]" 
                                                               class="form-control form-control-sm" 
                                                               value="{{ $valeurExpiration }}">
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </div>
                            
                            <div class="card-footer bg-light border-0 py-3 d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-wizard-prev" {{ $stepIndex === 1 ? 'disabled' : '' }}>
                                    <i class="fas fa-chevron-left me-1"></i>Précédent
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary btn-save-step">
                                    <i class="fas fa-save me-1"></i>Enregistrer cette étape & Continuer <i class="fas fa-chevron-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @php $stepIndex++; @endphp
            @endforeach

            {{-- Étape de validation finale --}}
            <div class="tab-pane fade" id="tab-pane-confirm" role="tabpanel" aria-labelledby="tab-btn-confirm">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-flag-checkered text-primary me-2"></i>Validation et Intégration Finale</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info rounded-1 mb-4">
                            <h6 class="alert-heading fw-bold"><i class="fas fa-info-circle me-2"></i>Résumé des saisies</h6>
                            <p class="small mb-0">Veuillez vérifier que toutes les étapes d'intégration ont été complétées. Une fois la validation finale soumise, les équipements physiques et les licences seront automatiquement créés et configurés dans votre parc informatique (statut "En stock", état "Bon"). Les consommables verront également leur stock incrémenté.</p>
                        </div>

                        <div class="list-group mb-4 rounded-1" id="wizard-summary-list">
                            @foreach($lignesWizard as $ligne)
                                @php
                                    $saved = $wizardData->get($ligne->article_id);
                                    $isCompleted = $saved && $saved->completed;
                                @endphp
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3 step-summary-item" data-article-id="{{ $ligne->article_id }}">
                                    <div>
                                        <h6 class="mb-0 fw-bold">{{ $ligne->article->designation }}</h6>
                                        <span class="small text-muted">{{ $ligne->quantite_livree }} unité(s) livrée(s)</span>
                                    </div>
                                    <span class="badge {{ $isCompleted ? 'bg-success' : 'bg-danger' }} p-2">
                                        {{ $isCompleted ? 'Complété' : 'Non complété' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-center py-3">
                            <button type="button" 
                                    id="btn-finalize-wizard" 
                                    class="btn btn-success text-white px-5 rounded-1 py-2 fw-bold" 
                                    data-url="{{ route('achat.bordereaux.wizard.valider', $bordereau->id) }}">
                                <i class="fas fa-check-double me-2"></i> Finaliser et valider l'intégration
                            </button>
                            <div class="text-danger small mt-2 d-none" id="finalize-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i> Veuillez compléter toutes les étapes avant de pouvoir finaliser.
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-wizard-prev">
                            <i class="fas fa-chevron-left me-1"></i>Précédent
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="{{ asset('js/modules/achat/bordereaux/wizard.js') }}?v={{ time() }}"></script>
@endpush
