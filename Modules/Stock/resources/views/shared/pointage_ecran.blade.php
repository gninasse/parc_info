{{--
    Écran de pointage PARTAGÉ sorties/transferts (D14/D17 — UX §4.3/§5).
    Paramètres fournis par le contrôleur appelant :
      - $document (DocumentAPointage), $lignesModeles, $pointees, $attendues
      - $routes : [update, retour, valider, unites, index]
      - $magasinSourceId, $verrouillageMessage
      - $titre, $filAriane (libellé de section : Sorties | Transferts)
--}}
@extends('stock::layouts.master')

@section('header', $titre)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="#">Accueil</a></li>
    <li class="breadcrumb-item active" aria-current="page">Stock</li>
    <li class="breadcrumb-item"><a href="{{ $routes['index'] }}">{{ $filAriane }}</a></li>
    <li class="breadcrumb-item active" aria-current="page">Pointage</li>
@endsection

@push('css')
<style>
    .bandeau-progression { position: sticky; top: 0; z-index: 110; }
    .barre-collante { position: sticky; bottom: 0; z-index: 100; background: var(--bs-body-bg); border-top: 1px solid var(--bs-border-color); box-shadow: 0 -4px 12px rgba(0,0,0,.06); }
</style>
@endpush

@section('content')

@include('stock::shared._stepper', ['etapeCourante' => 2, 'etapes' => ['Quantités', 'Pointage des unités', 'Validation']])

<div class="card border-0 shadow-sm mb-2 bandeau-progression">
    <div class="card-body py-2 d-flex flex-wrap align-items-center gap-3">
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between small mb-1">
                <span><strong id="progression-texte">{{ $pointees }}/{{ $attendues }}</strong> unités pointées</span>
                <span class="badge bg-warning text-dark">Pointage en cours</span>
            </div>
            <div class="progress" style="height: 8px;" aria-label="Progression du pointage">
                <div class="progress-bar" id="progression-barre" role="progressbar"
                     style="width: {{ $attendues > 0 ? round($pointees * 100 / $attendues) : 0 }}%"></div>
            </div>
        </div>
        <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary btn-sm">Enregistrer et continuer plus tard</a>
    </div>
</div>

<div class="text-muted small mb-3">
    <i class="bi bi-lock-fill" data-bs-toggle="tooltip" title="{{ $verrouillageMessage }}"></i>
    {{ $verrouillageMessage }}
</div>

{{-- Accordéons par ligne modèle × N --}}
<div class="accordion mb-3" id="accordeon-pointage">
    @foreach($lignesModeles as $ligne)
        <div class="accordion-item" data-ligne-id="{{ $ligne->id }}" data-modele-id="{{ $ligne->article->id }}" data-quantite="{{ (int) $ligne->quantite }}">
            <h2 class="accordion-header">
                <button class="accordion-button @if($loop->index > 0) collapsed @endif" type="button"
                        data-bs-toggle="collapse" data-bs-target="#pointage-ligne-{{ $ligne->id }}">
                    <span class="badge bg-dark me-2">E</span>
                    <strong>{{ $ligne->article->nom }}</strong>
                    <span class="ms-2 text-muted compteur-ligne">{{ $ligne->tampons->count() }}/{{ (int) $ligne->quantite }}</span>
                </button>
            </h2>
            <div id="pointage-ligne-{{ $ligne->id }}" class="accordion-collapse collapse @if($loop->first) show @endif"
                 data-bs-parent="#accordeon-pointage">
                <div class="accordion-body">
                    <button type="button" class="btn btn-outline-primary btn-sm btn-choisir-unites mb-2" data-ligne-id="{{ $ligne->id }}">
                        <i class="bi bi-upc-scan me-1"></i>Choisir des unités…
                    </button>
                    <table class="table table-sm align-middle mb-0">
                        <tbody class="liste-pointages" data-ligne-id="{{ $ligne->id }}">
                            @foreach($ligne->tampons as $tampon)
                                <tr data-tampon-id="{{ $tampon->id }}">
                                    <td class="font-monospace">{{ $tampon->equipement?->code_inventaire }}</td>
                                    <td>{{ $tampon->equipement?->modele }}</td>
                                    <td class="font-monospace">{{ $tampon->equipement?->numero_serie }}</td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-depointer" data-tampon-id="{{ $tampon->id }}">
                                            <i class="fas fa-times"></i>
                                        </button>
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

<div class="barre-collante py-2 px-3 d-flex flex-wrap align-items-center gap-2">
    <div class="flex-grow-1"></div>
    <a href="{{ $routes['index'] }}" class="btn btn-outline-secondary">Reprendre plus tard</a>
    <button type="button" class="btn btn-outline-warning" id="btn-retour-brouillon">
        <i class="bi bi-arrow-counterclockwise me-1"></i>Revenir au brouillon
    </button>
    <button type="button" class="btn btn-primary" id="btn-valider"
            @if($pointees < $attendues) disabled data-bs-toggle="tooltip" title="{{ $attendues - $pointees }} unité(s) restant à pointer" @endif>
        <i class="bi bi-check-lg me-1"></i>Valider
    </button>
</div>

@include('stock::shared._selecteur_unites')
@endsection

@push('js')
<script>
    window.POINTAGE = {
        routes: @json($routes),
        magasinSourceId: @json($magasinSourceId),
        pointees: @json($pointees),
        attendues: @json($attendues),
        typeDocument: @json($typeDocument ?? 'sortie'),
    };
</script>
<script type="module" src="{{ asset('js/modules/stock/shared/pointage-ecran.js') }}?v={{ time() }}"></script>
@endpush
