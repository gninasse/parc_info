{{-- Export PDF générique des états du module Stock — A4 paysage, lisible en N&B (S7/S9). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $etat['titre'] }}</title>
    <style>
        @page { margin: 18px 20px 28px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .intention { font-size: 9px; color: #444; font-style: italic; margin-bottom: 6px; }
        .filtres { font-size: 8.5px; color: #333; margin-bottom: 8px; }
        .resume { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .resume td {
            border: 0.5px solid #999; padding: 4px 6px; width: 25%;
            font-size: 8.5px;
        }
        .resume .libelle { color: #444; text-transform: uppercase; font-size: 7.5px; display: block; }
        .resume .valeur { font-weight: bold; font-size: 10px; }
        table.donnees { width: 100%; border-collapse: collapse; }
        table.donnees th, table.donnees td { border: 0.5px solid #666; padding: 2.5px 4px; }
        table.donnees th { background: #e8e8e8; text-align: left; text-transform: uppercase; font-size: 7.5px; }
        table.donnees tfoot td { background: #dcdcdc; font-weight: bold; }
        .num { text-align: right; }
        .vide { text-align: center; padding: 14px; font-style: italic; }
    </style>
</head>
<body>
    <h1>{{ $etat['titre'] }} — CHU-YO</h1>
    <div class="intention">{{ $etat['intention'] }}</div>

    <div class="filtres">
        @foreach($filtres as $libelle => $valeur)
            <strong>{{ $libelle }}</strong> : {{ $valeur }}@if(! $loop->last) · @endif
        @endforeach
    </div>

    @if(! empty($etat['resume']))
        <table class="resume">
            <tr>
                @foreach($etat['resume'] as $item)
                    <td>
                        <span class="libelle">{{ $item['libelle'] }}</span>
                        <span class="valeur">{{ $item['valeur'] }}</span>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @php
        $numerique = ['nombre', 'decimal', 'montant'];
        $formater = function ($valeur, string $type) {
            if ($valeur === null || $valeur === '') {
                return '—';
            }

            // Les libellés de la ligne de totaux (« TOTAL ») peuvent tomber sur
            // une colonne typée : on ne formate que ce qui est du bon type.
            if (in_array($type, ['montant', 'decimal', 'nombre'], true) && ! is_numeric($valeur)) {
                return $valeur;
            }

            if (in_array($type, ['date', 'datetime'], true)) {
                try {
                    $date = \Illuminate\Support\Carbon::parse($valeur);
                } catch (\Throwable) {
                    return $valeur;
                }

                return $date->format($type === 'date' ? 'd/m/Y' : 'd/m/Y H:i');
            }

            return match ($type) {
                'montant' => number_format((float) $valeur, 0, ',', ' '),
                'decimal' => number_format((float) $valeur, 2, ',', ' '),
                'nombre' => number_format((float) $valeur, 0, ',', ' '),
                default => $valeur,
            };
        };
    @endphp

    <table class="donnees">
        <thead>
            <tr>
                @foreach($etat['colonnes'] as $colonne)
                    <th class="{{ in_array($colonne['type'], $numerique, true) ? 'num' : '' }}">{{ $colonne['libelle'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($etat['lignes'] as $ligne)
                <tr>
                    @foreach($etat['colonnes'] as $colonne)
                        <td class="{{ in_array($colonne['type'], $numerique, true) ? 'num' : '' }}">
                            {{ $formater($ligne[$colonne['cle']] ?? null, $colonne['type']) }}
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($etat['colonnes']) }}" class="vide">Aucune donnée pour ces filtres.</td></tr>
            @endforelse
        </tbody>
        @if(! empty($etat['totaux']) && count($etat['lignes']) > 0)
            <tfoot>
                <tr>
                    @foreach($etat['colonnes'] as $colonne)
                        <td class="{{ in_array($colonne['type'], $numerique, true) ? 'num' : '' }}">
                            {{ array_key_exists($colonne['cle'], $etat['totaux']) ? $formater($etat['totaux'][$colonne['cle']], $colonne['type']) : '' }}
                        </td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    @include('stock::pdf._cartouche', [
        'creePar' => $generePar,
        'creeLe' => $genereLe,
    ])
</body>
</html>
