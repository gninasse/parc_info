{{--
    Fil d'étapes ①② de la saisie d'un bon de commande (SPEC_UX A-03, UX2-03).

    Calqué sur `stock::shared._stepper` — l'interface du module Achat est
    dérivée de celle du Stock à l'écran près (décision A12) — mais avec ses
    deux étapes propres : les lignes puis le récapitulatif.

    @include('achat::shared._stepper', ['etapeCourante' => 1])
--}}
@php
    $etapes = $etapes ?? ['Lignes & montants', 'Récapitulatif & soumission'];
    $etapeCourante = $etapeCourante ?? 1;
@endphp

<ol class="achat-stepper d-flex list-unstyled align-items-center gap-2 mb-4" aria-label="Étapes du bon de commande">
    @foreach($etapes as $index => $libelle)
        @php
            $numero = $index + 1;
            $etat = $numero < $etapeCourante ? 'faite' : ($numero === (int) $etapeCourante ? 'courante' : 'a-venir');
        @endphp
        <li class="d-flex align-items-center gap-2 {{ $etat === 'courante' ? 'fw-bold text-primary' : ($etat === 'faite' ? 'text-success' : 'text-secondary') }}"
            @if($etat === 'courante') aria-current="step" @endif>
            <span class="d-inline-flex align-items-center justify-content-center rounded-circle border {{ $etat === 'courante' ? 'border-primary bg-primary text-white' : ($etat === 'faite' ? 'border-success bg-success text-white' : 'border-secondary') }}"
                  style="width: 2rem; height: 2rem;">
                @if($etat === 'faite')<i class="bi bi-check-lg"></i>@else{{ $numero }}@endif
            </span>
            <span>{{ $libelle }}</span>
        </li>
        @unless($loop->last)
            <li class="flex-grow-1 border-top {{ $numero < $etapeCourante ? 'border-success' : 'border-secondary-subtle' }}" aria-hidden="true" style="min-width: 2rem;"></li>
        @endunless
    @endforeach
</ol>
