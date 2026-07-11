<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333333;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .title {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin: 0 0 5px 0;
        }
        .subtitle {
            font-size: 11px;
            color: #666666;
            margin: 0;
        }
        .filter-section {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .filter-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
            text-transform: uppercase;
            font-size: 9px;
        }
        .filter-item {
            display: inline-block;
            margin-right: 15px;
        }
        .filter-label {
            font-weight: bold;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f1f3f5;
            color: #495057;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            font-size: 9px;
            color: #999999;
        }
        .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%; border: none; margin: 0;">
            <tr style="background: none;">
                <td style="border: none; padding: 0; width: 70%;">
                    <div class="title">{{ $title }}</div>
                    <div class="subtitle">CHU-YO — Module de Gestion des Achats & Approvisionnements</div>
                </td>
                <td style="border: none; padding: 0; text-align: right; width: 30%;">
                    <div style="font-weight: bold; color: #495057;">Date d'édition : {{ date('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if(count($filters) > 0)
        <div class="filter-section">
            <div class="filter-title">Filtres de recherche appliqués</div>
            @foreach($filters as $label => $val)
                <div class="filter-item">
                    <span class="filter-label">{{ $label }} :</span> <span>{{ $val }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach($columns as $key => $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $key => $label)
                        <td>{{ $row[$key] ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; color: #999999;">
                        Aucun enregistrement ne correspond aux critères sélectionnés.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        © {{ date('Y') }} CHU-YO - Gestion du Parc Informatique & Achats | Page <span class="page-number"></span>
    </div>
</body>
</html>
