@extends('achat::layouts.master')

@section('header', 'Administration')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li>
    <li class="breadcrumb-item"><a href="{{ route('achat.dashboard') }}">Achat</a></li>
    <li class="breadcrumb-item active" aria-current="page">Administration</li>
@endsection

@push('css')
<style>
    .carte-parametre { border: 1px solid var(--bs-border-color); }
    .pilule-motif { display: inline-flex; align-items: center; gap: .35rem; }
    .apercu-numero { font-family: var(--bs-font-monospace); }
</style>
@endpush

@section('content')

<div class="alert alert-light border d-flex align-items-center gap-2">
    <i class="bi bi-info-circle text-primary"></i>
    <div>
        Chaque modification prend effet <strong>immédiatement</strong>, sans redéploiement,
        et son ancienne valeur est conservée au journal.
    </div>
</div>

<div class="row g-3" id="parametres" data-url-base="{{ url('achat/parametres') }}">

    {{-- Préfixe de numérotation, avec aperçu vivant --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre" data-cle="prefixe_numerotation">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Préfixe de numérotation</h6>
                <p class="small text-muted">Il précède chaque numéro de bon de commande.</p>

                <div class="input-group input-group-sm">
                    <input type="text" class="form-control champ-parametre" value="{{ $prefixe }}"
                           maxlength="8" aria-label="Préfixe de numérotation">
                    <button class="btn btn-outline-primary btn-enregistrer" type="button">Enregistrer</button>
                </div>

                <div class="form-text">
                    Aperçu : <span class="apercu-numero fw-semibold" id="apercu-numero">{{ $prefixe }}-{{ $anneeCourante }}-0042</span>
                </div>
                <div class="invalid-feedback d-block small message-erreur"></div>
            </div>
        </div>
    </div>

    {{-- Délai d'alerte des reliquats --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre" data-cle="delai_alerte_reliquat_jours">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Délai d'alerte des reliquats</h6>
                <p class="small text-muted">
                    Pilote les badges d'âge de l'écran Reliquats et le KPI du tableau de bord.
                </p>

                <div class="input-group input-group-sm">
                    <input type="number" class="form-control champ-parametre" value="{{ $delaiReliquat }}"
                           min="1" max="365" aria-label="Délai d'alerte en jours">
                    <span class="input-group-text">jours</span>
                    <button class="btn btn-outline-primary btn-enregistrer" type="button">Enregistrer</button>
                </div>
                <div class="invalid-feedback d-block small message-erreur"></div>
            </div>
        </div>
    </div>

    {{-- Seuil d'écart de prix --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre" data-cle="seuil_ecart_prix_pct">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Seuil d'écart de prix</h6>
                <p class="small text-muted">
                    Au-delà, la ligne est signalée à la saisie, au visa et au rapport Signaux.
                </p>

                <div class="input-group input-group-sm">
                    <input type="number" class="form-control champ-parametre" value="{{ $seuilEcart }}"
                           min="1" max="100" aria-label="Seuil d'écart en pourcentage">
                    <span class="input-group-text">%</span>
                    <button class="btn btn-outline-primary btn-enregistrer" type="button">Enregistrer</button>
                </div>
                <div class="invalid-feedback d-block small message-erreur"></div>
            </div>
        </div>
    </div>

    {{-- Taille max des pièces --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre" data-cle="taille_max_piece_mo">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Taille maximale des pièces jointes</h6>
                <p class="small text-muted">S'applique au dépôt des pièces justificatives (M-05).</p>

                <div class="input-group input-group-sm">
                    <input type="number" class="form-control champ-parametre" value="{{ $tailleMaxPiece }}"
                           min="1" max="100" aria-label="Taille maximale en mégaoctets">
                    <span class="input-group-text">Mo</span>
                    <button class="btn btn-outline-primary btn-enregistrer" type="button">Enregistrer</button>
                </div>
                <div class="invalid-feedback d-block small message-erreur"></div>
            </div>
        </div>
    </div>

    {{-- Motifs d'observation : pilules éditables --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre" data-cle="motifs_observation">
            <div class="card-body">
                <h6 class="fw-bold mb-1">Motifs d'observation</h6>
                <p class="small text-muted">
                    Les pilules proposées à la saisie d'un bon — un clic suffit dans 90 % des cas.
                </p>

                <div class="d-flex flex-wrap gap-2 mb-2" id="liste-motifs">
                    @foreach($motifs as $motif)
                        <span class="badge bg-secondary-subtle text-secondary-emphasis pilule-motif" data-motif="{{ $motif }}">
                            {{ $motif }}
                            <button type="button" class="btn-close btn-close-sm" aria-label="Retirer {{ $motif }}"
                                    style="font-size:.6rem;"></button>
                        </span>
                    @endforeach
                </div>

                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="nouveau-motif"
                           placeholder="Ajouter un motif…" maxlength="60">
                    <button class="btn btn-outline-secondary" type="button" id="btn-ajouter-motif">Ajouter</button>
                    <button class="btn btn-outline-primary btn-enregistrer" type="button">Enregistrer</button>
                </div>
                <div class="invalid-feedback d-block small message-erreur"></div>
            </div>
        </div>
    </div>

    {{-- Régularisation : interrupteur + dette + réouverture SW-06 --}}
    <div class="col-md-6">
        <div class="card h-100 carte-parametre border-warning" id="carte-regularisation"
             data-url-reactiver="{{ route('achat.regularisation.reactiver') }}">
            <div class="card-body">
                <h6 class="fw-bold mb-1">
                    <span class="pictogramme-regularisation me-1" aria-hidden="true"></span>
                    Régularisation de l'intérim
                </h6>
                <p class="small text-muted mb-2">
                    Le mode s'éteint <strong>automatiquement</strong> quand la dette atteint zéro.
                    Le rouvrir est un geste délibéré, motivé et tracé.
                </p>

                <div class="d-flex align-items-center gap-2 mb-2">
                    @if($regularisationActive)
                        <span class="badge bg-success">Ouverte</span>
                    @else
                        <span class="badge bg-secondary">Fermée</span>
                    @endif

                    <span class="badge bg-{{ $detteInterim > 0 ? 'warning text-dark' : 'success' }}">
                        Dette : {{ $detteInterim }} équipement(s) sans origine
                    </span>
                </div>

                @if($intermede['debut'] || $intermede['fin'])
                    <p class="small text-muted mb-2">
                        Période d'intérim :
                        {{ $intermede['debut'] ? \Illuminate\Support\Carbon::parse($intermede['debut'])->format('d/m/Y') : '—' }}
                        →
                        {{ $intermede['fin'] ? \Illuminate\Support\Carbon::parse($intermede['fin'])->format('d/m/Y') : 'la mise en service' }}
                    </p>
                @endif

                @unless($regularisationActive)
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btn-rouvrir-regularisation">
                        <i class="bi bi-unlock me-1"></i>Rouvrir la régularisation…
                    </button>
                @else
                    <p class="small text-muted mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Elle se fermera d'elle-même au dernier rattachement.
                    </p>
                @endunless
            </div>
        </div>
    </div>

</div>

@endsection

@push('js')
<script type="module" src="{{ asset('js/modules/achat/administration/index.js') }}?v={{ time() }}"></script>
@endpush
