{{--
    Gabarit PDF générique des rapports A-07 (D-16).

    Le titre PORTE la qualification HT/TTC, et les filtres sont imprimés :
    un export circule sans son écran, il doit se relire seul six mois plus
    tard sans qu'on ait à deviner son périmètre.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        @page { margin: 18mm 12mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #212529; }
        h1 { font-size: 14px; margin: 0 0 2mm; }
        .filtres { font-size: 8px; color: #555; margin-bottom: 4mm; }
        .filtres span { margin-right: 6mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 0.4px solid #adb5bd; padding: 1.6mm 2mm; text-align: left; }
        th { background: #e9ecef; font-size: 8px; text-transform: uppercase; }
        td.nombre { text-align: right; }
        tr:nth-child(even) td { background: #f8f9fa; }
        .pied { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7px; color: #6c757d; }
        .vide { padding: 8mm; text-align: center; color: #6c757d; }
    </style>
</head>
<body>

<h1>{{ $titre }}</h1>

<div class="filtres">
    @foreach($filtres as $libelle => $valeur)
        <span><strong>{{ $libelle }}</strong> : {{ $valeur }}</span>
    @endforeach
    <span><strong>Édité le</strong> : {{ $genereLe->format('d/m/Y à H:i') }}</span>
    @if($generePar)
        <span><strong>Par</strong> : {{ $generePar }}</span>
    @endif
</div>

@if(count($lignes) === 0)
    <p class="vide">Aucune donnée pour ce périmètre.</p>
@else
    <table>
        <thead>
            <tr>
                @foreach($colonnes as $colonne)
                    <th>{{ ucfirst(str_replace('_', ' ', $colonne)) }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    @foreach($colonnes as $colonne)
                        @php $valeur = $ligne[$colonne] ?? null; @endphp
                        <td class="{{ is_numeric($valeur) ? 'nombre' : '' }}">
                            {{ is_numeric($valeur) && ! is_int($valeur)
                                ? number_format((float) $valeur, 0, ',', ' ')
                                : ($valeur ?? '—') }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="pied">
    {{ config('app.name') }} — module Achat · {{ $titre }}
</div>

</body>
</html>
