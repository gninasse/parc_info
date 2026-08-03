{{--
    Champ scan douchette (S6 — UX §0.5) : autofocus permanent, refocus
    programmatique, bips Web Audio, historique des 5 derniers scans.
    Comportement porté par public/js/modules/stock/shared/scan-field.js :

    @include('stock::shared._scan_field', ['id' => 'scan-pointage'])
    puis côté page : new StockScanField('#scan-pointage', { onScan: (code, champ) => ... })

    Paramètres :
      - $id          : identifiant du champ (défaut 'champ-scan')
      - $placeholder : texte d'invite (défaut « Scannez un n° de série… »)
      - $label       : libellé accessible (défaut « Numéro de série »)
--}}
@php
    $id = $id ?? 'champ-scan';
    $placeholder = $placeholder ?? 'Scannez un n° de série…';
    $label = $label ?? 'Numéro de série';
@endphp

<div class="stock-scan-field" data-scan-field="{{ $id }}">
    <label for="{{ $id }}" class="form-label visually-hidden">{{ $label }}</label>
    <div class="input-group input-group-lg">
        <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
        <input type="text"
               id="{{ $id }}"
               class="form-control font-monospace"
               placeholder="{{ $placeholder }}"
               autocomplete="off"
               autocapitalize="off"
               spellcheck="false"
               autofocus
               data-file-scans-max="{{ config('stock.file_scans_max', 50) }}">
    </div>
    <div id="{{ $id }}-message" class="form-text text-danger d-none" role="alert"></div>
    {{-- Historique visuel des 5 derniers scans (rempli par scan-field.js) --}}
    <ul id="{{ $id }}-historique" class="list-unstyled small mt-2 mb-0" aria-live="polite" aria-label="Derniers scans"></ul>
</div>

@push('js')
<script src="{{ asset('js/modules/stock/shared/scan-field.js') }}"></script>
@endpush
