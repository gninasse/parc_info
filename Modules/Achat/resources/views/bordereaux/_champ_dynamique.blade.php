{{--
    Rend un champ personnalisé de catégorie d'équipement (RG-INT-05 / EF-INT-05).
    Le contrôle affiché suit la définition portée par ParcInfo.

    Variables : $champ, $name, $valeur, $classeSupplementaire
--}}
@php $classe = 'form-control form-control-sm '.($classeSupplementaire ?? ''); @endphp

@switch($champ->type_champ)
    @case('select')
        <select name="{{ $name }}"
                class="form-select form-select-sm {{ $classeSupplementaire ?? '' }}"
                data-code-champ="{{ $champ->code }}">
            <option value="">Sélectionner…</option>
            @foreach($champ->options_resolved ?? [] as $cle => $libelle)
                <option value="{{ $cle }}" @selected((string) $valeur === (string) $cle)>{{ $libelle }}</option>
            @endforeach
        </select>
        @break

    @case('number')
        <input type="number" name="{{ $name }}" class="{{ $classe }}"
               data-code-champ="{{ $champ->code }}" value="{{ $valeur }}">
        @break

    @case('date')
        <input type="date" name="{{ $name }}" class="{{ $classe }}"
               data-code-champ="{{ $champ->code }}" value="{{ $valeur }}">
        @break

    @case('boolean')
        <select name="{{ $name }}"
                class="form-select form-select-sm {{ $classeSupplementaire ?? '' }}"
                data-code-champ="{{ $champ->code }}">
            <option value="">—</option>
            <option value="1" @selected((string) $valeur === '1')>Oui</option>
            <option value="0" @selected((string) $valeur === '0')>Non</option>
        </select>
        @break

    @default
        <input type="text" name="{{ $name }}" class="{{ $classe }}"
               data-code-champ="{{ $champ->code }}" value="{{ $valeur }}">
@endswitch
