<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }
        .header table {
            width: 100%;
        }
        .logo-title {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
        }
        .report-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 5px;
            color: #212529;
            text-transform: uppercase;
        }
        .meta-info {
            text-align: right;
            font-size: 10px;
            color: #6c757d;
        }
        .filters {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .filters-title {
            font-weight: bold;
            font-size: 10px;
            color: #495057;
            margin-bottom: 3px;
        }
        .filters-list {
            font-size: 9px;
            color: #6c757d;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table.data-table th {
            background-color: #0d6efd;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #0d6efd;
            font-size: 10px;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #dee2e6;
            font-size: 9px;
            vertical-align: middle;
        }
        table.data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            color: white;
        }
        .bg-secondary { background-color: #6c757d; }
        .bg-success { background-color: #198754; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-danger { background-color: #dc3545; }
        .bg-dark { background-color: #212529; }
        .text-right {
            text-align: right;
        }
        .footer {
            position: fixed;
            bottom: -30px;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 9px;
            color: #adb5bd;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
        }
        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td>
                    <div class="logo-title">PARC INFO</div>
                    <div class="report-title">{{ $title }}</div>
                </td>
                <td class="meta-info">
                    Généré le : {{ now()->format('d/m/Y H:i') }}<br>
                    Par : {{ auth()->user() ? auth()->user()->name : 'Système' }}
                </td>
            </tr>
        </table>
    </div>

    @if(!empty($filters))
        <div class="filters">
            <div class="filters-title">Filtres appliqués :</div>
            <div class="filters-list">
                @foreach($filters as $name => $value)
                    <strong>{{ $name }} :</strong> {{ $value }} &nbsp;&nbsp;&nbsp;
                @endforeach
            </div>
        </div>
    @endif

    <table class="data-table">
        <thead>
            <tr>
                @foreach($columns as $key => $label)
                    <th class="{{ in_array($key, ['valeur_achat', 'cout', 'cout_unitaire', 'cout_total', 'valeur_totale']) ? 'text-right' : '' }}">{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $key => $label)
                        @php
                            $val = data_get($row, $key);
                            // Format status & state badges
                            $isBadge = in_array($key, ['statut', 'etat']);
                            
                            // Alignments and formats
                            $isAmount = in_array($key, ['valeur_achat', 'cout', 'cout_unitaire', 'cout_total', 'valeur_totale']);
                        @endphp
                        <td class="{{ $isAmount ? 'text-right' : '' }}">
                            @if($isBadge)
                                @if($key === 'statut')
                                    @php
                                        $classes = [
                                            'en_stock' => 'bg-secondary',
                                            'en_service' => 'bg-success',
                                            'en_reparation' => 'bg-warning',
                                            'perdu' => 'bg-danger',
                                            'reforme' => 'bg-dark'
                                        ];
                                        $labels = [
                                            'en_stock' => 'En stock',
                                            'en_service' => 'En service',
                                            'en_reparation' => 'En réparation',
                                            'perdu' => 'Perdu',
                                            'reforme' => 'Réformé'
                                        ];
                                    @endphp
                                    <span class="badge {{ $classes[$val] ?? 'bg-secondary' }}">{{ $labels[$val] ?? $val }}</span>
                                @elseif($key === 'etat')
                                    @php
                                        $classes = [
                                            'bon' => 'bg-success',
                                            'passable' => 'bg-secondary',
                                            'mauvais' => 'bg-warning',
                                            'avarie' => 'bg-danger'
                                        ];
                                        $labels = [
                                            'bon' => 'Bon',
                                            'passable' => 'Passable',
                                            'mauvais' => 'Mauvais',
                                            'avarie' => 'Avarié'
                                        ];
                                    @endphp
                                    <span class="badge {{ $classes[$val] ?? 'bg-secondary' }}">{{ $labels[$val] ?? $val }}</span>
                                @endif
                            @elseif($isAmount)
                                {{ number_format((float)$val, 2, ',', ' ') }} FCFA
                            @elseif($val instanceof \Carbon\Carbon || (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)))
                                {{ \Carbon\Carbon::parse($val)->format('d/m/Y') }}
                            @else
                                {{ $val ?? '-' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align: center; padding: 15px; color: #6c757d;">
                        Aucun enregistrement trouvé.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Page <span class="page-number"></span> / Parc Info - Rapports Automatiques
    </div>

</body>
</html>
