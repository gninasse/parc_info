{{-- Export PDF de l'état des stocks — A4 paysage, lisible en N&B laser (S7/S9). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>État des stocks</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 2px; }
        .filtres { font-size: 9px; color: #333; margin-bottom: 10px; }
        table.donnees { width: 100%; border-collapse: collapse; }
        table.donnees th, table.donnees td { border: 0.5px solid #555; padding: 3px 5px; }
        table.donnees th { background: #e8e8e8; text-align: left; text-transform: uppercase; font-size: 8.5px; }
        .num { text-align: right; }
        .centre { text-align: center; }
        /* Statuts en texte + motif, jamais la couleur seule (S7) */
        .statut-sous-seuil { background: repeating-linear-gradient(45deg, #fff, #fff 3px, #ddd 3px, #ddd 6px); }
        .statut-rupture { background: #cfcfcf; font-weight: bold; }
    </style>
</head>
<body>
    <h1>État des stocks — CHU-YO</h1>
    <div class="filtres">
        {{-- Filtres imprimés en en-tête (amendement UX n°18) --}}
        @foreach($filtres as $libelle => $valeur)
            <strong>{{ $libelle }}</strong> : {{ $valeur }}@if(! $loop->last) · @endif
        @endforeach
    </div>

    <table class="donnees">
        <thead>
            <tr>
                <th>Article</th>
                <th class="centre">Nature</th>
                <th>Magasin</th>
                <th class="num">Quantité</th>
                <th>Unité</th>
                <th class="num">Seuil effectif</th>
                <th class="centre">Statut</th>
                <th class="num">Valeur (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['article_code'] }} — {{ $ligne['article_nom'] }}</td>
                    <td class="centre">{{ strtoupper(substr($ligne['nature'], 0, 1)) }}</td>
                    <td>{{ $ligne['magasin'] }}</td>
                    <td class="num">{{ number_format($ligne['quantite'], 2, ',', ' ') }}</td>
                    <td>{{ $ligne['unite'] }}</td>
                    <td class="num">{{ $ligne['seuil_effectif'] !== null ? number_format($ligne['seuil_effectif'], 0, ',', ' ').' ('.$ligne['seuil_origine'].')' : '—' }}</td>
                    <td class="centre {{ $ligne['statut'] === 'SOUS_SEUIL' ? 'statut-sous-seuil' : ($ligne['statut'] === 'RUPTURE' ? 'statut-rupture' : '') }}">
                        @if($ligne['statut'] === 'OK') ✓ OK
                        @elseif($ligne['statut'] === 'SOUS_SEUIL') ⚠ SOUS SEUIL
                        @else ⛔ RUPTURE
                        @endif
                    </td>
                    <td class="num">{{ number_format($ligne['valeur'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="centre">Aucune ligne pour ces filtres.</td></tr>
            @endforelse
        </tbody>
    </table>

    @include('stock::pdf._cartouche', [
        'creePar' => $generePar,
        'creeLe' => $genereLe,
    ])
</body>
</html>
