{{--
    PDF de la carte SIGNAUX (D-16, SFD §7.7).

    Chaque indicateur porte son AIDE : un chiffre de contrôle sans sa
    définition se prête à toutes les interprétations, et un signal mal lu
    accuse quelqu'un à tort.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Signaux — module Achat</title>
    <style>
        @page { margin: 18mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #212529; }
        h1 { font-size: 15px; margin: 0 0 2mm; }
        h2 { font-size: 11px; margin: 6mm 0 1mm; padding-bottom: 1mm; border-bottom: 0.6px solid #adb5bd; }
        .filtres { font-size: 8px; color: #555; margin-bottom: 3mm; }
        .filtres span { margin-right: 6mm; }
        .aide { font-size: 8px; color: #6c757d; font-style: italic; margin: 0 0 1.5mm; }
        .avertissement { font-size: 8px; background: #fff3cd; border: 0.4px solid #ffe69c; padding: 2mm; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.4px solid #adb5bd; padding: 1.4mm 2mm; text-align: left; }
        th { background: #e9ecef; font-size: 8px; text-transform: uppercase; }
        td.nombre { text-align: right; }
        .vide { font-size: 8px; color: #198754; padding: 2mm 0; }
    </style>
</head>
<body>

<h1>Signaux — module Achat</h1>

<div class="filtres">
    @foreach($filtres as $libelle => $valeur)
        <span><strong>{{ $libelle }}</strong> : {{ $valeur }}</span>
    @endforeach
    <span><strong>Édité le</strong> : {{ $genereLe->format('d/m/Y à H:i') }}</span>
    @if($generePar)
        <span><strong>Par</strong> : {{ $generePar }}</span>
    @endif
</div>

{{-- La doctrine, imprimée sur le document : un signal n'est pas une preuve. --}}
<div class="avertissement">
    <strong>Ces indicateurs signalent, ils n'accusent pas.</strong>
    Un écart de prix peut être justifié, une auto-validation peut être la seule
    option un jour de congés. Ils servent à poser une question, jamais à
    conclure.
</div>

@foreach($signaux as $signal)
    <h2>{{ $signal['titre'] }}</h2>
    <p class="aide">{{ $signal['aide'] }}</p>

    @if(count($signal['lignes']) === 0)
        <p class="vide">✔ Aucun signal sur ce périmètre.</p>
    @else
        @php $colonnes = array_keys($signal['lignes'][0]); @endphp
        <table>
            <thead>
                <tr>
                    @foreach($colonnes as $colonne)
                        <th>{{ ucfirst(str_replace('_', ' ', $colonne)) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($signal['lignes'] as $ligne)
                    <tr>
                        @foreach($colonnes as $colonne)
                            @php $valeur = $ligne[$colonne] ?? null; @endphp
                            <td class="{{ is_numeric($valeur) ? 'nombre' : '' }}">
                                {{ is_float($valeur) ? number_format($valeur, 2, ',', ' ') : ($valeur ?? '—') }}
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endforeach

</body>
</html>
