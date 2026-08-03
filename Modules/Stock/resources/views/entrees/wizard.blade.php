@extends('stock::layouts.master')

@section('header', 'Saisie des numéros de série — '.$entree->numero_affiche)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ route('stock.entrees.index') }}">Entrées</a></li>
    <li class="breadcrumb-item active" aria-current="page">Référencement</li>
@endsection

@push('css')
<style>
    .bandeau-progression { position: sticky; top: 0; z-index: 110; }
    .barre-collante { position: sticky; bottom: 0; z-index: 100; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
    .rangee-serie .indicateur-enregistre { opacity: 0; transition: opacity .2s; }
    .rangee-serie .indicateur-enregistre.visible { opacity: 1; }
    #bandeau-hors-ligne { position: sticky; top: 58px; z-index: 105; }
</style>
@endpush

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 2])

{{-- Bandeau de progression sticky --}}
<div class="card border-0 shadow-sm mb-2 bandeau-progression">
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between small mb-1">
                <span><strong id="progression-texte">{{ $saisis }}/{{ $total }}</strong> références saisies</span>
                <span class="badge bg-warning text-dark">Saisie des n° de série</span>
            </div>
            <div class="progress" style="height: 8px;" aria-label="Progression du référencement">
                <div class="progress-bar" id="progression-barre" role="progressbar"
                     style="width: {{ $total > 0 ? round($saisis * 100 / $total) : 0 }}%"></div>
            </div>
        </div>
        <a href="{{ route('stock.entrees.index') }}" class="btn btn-outline-secondary btn-sm">
            Enregistrer et continuer plus tard
        </a>
    </div>
</div>

{{-- Rappel du verrouillage (I16) --}}
<div class="text-muted small mb-3">
    <i class="bi bi-lock-fill" data-bs-toggle="tooltip"
       title="Lignes et quantités verrouillées pendant le référencement — « Revenir au brouillon » pour les modifier"></i>
    Lignes et quantités verrouillées pendant le référencement — « Revenir au brouillon » pour les modifier
</div>

{{-- Perte réseau (amendement UX n°23) --}}
<div class="alert alert-warning py-2 d-none" id="bandeau-hors-ligne" role="alert">
    <i class="bi bi-wifi-off me-1"></i>
    Connexion perdue — les saisies continuent et seront renvoyées automatiquement.
    <strong><span id="hors-ligne-compteur">0</span> en attente</strong>.
</div>

{{-- Champ scan global (S6) : remplit la première rangée vide --}}
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        @include('stock::shared._scan_field', [
            'id' => 'scan-wizard',
            'placeholder' => 'Scannez un n° de série — il remplit la prochaine rangée vide…',
        ])
    </div>
</div>

{{-- Accordéons par ligne modèle × N --}}
<div class="accordion mb-3" id="accordeon-lignes">
    @foreach($lignesModeles as $ligne)
        @php
            $saisisLigne = $ligne->tampons->whereNotNull('numero_serie')->count();
        @endphp
        <div class="accordion-item" data-ligne-id="{{ $ligne->id }}">
            <h2 class="accordion-header">
                <button class="accordion-button @if($loop->index > 0) collapsed @endif" type="button"
                        data-bs-toggle="collapse" data-bs-target="#ligne-{{ $ligne->id }}">
                    <span class="badge bg-dark me-2">E</span>
                    <strong>{{ $ligne->article->nom }}</strong>
                    <span class="ms-2 text-muted compteur-ligne" data-total="{{ (int) $ligne->quantite }}">{{ $saisisLigne }}/{{ (int) $ligne->quantite }}</span>
                </button>
            </h2>
            <div id="ligne-{{ $ligne->id }}" class="accordion-collapse collapse @if($loop->first) show @endif"
                 data-bs-parent="#accordeon-lignes">
                <div class="accordion-body p-0">
                    <table class="table table-sm align-middle mb-0">
                        <tbody>
                            @foreach($ligne->tampons as $tampon)
                                <tr class="rangee-serie" data-tampon-id="{{ $tampon->id }}">
                                    <td class="text-muted text-center" style="width:56px;">{{ $loop->iteration }}</td>
                                    <td>
                                        <input type="text"
                                               class="form-control form-control-sm font-monospace champ-serie"
                                               value="{{ $tampon->numero_serie }}"
                                               placeholder="N° de série"
                                               autocomplete="off" autocapitalize="off" spellcheck="false"
                                               aria-label="Numéro de série rangée {{ $loop->iteration }}">
                                        <div class="invalid-feedback d-block small message-erreur"></div>
                                    </td>
                                    <td style="width:130px;" class="text-center">
                                        <span class="indicateur-enregistre text-success small @if($tampon->numero_serie) visible @endif">
                                            <i class="bi bi-check-lg"></i> enregistré
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Barre collante --}}
<div class="barre-collante py-2 px-3 d-flex flex-wrap align-items-center gap-2">
    <button type="button" class="btn btn-outline-secondary" id="btn-importer">
        <i class="bi bi-file-earmark-arrow-up me-1"></i>Importer une liste
    </button>
    <div class="flex-grow-1"></div>
    <a href="{{ route('stock.entrees.index') }}" class="btn btn-outline-secondary">Reprendre plus tard</a>
    <button type="button" class="btn btn-outline-warning" id="btn-retour-brouillon">
        <i class="bi bi-arrow-counterclockwise me-1"></i>Revenir au brouillon
    </button>
    <button type="button" class="btn btn-primary" id="btn-valider"
            @if($saisis < $total) disabled data-bs-toggle="tooltip" title="{{ $total - $saisis }} références manquantes" @endif>
        <i class="bi bi-check-lg me-1"></i>Valider
    </button>
</div>

@include('stock::entrees._modal_import')
@endsection

@push('js')
<script>
    window.WIZARD = {
        entreeId: @json($entree->id),
        saisis: @json($saisis),
        total: @json($total),
        magasin: @json($entree->magasin?->libelle),
        fileScansMax: @json(config('stock.file_scans_max', 50)),
    };
</script>
<script type="module" src="{{ asset('js/modules/stock/entrees/wizard.js') }}?v={{ time() }}"></script>
@endpush
