@extends('achat::layouts.master')

@section('header', 'Réception de licences — '.$bon->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.index') }}">Bons de commande</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.bons-commande.show', $bon->id) }}">{{ $bon->numero_affiche }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Réception de licences</li>
@endsection

@push('css')
<style>
    .barre-collante {
        position: sticky; bottom: 0; z-index: 100;
        background: var(--bs-body-bg);
        border-top: 1px solid var(--bs-border-color);
        box-shadow: 0 -4px 12px rgba(0,0,0,.06);
    }
    #table-cles td { vertical-align: middle; }
    .cle-invalide { border-color: var(--bs-danger) !important; }
</style>
@endpush

@section('content')

@php
    $saisies = $tampon->count();
    $quantite = (int) $reception->quantite;
@endphp

{{-- Bandeau du wizard (SPEC_UX A-05) --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <h5 class="mb-0">
                <i class="bi bi-key me-1"></i>Réception de licences —
                <span class="font-monospace">{{ $bon->numero_affiche }}</span>
            </h5>
            <div class="text-muted small mt-1">
                {{ $ligne->designation }}
                <span class="mx-2">·</span>
                Prix figé : {{ number_format((float) $ligne->prix_unitaire_ht, 0, ',', ' ') }} FCFA HT
                <span class="mx-2">·</span>
                Fournisseur : {{ $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale ?? '—' }}
            </div>
        </div>
        <div class="text-end">
            <div class="small text-uppercase text-muted">Progression</div>
            <div class="fs-4 fw-bold"><span id="compteur-saisies">{{ $saisies }}</span>/{{ $quantite }}</div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3"
     id="wizard-licences"
     data-url-saisir="{{ route('achat.licences.saisir', [$bon->id, $ligne->id, $reception->id]) }}"
     data-url-importer="{{ route('achat.licences.importer', [$bon->id, $ligne->id, $reception->id]) }}"
     data-url-finaliser="{{ route('achat.licences.finaliser', [$bon->id, $ligne->id, $reception->id]) }}"
     data-url-abandonner="{{ route('achat.licences.abandonner', [$bon->id, $ligne->id, $reception->id]) }}"
     data-url-supprimer="{{ route('achat.licences.supprimer-cle', [$bon->id, $ligne->id, $reception->id, 0]) }}"
     data-quantite="{{ $quantite }}"
     data-designation="{{ $ligne->designation }}"
     data-prix="{{ number_format((float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}">
    <div class="card-body">

        {{-- Champ commun : date d'activation appliquée à toutes --}}
        <div class="row g-2 align-items-end mb-3">
            <div class="col-md-4">
                <label class="form-label" for="date-commune">Date d'activation</label>
                <input type="date" class="form-control" id="date-commune">
            </div>
            <div class="col-md-3">
                <button type="button" class="btn btn-outline-secondary w-100" id="btn-appliquer-toutes">
                    <i class="bi bi-arrow-down-up me-1"></i>Appliquer à toutes
                </button>
            </div>
            <div class="col-md-5 text-md-end">
                <button type="button" class="btn btn-outline-primary" id="btn-importer"
                        data-bs-toggle="modal" data-bs-target="#modal-import">
                    <i class="bi bi-clipboard-plus me-1"></i>Coller / importer CSV…
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle" id="table-cles">
                <thead class="table-light">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Clé de licence <span class="text-danger">*</span></th>
                        <th style="width:180px;">Date d'activation</th>
                        <th style="width:180px;">Date d'expiration</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tampon as $index => $cle)
                        <tr data-tampon-id="{{ $cle->id }}">
                            <td class="text-muted">{{ $index + 1 }}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm input-cle"
                                       value="{{ $cle->cle }}" aria-label="Clé de licence {{ $index + 1 }}">
                                <div class="invalid-feedback d-block small text-danger cle-erreur"></div>
                            </td>
                            <td><input type="date" class="form-control form-control-sm input-activation" value="{{ $cle->date_activation?->format('Y-m-d') }}"></td>
                            <td><input type="date" class="form-control form-control-sm input-expiration" value="{{ $cle->date_expiration?->format('Y-m-d') }}"></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger btn-supprimer-cle" title="Retirer">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Ligne de saisie : autofocus, Entrée = enregistrer + suivante --}}
                    <tr id="ligne-saisie">
                        <td class="text-muted">{{ $saisies + 1 }}</td>
                        <td>
                            <input type="text" class="form-control form-control-sm" id="nouvelle-cle"
                                   placeholder="Saisissez ou scannez la clé, puis Entrée" autofocus
                                   aria-label="Nouvelle clé de licence">
                            <div class="invalid-feedback d-block small text-danger" id="nouvelle-cle-erreur"></div>
                        </td>
                        <td><input type="date" class="form-control form-control-sm" id="nouvelle-activation"></td>
                        <td><input type="date" class="form-control form-control-sm" id="nouvelle-expiration"></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p class="text-muted small mb-0">
            <i class="bi bi-shield-check me-1"></i>
            Chaque clé est enregistrée dès sa saisie : vous pouvez fermer cette page et reprendre plus tard.
        </p>
    </div>
</div>

{{-- Barre collante (SPEC_UX A-05) --}}
<div class="barre-collante py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
    <button type="button" class="btn btn-outline-danger btn-sm" id="btn-abandonner">
        <i class="bi bi-arrow-left me-1"></i>Revenir en arrière
    </button>

    <div class="d-flex align-items-center gap-2">
        <span class="small text-muted" id="diagnostic-finaliser"></span>
        <a href="{{ route('achat.bons-commande.show', $bon->id) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-save me-1"></i>Enregistrer et continuer plus tard
        </a>
        <button type="button" class="btn btn-success btn-sm" id="btn-finaliser" @disabled($saisies !== $quantite)>
            <i class="bi bi-check-lg me-1"></i>Finaliser
        </button>
    </div>
</div>

{{-- Import en masse : zone de collage + rapport --}}
<div class="modal fade" id="modal-import" tabindex="-1" aria-labelledby="modal-import-titre" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-import-titre">Coller / importer des clés</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="import-texte">Une clé par ligne</label>
                <textarea class="form-control font-monospace" id="import-texte" rows="10"
                          placeholder="XXXX-XXXX-XXXX-0001&#10;XXXX-XXXX-XXXX-0002&#10;…"></textarea>
                <div class="form-text">
                    Les doublons et les lignes vides sont ignorés — un rapport vous dira ce qui a été retenu.
                </div>
                <div class="alert alert-info mt-3 d-none" id="import-rapport" role="status"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-lancer-import">
                    <i class="bi bi-clipboard-check me-1"></i>Importer
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/achat/licences/wizard.js') }}?v={{ time() }}"></script>
@endpush
