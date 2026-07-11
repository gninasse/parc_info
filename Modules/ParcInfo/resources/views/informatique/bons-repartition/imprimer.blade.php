<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BON DE RÉPARTITION — {{ $bon->numero_bon }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.4;
        }
        @page {
            margin: 40px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
            border: 0;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 5px;
        }
        .hospital-name {
            font-size: 12px;
            font-weight: bold;
            color: #1a5276;
            text-transform: uppercase;
        }
        .hospital-sub {
            font-size: 8px;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .hospital-address {
            font-size: 8px;
            color: #495057;
            line-height: 1.3;
        }
        .document-title-container {
            text-align: right;
        }
        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #1a5276;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .bon-box {
            display: inline-block;
            border: 2px solid #1a5276;
            padding: 8px 12px;
            text-align: left;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        .bon-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .bon-box td {
            padding: 2px 4px;
            font-size: 10px;
        }
        .bon-box td.label {
            font-weight: bold;
            color: #495057;
        }
        .bon-box td.value {
            font-weight: bold;
            color: #212529;
            font-family: monospace;
        }
        .divider {
            border-top: 2px solid #1a5276;
            margin-bottom: 20px;
        }
        
        .info-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 8px 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-card-text {
            font-size: 10px;
            color: #334155;
            line-height: 1.5;
        }
        .info-card-text strong {
            color: #1a5276;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #1a5276;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #1a5276;
            font-size: 9px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .badge-primary {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }
        
        .sign-box-container {
            border: 1px dashed #cbd5e1;
            height: 35px;
            margin-top: 4px;
            border-radius: 3px;
            background-color: #fafafa;
        }

        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 30px;
        }
        .signatures-table td {
            width: 33.33%;
            border: 0;
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-box {
            border: 1px dashed #cbd5e1;
            padding: 10px;
            border-radius: 4px;
            min-height: 110px;
            background-color: #f8fafc;
        }
        .signature-title {
            font-weight: bold;
            font-size: 10px;
            color: #1a5276;
            text-align: center;
            margin-bottom: 55px;
            text-decoration: underline;
        }
        .signature-line {
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
        
        .summary-info {
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 15px;
            text-align: right;
        }

        .footer {
            position: fixed;
            bottom: -15px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #64748b;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }
        .page-number:after {
            content: counter(page);
        }
        
        .clear {
            clear: both;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                @if(file_exists(public_path('images/chuyo_logo.png')))
                    <img class="logo" src="{{ public_path('images/chuyo_logo.png') }}" alt="CHU-YO">
                @endif
                <div class="hospital-name">CHU Yalgado Ouédraogo</div>
                <div class="hospital-sub">DIRECTION DES SYSTEMES D'INFORMATION</div>
                <div class="hospital-address">
                    03 BP 7022 Ouagadougou 03, Burkina Faso<br>
                    Avenue du Capitaine Thomas Sankara<br>
                    Tél: (+226) 25 31 16 55 / 56 / 57
                </div>
            </td>
            <td class="document-title-container">
                <div class="document-title">Bon de Répartition</div>
                <div class="bon-box">
                    <table>
                        <tr>
                            <td class="label">N° Bon :</td>
                            <td class="value">{{ $bon->numero_bon }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date :</td>
                            <td class="value">{{ $bon->date_bon->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Fournisseur :</td>
                            <td class="value">{{ $bon->fournisseur?->nom ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    @if($bon->observation)
        <div class="info-card">
            <div class="info-card-text">
                <strong>Observation / Motif :</strong> {{ $bon->observation }}
            </div>
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%; text-align: center;">N°</th>
                <th style="width: 25%;">Équipement</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 15%;">N° Série</th>
                <th style="width: 20%;">Destination</th>
                <th style="width: 20%;">Réceptionniste / Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bon->lignes as $i => $ligne)
                <tr>
                    <td style="text-align: center;">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $ligne->equipement->code_inventaire }}</strong>
                        @if($ligne->equipement->marque || $ligne->equipement->modele)
                            <br><span style="color:#64748b; font-size:8px;">{{ trim(($ligne->equipement->marque?->libelle ?? '') . ' ' . $ligne->equipement->modele) }}</span>
                        @endif
                    </td>
                    <td>{{ $ligne->equipement->categorie?->libelle ?? '—' }}</td>
                    <td style="font-family: monospace;">{{ $ligne->equipement->numero_serie }}</td>
                    <td>
                        <span class="badge badge-primary">{{ $ligne->type_cible }}</span>
                        <div style="margin-top: 2px;">{{ $ligne->cible_label }}</div>
                    </td>
                    <td>
                        @if($ligne->est_signe)
                            <span class="badge badge-success">✓ Réceptionné</span>
                            <div style="font-size: 8px; color: #475569; margin-top: 2px;">
                                Par: {{ $ligne->nom_receptionniste ?? 'N/A' }}<br>
                                Le: {{ $ligne->date_signature?->format('d/m/Y') }}
                            </div>
                        @else
                            <span class="badge badge-warning">En attente</span>
                            @if($ligne->nom_receptionniste)
                                <div style="font-size: 8px; color: #64748b; margin-top: 2px;">Prévu: {{ $ligne->nom_receptionniste }}</div>
                            @endif
                            <div class="sign-box-container"></div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #64748b; padding: 15px;">Aucun équipement enregistré dans ce bon de répartition.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-info">
        Total équipements : <strong>{{ $bon->lignes->count() }}</strong> |
        Réceptionnés : <strong>{{ $bon->lignes->where('est_signe', true)->count() }}</strong> |
        En attente : <strong>{{ $bon->lignes->where('est_signe', false)->count() }}</strong>
    </div>

    <table class="signatures-table">
        <tr>
            <td>
                <div class="signature-box">
                    <div class="signature-title">Établi par (Responsable DSI)</div>
                    <div class="signature-line">
                        Signature & Cachet
                    </div>
                </div>
            </td>
            <td>
                <div class="signature-box">
                    <div class="signature-title">Visé par (Direction)</div>
                    <div class="signature-line">
                        Signature & Cachet
                    </div>
                </div>
            </td>
            <td>
                <div class="signature-box">
                    <div class="signature-title">Approuvé (Administration)</div>
                    <div class="signature-line">
                        Signature & Cachet
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Imprimé le {{ now()->format('d/m/Y à H:i') }} — Système de Gestion du Parc Informatique CHU-YO
        <br>
        Page <span class="page-number"></span> / CHU-YO Parc Info - Bon de Répartition {{ $bon->numero_bon }}
    </div>

</body>
</html>
