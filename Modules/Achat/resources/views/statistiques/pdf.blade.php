<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 12mm 10mm 16mm 10mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #212529; margin: 0; }

        .entete { border-bottom: 2px solid #0d2060; padding-bottom: 6px; margin-bottom: 10px; }
        .etablissement { font-size: 12px; font-weight: bold; color: #0d2060; }
        .sous-titre { font-size: 8px; color: #6c757d; }
        .titre-rapport {
            font-size: 14px; font-weight: bold; color: #0d2060;
            text-transform: uppercase; margin-top: 6px;
        }

        .filtres {
            border: 1px solid #dee2e6; background-color: #f8f9fa;
            padding: 6px 8px; margin-bottom: 10px; font-size: 8.5px;
        }
        .filtres strong { color: #0d2060; }

        table.donnees { width: 100%; border-collapse: collapse; }
        table.donnees th {
            background-color: #0d2060; color: #ffffff; font-size: 8.5px;
            text-transform: uppercase; padding: 5px 4px; text-align: left;
        }
        table.donnees td { padding: 4px; border-bottom: 1px solid #e9ecef; }
        table.donnees tr:nth-child(even) td { background-color: #f8f9fa; }

        .vide { text-align: center; padding: 16px; color: #6c757d; font-style: italic; }

        .pied {
            position: fixed; bottom: -8mm; left: 0; right: 0;
            font-size: 7px; color: #6c757d;
            border-top: 1px solid #dee2e6; padding-top: 3px;
        }
    </style>
</head>
<body>

<div class="entete">
    <div class="etablissement">CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO</div>
    <div class="sous-titre">Direction des Systèmes d'Information &mdash; Service Approvisionnement</div>
    <div class="titre-rapport">{{ $title }}</div>
</div>

<div class="filtres">
    <strong>Critères appliqués :</strong>
    @forelse($filters as $libelle => $valeur)
        {{ $libelle }} = {{ $valeur }}@if(! $loop->last) &nbsp;|&nbsp; @endif
    @empty
        aucun filtre (portée complète)
    @endforelse
    &nbsp;|&nbsp; <strong>{{ count($rows) }}</strong> ligne(s)
</div>

<table class="donnees">
    <thead>
        <tr>
            @foreach($columns as $entete)
                <th>{{ $entete }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $ligne)
            <tr>
                @foreach(array_keys($columns) as $cle)
                    <td>{{ $ligne[$cle] ?? '—' }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ max(count($columns), 1) }}" class="vide">
                    Aucune donnée ne correspond aux critères sélectionnés.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="pied">
    {{ $title }} &mdash; édité le {{ $genereLe->format('d/m/Y à H:i') }}
    @if($genrePar) par {{ $genrePar }} @endif
    &mdash; CHU Yalgado Ouédraogo, système de gestion du parc informatique.
</div>

</body>
</html>
