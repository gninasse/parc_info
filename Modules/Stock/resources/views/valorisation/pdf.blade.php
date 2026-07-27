<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Valorisation du stock</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #0d2060; }
        .meta { color: #6c757d; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6; padding: 4px 6px; }
        th { background: #f8f9fa; text-align: left; }
        .text-end { text-align: right; }
        tfoot td { font-weight: bold; background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Valorisation du stock (FIFO)</h1>
    <div class="meta">CHU-YO — généré le {{ $genereLe }}</div>

    <table>
        <thead>
            <tr>
                <th>Magasin</th>
                <th>Code</th>
                <th>Article</th>
                <th class="text-end">Quantité</th>
                <th class="text-end">Valeur FIFO (FCFA)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['magasin'] }}</td>
                    <td>{{ $ligne['code_article'] }}</td>
                    <td>{{ $ligne['article'] }}</td>
                    <td class="text-end">{{ $ligne['quantite'] }}</td>
                    <td class="text-end">{{ number_format($ligne['valeur_fifo'], 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center; color:#6c757d;">Aucun stock.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end">Valeur totale</td>
                <td class="text-end">{{ number_format($valeurTotale, 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
