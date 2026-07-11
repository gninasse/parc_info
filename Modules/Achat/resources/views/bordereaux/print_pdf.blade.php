<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bordereau de Livraison {{ $bordereau->numero_livraison }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
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
            margin-bottom: 25px;
        }
        .header-table td {
            vertical-align: top;
            border: 0;
        }
        .logo {
            max-height: 65px;
            margin-bottom: 5px;
        }
        .hospital-name {
            font-size: 13px;
            font-weight: bold;
            color: #212529;
            text-transform: uppercase;
        }
        .hospital-sub {
            font-size: 9px;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .hospital-address {
            font-size: 8.5px;
            color: #495057;
            line-height: 1.3;
        }
        .document-title-container {
            text-align: right;
        }
        .document-title {
            font-size: 18px;
            font-weight: 900;
            color: #28a745;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .bc-box {
            display: inline-block;
            border: 2px solid #28a745;
            padding: 8px 12px;
            text-align: left;
            background-color: #f8fafc;
            border-radius: 4px;
        }
        .bc-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .bc-box td {
            padding: 2px 4px;
            font-size: 10px;
        }
        .bc-box td.label {
            font-weight: bold;
            color: #495057;
        }
        .bc-box td.value {
            font-weight: bold;
            color: #212529;
            font-family: monospace;
        }
        .divider {
            border-top: 2px solid #28a745;
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            width: 50%;
            vertical-align: top;
            border: 0;
        }
        .info-table td.left-col {
            padding-right: 10px;
        }
        .info-table td.right-col {
            padding-left: 10px;
        }
        .info-card {
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            padding: 10px;
            border-radius: 4px;
            min-height: 105px;
        }
        .info-card-title {
            font-size: 10px;
            font-weight: bold;
            color: #1e3a8a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-card-text {
            font-size: 9.5px;
            color: #334155;
            line-height: 1.4;
        }
        .observations-section {
            border: 1px solid #e2e8f0;
            background-color: #fafafa;
            padding: 8px 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .observations-title {
            font-weight: bold;
            font-size: 9.5px;
            color: #475569;
            margin-bottom: 4px;
        }
        .observations-body {
            font-size: 9px;
            color: #334155;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #28a745;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #28a745;
            font-size: 9.5px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .clear {
            clear: both;
        }
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }
        .signatures-table td {
            width: 50%;
            border: 0;
            vertical-align: top;
        }
        .signature-box {
            border: 1px dashed #cbd5e1;
            padding: 10px;
            border-radius: 4px;
            min-height: 100px;
        }
        .signature-title {
            font-weight: bold;
            font-size: 10px;
            color: #334155;
            text-align: center;
            margin-bottom: 50px;
            text-decoration: underline;
        }
        .signature-line {
            text-align: center;
            font-size: 9px;
            color: #64748b;
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
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td>
                @if(file_exists(public_path('images/chuyo_logo.png')))
                    <img class="logo" src="{{ public_path('images/chuyo_logo.png') }}" alt="CHU-YO">
                @endif
                <div class="hospital-name">CHU-YO Achat</div>
                <div class="hospital-sub">CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUEDRAOGO</div>
                <div class="hospital-address">
                    03 BP 7022 Ouagadougou 03, Burkina Faso<br>
                    Avenue du Capitaine Thomas Sankara<br>
                    Tél: (+226) 25 31 16 55 / 56 / 57
                </div>
            </td>
            <td class="document-title-container">
                <div class="document-title">Bordereau de Livraison</div>
                <div class="bc-box">
                    <table>
                        <tr>
                            <td class="label">N° Livraison :</td>
                            <td class="value">{{ $bordereau->numero_livraison }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date Réception :</td>
                            <td class="value">{{ $bordereau->date_livraison->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Statut :</td>
                            <td class="value" style="color: {{ $bordereau->statut === 'valide' ? '#28a745' : '#4b5563' }};">
                                {{ strtoupper($bordereau->statut) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="info-table">
        <tr>
            <td class="left-col">
                <div class="info-card">
                    <div class="info-card-title">Fournisseur & Commande</div>
                    <div class="info-card-text">
                        <strong>{{ $bordereau->bonCommande->fournisseur->nom }}</strong><br>
                        N° Bon de Commande: <strong>{{ $bordereau->bonCommande->numero_commande }}</strong><br>
                        Réf. Bordereau Physique: <strong>{{ $bordereau->ref_bordereau_physique ?: '-' }}</strong><br>
                        Date de commande: {{ $bordereau->bonCommande->date_commande->format('d/m/Y') }}
                    </div>
                </div>
            </td>
            <td class="right-col">
                <div class="info-card">
                    <div class="info-card-title">Lieu de Réception</div>
                    <div class="info-card-text">
                        <strong>CHU Yalgado Ouédraogo</strong><br>
                        Service Réceptionnaire: Service des Achats & Approvisionnements<br>
                        Bâtiment: Direction Générale, Rez-de-chaussée<br>
                        Réceptionné par: {{ $bordereau->createur?->name ?? 'DSI / Service Approvisionnement' }}
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($bordereau->commentaire)
        <div class="observations-section">
            <div class="observations-title">Observations / Notes de réception :</div>
            <div class="observations-body">{{ $bordereau->commentaire }}</div>
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 20%;">Code Article</th>
                <th style="width: 50%;">Désignation</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 15%; text-align: right;">Qté Livrée</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bordereau->lignesLivraison as $l)
                <tr>
                    <td style="font-family: monospace;">{{ $l->article->code_article }}</td>
                    <td><strong>{{ $l->article->designation }}</strong></td>
                    <td>{{ config("achat.types_articles.{$l->article->type_article}", $l->article->type_article) }}</td>
                    <td class="text-right" style="font-weight: bold;">{{ $l->quantite_livree }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="signatures-table">
        <tr>
            <td style="padding-right: 15px;">
                <div class="signature-box">
                    <div class="signature-title">L'Agent de Réception (Signature)</div>
                    <div class="signature-line">
                        Réceptionné par: {{ $bordereau->createur?->name ?? 'DSI' }}<br>
                        Le: {{ $bordereau->created_at->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </td>
            <td style="padding-left: 15px;">
                <div class="signature-box">
                    <div class="signature-title">Le Livreur (Signature & Cachet)</div>
                    <div class="signature-line">Date: ____/____/________<br>Nom & Signature</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Bordereau de livraison généré par le portail CHU-YO. Réf. interne: {{ $bordereau->numero_livraison }}.
        <br>
        Page <span class="page-number"></span> / CHU-YO Achats - Bordereau de Livraison {{ $bordereau->numero_livraison }}
    </div>

</body>
</html>
