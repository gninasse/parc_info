<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bon de Commande {{ $bonCommande->numero_commande }}</title>
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
            font-size: 20px;
            font-weight: 900;
            color: #1a73e8;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .bc-box {
            display: inline-block;
            border: 2px solid #1a73e8;
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
            border-top: 2px solid #1a73e8;
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
            background-color: #1a73e8;
            color: white;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            border: 1px solid #1a73e8;
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
        .totals-container {
            width: 100%;
            margin-bottom: 30px;
        }
        .totals-table {
            width: 280px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .totals-table td.label {
            color: #475569;
            text-align: right;
        }
        .totals-table td.value {
            font-weight: bold;
            color: #212529;
            text-align: right;
            font-family: monospace;
        }
        .totals-table tr.total-row td {
            border: 2px solid #1a73e8;
            background-color: #eff6ff;
            padding: 8px;
        }
        .totals-table tr.total-row td.label {
            color: #1e3a8a;
            font-weight: bold;
            font-size: 11px;
        }
        .totals-table tr.total-row td.value {
            color: #1a73e8;
            font-weight: 900;
            font-size: 12px;
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
                <div class="document-title">Bon de Commande</div>
                <div class="bc-box">
                    <table>
                        <tr>
                            <td class="label">N° Commande :</td>
                            <td class="value">{{ $bonCommande->numero_commande }}</td>
                        </tr>
                        <tr>
                            <td class="label">Date :</td>
                            <td class="value">{{ $bonCommande->date_commande->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label">Statut :</td>
                            <td class="value" style="color: {{ $bonCommande->statut === 'valide' ? '#16a34a' : ($bonCommande->statut === 'annule' ? '#dc2626' : '#4b5563') }};">
                                {{ strtoupper($bonCommande->statut) }}
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
                    <div class="info-card-title">Fournisseur</div>
                    <div class="info-card-text">
                        <strong>{{ $bonCommande->fournisseur->nom }}</strong><br>
                        Code: {{ $bonCommande->fournisseur->code }}<br>
                        Ville: {{ $bonCommande->fournisseur->ville }}<br>
                        Téléphone: {{ $bonCommande->fournisseur->telephone ?: '-' }}
                    </div>
                </div>
            </td>
            <td class="right-col">
                <div class="info-card">
                    <div class="info-card-title">Adresse de Livraison</div>
                    <div class="info-card-text">
                        <strong>CHU Yalgado Ouédraogo</strong><br>
                        Service Destinataire: Service des Achats & Approvisionnements<br>
                        Bâtiment: Direction Générale, Rez-de-chaussée<br>
                        Ouagadougou, Burkina Faso
                    </div>
                </div>
            </td>
        </tr>
    </table>

    @if($bonCommande->commentaire)
        <div class="observations-section">
            <div class="observations-title">Observations / Notes :</div>
            <div class="observations-body">{{ $bonCommande->commentaire }}</div>
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Code Article</th>
                <th style="width: 45%;">Désignation</th>
                <th style="width: 15%;">Catégorie</th>
                <th style="width: 10%; text-align: right;">Qté</th>
                <th style="width: 15%; text-align: right;">Prix Unit. HT</th>
                <th style="width: 15%; text-align: right;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bonCommande->lignesCommande as $l)
                <tr>
                    <td style="font-family: monospace;">{{ $l->article->code_article }}</td>
                    <td><strong>{{ $l->article->designation }}</strong></td>
                    <td>{{ config("achat.types_articles.{$l->article->type_article}", $l->article->type_article) }}</td>
                    <td class="text-right">{{ $l->quantite }}</td>
                    <td class="text-right" style="font-family: monospace;">{{ number_format($l->prix_unitaire, 0, ',', ' ') }}</td>
                    <td class="text-right" style="font-family: monospace;">{{ number_format($l->quantite * $l->prix_unitaire, 0, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <td class="label">Sous-total HT :</td>
                <td class="value">{{ number_format($bonCommande->montant_total, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr>
                <td class="label">TVA (18%) :</td>
                <td class="value">{{ number_format($bonCommande->montant_total * 0.18, 0, ',', ' ') }} FCFA</td>
            </tr>
            <tr class="total-row">
                <td class="label">Montant Total TTC :</td>
                <td class="value">{{ number_format($bonCommande->montant_total * 1.18, 0, ',', ' ') }} FCFA</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <table class="signatures-table">
        <tr>
            <td style="padding-right: 15px;">
                <div class="signature-box">
                    <div class="signature-title">L'Acheteur (Signature & Cachet)</div>
                    <div class="signature-line">
                        @if($bonCommande->validateur)
                            Validé par: {{ $bonCommande->validateur->name }}<br>
                            Le: {{ $bonCommande->date_validation ? $bonCommande->date_validation->format('d/m/Y à H:i') : '' }}
                        @else
                            &nbsp;
                        @endif
                    </div>
                </div>
            </td>
            <td style="padding-left: 15px;">
                <div class="signature-box">
                    <div class="signature-title">Le Fournisseur (Bon pour Accord)</div>
                    <div class="signature-line">Date: ____/____/________<br>Signature & Cachet</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Les conditions générales d'achat du CHU-YO s'appliquent à cette commande. Le paiement sera effectué à 30 jours fin de mois après réception conforme du matériel et de la facture.
        <br>
        Page <span class="page-number"></span> / CHU-YO Achats - Bon de Commande {{ $bonCommande->numero_commande }}
    </div>

</body>
</html>
