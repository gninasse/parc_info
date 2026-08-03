{{--
    Fil d'étapes ①②③ des pages de document (S12 — UX §0.5).

    @include('stock::shared._stepper', ['etapeCourante' => 2])
    Paramètres :
      - $etapeCourante : 1..3 (étape en bleu, précédentes en vert coché)
      - $etapes        : libellés (défaut : Quantités — N° de série — Validation)
--}}
@php
    $etapes = $etapes ?? ['Quantités', 'N° de série', 'Validation'];
    $etapeCourante = $etapeCourante ?? 1;
@endphp

<ol class="stock-stepper d-flex list-unstyled align-items-center gap-2 mb-4" aria-label="Étapes du document">
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
