@extends('achat::layouts.master')

@section('title', "Intégration du bordereau {$bordereau->numero_livraison} - Achat")
@section('header', "Assistant d'intégration &mdash; {$bordereau->numero_livraison}")

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard.index') }}">Achats</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.index') }}">Bordereaux</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bordereaux.show', $bordereau) }}">{{ $bordereau->numero_livraison }}</a></li>
    <li class="breadcrumb-item active">Intégration</li>
@endsection

@push('css')
<style>
    .stepper .nav-link {
        border-left: 3px solid var(--bs-border-color);
        border-radius: 0;
        text-align: left;
        color: var(--bs-secondary-color);
        padding: 0.75rem 1rem;
        background: none;
        width: 100%;
    }
    .stepper .nav-link.active {
        border-left-color: var(--bs-primary);
        color: var(--bs-primary);
        font-weight: 600;
        background-color: rgba(13, 110, 253, 0.05);
    }
    .stepper .nav-link.etape-complete {
        border-left-color: var(--bs-success);
        color: var(--bs-success);
    }
    .carte-unite {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.25rem;
    }
    .carte-unite-entete {
        background-color: var(--bs-tertiary-bg);
        border-bottom: 1px solid var(--bs-border-color);
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>
@endpush

@section('content')

<div class="alert alert-light border rounded-1 d-flex align-items-start gap-3 py-2 px-3 mb-3">
    <i class="fas fa-info-circle text-primary mt-1"></i>
    <div class="small">
        Renseignez les informations d'inventaire de chaque unité reçue. Les saisies sont enregistrées
        étape par étape : vous pouvez interrompre et reprendre l'assistant sans rien perdre.
        <strong>Rien n'est créé dans le parc avant la validation finale.</strong>
    </div>
</div>

<div class="row g-3">
    {{-- ── NAVIGATION PAR ÉTAPES ───────────────────────────────────────── --}}
    <div class="col-md-3">
        <div class="card border-1 rounded-1">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold"><i class="fas fa-list-ul me-2 text-primary"></i>Étapes</h6>
            </div>
            <div class="card-body p-0">
                <div class="nav flex-column stepper" id="wizard-nav" role="tablist" aria-orientation="vertical">
                    @foreach($lignesWizard as $index => $ligne)
                        @php
                            $saisie = $saisies->get($ligne->article_id);
                            $complete = $saisie?->completed ?? false;
                        @endphp
                        <button class="nav-link {{ $loop->first ? 'active' : '' }} {{ $complete ? 'etape-complete' : '' }}"
                                id="etape-{{ $ligne->article_id }}"
                                data-article-id="{{ $ligne->article_id }}"
                                data-bs-toggle="pill"
                                data-bs-target="#panneau-{{ $ligne->article_id }}"
                                type="button" role="tab">
                            <div class="d-flex justify-content-between align-items-center gap-2">
                                <div class="overflow-hidden">
                                    <div class="small fw-semibold text-uppercase" style="font-size: 0.68rem;">
                                        Étape {{ $loop->iteration }}
                                    </div>
                                    <div class="text-truncate">{{ $ligne->article->designation }}</div>
                                    <div class="small text-muted">{{ $ligne->quantite_livree }} unité(s)</div>
                                </div>
                                <span class="icone-etat flex-shrink-0">
                                    <i class="{{ $complete ? 'fas fa-check-circle text-success' : 'far fa-circle' }}"></i>
                                </span>
                            </div>
                        </button>
                    @endforeach

                    <button class="nav-link" id="etape-finale" data-bs-toggle="pill"
                            data-bs-target="#panneau-final" type="button" role="tab">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="small fw-semibold text-uppercase" style="font-size: 0.68rem;">
                                    Étape {{ $lignesWizard->count() + 1 }}
                                </div>
                                <div>Validation finale</div>
                            </div>
                            <span class="flex-shrink-0"><i class="fas fa-flag-checkered"></i></span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── PANNEAUX DE SAISIE ──────────────────────────────────────────── --}}
    <div class="col-md-9">
        <div class="tab-content">
            @foreach($lignesWizard as $ligne)
                @php
                    $article = $ligne->article;
                    $saisie = $saisies->get($article->id);
                    $unites = $saisie?->unites_data ?? [];
                    $communs = $saisie?->attributs_communs ?? [];
                    $champs = $champsParCategorie[$article->categorie_equipement_id] ?? [];
                @endphp

                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                     id="panneau-{{ $article->id }}" role="tabpanel">

                    <form class="form-etape"
                          data-article-id="{{ $article->id }}"
                          data-quantite="{{ $ligne->quantite_livree }}"
                          data-url="{{ route('achat.bordereaux.wizard.sauvegarder', [$bordereau, $article]) }}"
                          novalidate>
                        @csrf

                        <div class="card border-1 rounded-1">
                            <div class="card-header bg-white border-0 py-3">
                                <h6 class="mb-0 fw-bold text-dark">{{ $article->designation }}</h6>
                                <span class="small text-muted">
                                    {{ $ligne->quantite_livree }} unité(s) à inventorier &mdash;
                                    <x-achat-badge-type :type="$article->type_article" />
                                </span>
                            </div>

                            <div class="card-body p-4">

                                {{-- EF-INT-18 : saisie commune répercutée sur toutes les unités --}}
                                @if($article->type_article === 'equipement' && count($champs) > 0 && $ligne->quantite_livree > 1)
                                    <div class="card border-1 rounded-1 mb-4 bg-light">
                                        <div class="card-header bg-transparent border-0 py-2 d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0 fw-bold small text-dark">
                                                <i class="fas fa-layer-group me-1 text-primary"></i>
                                                Caractéristiques communes aux {{ $ligne->quantite_livree }} unités
                                            </h6>
                                            <button type="button" class="btn btn-xs btn-outline-primary rounded-1 btn-appliquer-communs">
                                                <i class="fas fa-arrow-down me-1"></i>Appliquer à toutes les unités
                                            </button>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="row g-2">
                                                @foreach($champs as $champ)
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-semibold">{{ $champ->libelle }}</label>
                                                        @include('achat::bordereaux._champ_dynamique', [
                                                            'champ' => $champ,
                                                            'name' => "attributs_communs[{$champ->code}]",
                                                            'valeur' => $communs[$champ->code] ?? '',
                                                            'classeSupplementaire' => 'champ-commun',
                                                        ])
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @for($i = 0; $i < $ligne->quantite_livree; $i++)
                                    @php $unite = $unites[$i] ?? []; @endphp
                                    <div class="carte-unite mb-3">
                                        <div class="carte-unite-entete p-2 px-3">
                                            <i class="fas fa-cube me-1 text-primary"></i> Unité n° {{ $i + 1 }}
                                        </div>
                                        <div class="p-3">
                                            @if($article->type_article === 'equipement')
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">
                                                            Numéro de série <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" required
                                                               name="unites[{{ $i }}][numero_serie]"
                                                               class="form-control form-control-sm text-uppercase"
                                                               value="{{ $unite['numero_serie'] ?? '' }}"
                                                               placeholder="Numéro de série physique">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">
                                                            Code inventaire
                                                            <span class="text-muted">(généré si laissé vide)</span>
                                                        </label>
                                                        <input type="text"
                                                               name="unites[{{ $i }}][code_inventaire]"
                                                               class="form-control form-control-sm text-uppercase"
                                                               value="{{ $unite['code_inventaire'] ?? '' }}"
                                                               placeholder="{{ config('achat.code_inventaire_pattern') }}">
                                                    </div>

                                                    @if(count($champs) > 0)
                                                        <div class="col-12 mt-3">
                                                            <div class="small fw-bold text-muted border-bottom pb-1 mb-2">
                                                                Caractéristiques techniques
                                                            </div>
                                                            <div class="row g-2">
                                                                @foreach($champs as $champ)
                                                                    <div class="col-md-4">
                                                                        <label class="form-label small fw-semibold">{{ $champ->libelle }}</label>
                                                                        @include('achat::bordereaux._champ_dynamique', [
                                                                            'champ' => $champ,
                                                                            'name' => "unites[{$i}][champs_valeurs][{$champ->code}]",
                                                                            'valeur' => $unite['champs_valeurs'][$champ->code] ?? '',
                                                                            'classeSupplementaire' => 'champ-unite',
                                                                        ])
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @elseif($article->type_article === 'licence')
                                                <div class="row g-3">
                                                    <div class="col-md-12">
                                                        <label class="form-label small fw-semibold">
                                                            Clé de licence <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" required
                                                               name="unites[{{ $i }}][cle_licence]"
                                                               class="form-control form-control-sm text-uppercase"
                                                               value="{{ $unite['cle_licence'] ?? '' }}"
                                                               placeholder="XXXXX-XXXXX-XXXXX-XXXXX-XXXXX">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">
                                                            Date d'activation <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="date" required
                                                               name="unites[{{ $i }}][date_activation]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $unite['date_activation'] ?? date('Y-m-d') }}">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label small fw-semibold">Date d'expiration</label>
                                                        <input type="date"
                                                               name="unites[{{ $i }}][date_expiration]"
                                                               class="form-control form-control-sm"
                                                               value="{{ $unite['date_expiration'] ?? '' }}">
                                                        @if($article->duree_validite_mois)
                                                            <div class="form-text" style="font-size:.7rem">
                                                                Validité contractuelle : {{ $article->duree_validite_mois }} mois.
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </div>

                            <div class="card-footer bg-light border-0 py-3 d-flex justify-content-between">
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-etape-precedente"
                                        {{ $loop->first ? 'disabled' : '' }}>
                                    <i class="fas fa-chevron-left me-1"></i>Précédent
                                </button>
                                <button type="submit" class="btn btn-sm btn-primary btn-enregistrer-etape">
                                    <i class="fas fa-save me-1"></i>Enregistrer et continuer
                                    <i class="fas fa-chevron-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach

            {{-- ── VALIDATION FINALE ───────────────────────────────────── --}}
            <div class="tab-pane fade" id="panneau-final" role="tabpanel">
                <div class="card border-1 rounded-1">
                    <div class="card-header bg-white border-0 py-3">
                        <h6 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-flag-checkered text-primary me-2"></i>Validation et intégration
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-warning border rounded-1 mb-4">
                            <h6 class="alert-heading fw-bold small">
                                <i class="fas fa-exclamation-triangle me-2"></i>Cette opération est définitive
                            </h6>
                            <p class="small mb-0">
                                Les équipements et licences saisis seront créés dans le parc informatique
                                (statut « en stock », état « bon »), les stocks de consommables seront
                                incrémentés et le bon de commande sera mis à jour.
                                <strong>Un bordereau validé ne peut plus être modifié.</strong>
                            </p>
                        </div>

                        <div class="list-group mb-4 rounded-1" id="recapitulatif">
                            @foreach($lignesWizard as $ligne)
                                @php $complete = $saisies->get($ligne->article_id)?->completed ?? false; @endphp
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3"
                                     data-article-id="{{ $ligne->article_id }}">
                                    <div>
                                        <h6 class="mb-0 fw-bold small">{{ $ligne->article->designation }}</h6>
                                        <span class="small text-muted">
                                            {{ $ligne->quantite_livree }} unité(s) à inventorier
                                        </span>
                                    </div>
                                    <span class="badge {{ $complete ? 'bg-success' : 'bg-danger' }} p-2 badge-etat">
                                        {{ $complete ? 'Complété' : 'À compléter' }}
                                    </span>
                                </div>
                            @endforeach

                            {{-- Les consommables sont intégrés sans saisie unitaire --}}
                            @foreach($bordereau->lignesLivraison as $ligne)
                                @if(! $ligne->article->necessiteWizard())
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                        <div>
                                            <h6 class="mb-0 fw-bold small">{{ $ligne->article->designation }}</h6>
                                            <span class="small text-muted">
                                                {{ $ligne->quantite_livree }} unité(s) &mdash; aucune saisie requise
                                            </span>
                                        </div>
                                        <span class="badge bg-secondary p-2">Automatique</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="text-center py-3">
                            <button type="button" id="btn-finaliser"
                                    class="btn btn-success text-white px-5 rounded-1 py-2 fw-bold" disabled>
                                <i class="fas fa-check-double me-2"></i>Finaliser et intégrer au parc
                            </button>
                            <div class="text-danger small mt-2" id="avertissement-finalisation">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Toutes les étapes doivent être complétées avant la validation.
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-0 py-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-etape-precedente">
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
<script>
    window.achatWizard = {
        nombreEtapes: {{ $lignesWizard->count() }},
        urlValidation: @json(route('achat.bordereaux.wizard.valider', $bordereau)),
    };
</script>
<script src="{{ asset('js/modules/achat/bordereaux/wizard.js') }}?v={{ time() }}"></script>
@endpush
