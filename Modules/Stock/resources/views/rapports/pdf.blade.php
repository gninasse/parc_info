<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $titre }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; }
        h1 { font-size: 15px; margin: 0 0 2px; color: #0d2060; }
        .meta { color: #6c757d; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #dee2e6; padding: 4px 6px; }
        th { background: #f8f9fa; text-align: left; }
    </style>
</head>
<body>
    <h1>{{ $titre }}</h1>
    <div class="meta">CHU-YO — généré le {{ $genereLe }} — {{ count($lignes) }} ligne(s)</div>

    <table>
        <thead>
            <tr>
                @foreach($colonnes as $colonne)
                    <th>{{ $colonne }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($lignes as $ligne)
                <tr>
                    @foreach($champs as $champ)
                        <td>{{ is_numeric($ligne[$champ] ?? null) && str_contains($champ, 'valeur') ? number_format($ligne[$champ], 0, ',', ' ') : ($ligne[$champ] ?? '-') }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($colonnes) }}" style="text-align:center; color:#6c757d;">Aucune donnée.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
