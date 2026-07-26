<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de commande {{ $bonCommande->numero_commande }}</title>
    <style>
        @page { margin: 15mm 14mm 18mm 14mm; }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #212529;
            margin: 0;
        }

        .entete { width: 100%; border-bottom: 2px solid #0d2060; padding-bottom: 8px; margin-bottom: 14px; }
        .entete td { vertical-align: middle; }
        .etablissement { font-size: 14px; font-weight: bold; color: #0d2060; }
        .sous-titre { font-size: 9px; color: #6c757d; }

        .titre-document {
            text-align: right;
            font-size: 16px;
            font-weight: bold;
            color: #0d2060;
            text-transform: uppercase;
        }
        .numero { text-align: right; font-size: 12px; font-weight: bold; }

        .bloc { width: 100%; margin-bottom: 12px; }
        .bloc td { vertical-align: top; width: 50%; }

        .encadre {
            border: 1px solid #dee2e6;
            padding: 8px 10px;
        }
        .encadre-titre {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            font-weight: bold;
            margin-bottom: 4px;
        }

        table.lignes { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lignes th {
            background-color: #0d2060;
            color: #ffffff;
            font-size: 9px;
            text-transform: uppercase;
            padding: 6px 5px;
            text-align: left;
        }
        table.lignes td { padding: 5px; border-bottom: 1px solid #e9ecef; }
        table.lignes tr:nth-child(even) td { background-color: #f8f9fa; }

        .num { text-align: right; }
        .centre { text-align: center; }

        table.totaux { width: 45%; margin-left: 55%; margin-top: 10px; border-collapse: collapse; }
        table.totaux td { padding: 4px 6px; }
        table.totaux .libelle { color: #6c757d; }
        table.totaux .valeur { text-align: right; font-weight: bold; }
        table.totaux tr.ttc td {
            border-top: 2px solid #0d2060;
            font-size: 12px;
            color: #0d2060;
            padding-top: 6px;
        }

        .signatures { width: 100%; margin-top: 28px; }
        .signatures td { width: 33%; padding-top: 6px; font-size: 9px; }
        .cadre-signature {
            border: 1px solid #dee2e6;
            height: 55px;
            margin-top: 4px;
        }

        .pied {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            font-size: 7.5px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 4px;
        }
        .mention { font-size: 8px; color: #6c757d; margin-top: 10px; font-style: italic; }
    </style>
</head>
<body>

<table class="entete">
    <tr>
        <td style="width: 60%;">
            <div class="etablissement">CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO</div>
            <div class="sous-titre">Direction des Systèmes d'Information &mdash; Service Approvisionnement</div>
        </td>
        <td style="width: 40%;">
            <div class="titre-document">Bon de commande</div>
            <div class="numero">{{ $bonCommande->numero_commande }}</div>
        </td>
    </tr>
</table>

<table class="bloc">
    <tr>
        <td style="padding-right: 6px;">
            <div class="encadre">
                <div class="encadre-titre">Fournisseur</div>
                <div style="font-weight: bold; font-size: 11px;">{{ $bonCommande->fournisseur?->nom }}</div>
                <div>Code : {{ $bonCommande->fournisseur?->code }}</div>
                @if($bonCommande->fournisseur?->adresse)
                    <div>{{ $bonCommande->fournisseur->adresse }}</div>
                @endif
                @if($bonCommande->fournisseur?->ville)
                    <div>{{ $bonCommande->fournisseur->ville }}</div>
                @endif
                @if($bonCommande->fournisseur?->telephone)
                    <div>Tél. : {{ $bonCommande->fournisseur->telephone }}</div>
                @endif
            </div>
        </td>
        <td style="padding-left: 6px;">
            <div class="encadre">
                <div class="encadre-titre">Références</div>
                <div><strong>Date de commande :</strong> {{ $bonCommande->date_commande?->format('d/m/Y') }}</div>
                <div><strong>Statut :</strong> {{ $bonCommande->statut_label }}</div>
                @if($bonCommande->date_validation)
                    <div><strong>Validé le :</strong> {{ $bonCommande->date_validation->format('d/m/Y') }}</div>
                    <div><strong>Validé par :</strong> {{ $bonCommande->validateur?->name ?? '—' }}</div>
                @endif
            </div>
        </td>
    </tr>
</table>

<table class="lignes">
    <thead>
        <tr>
            <th style="width: 11%;">Code</th>
            <th style="width: 37%;">Désignation</th>
            <th style="width: 8%;" class="centre">Unité</th>
            <th style="width: 7%;" class="centre">Qté</th>
            <th style="width: 13%;" class="num">P.U. HT</th>
            <th style="width: 8%;" class="centre">TVA</th>
            <th style="width: 16%;" class="num">Montant HT</th>
        </tr>
    </thead>
    <tbody>
        @forelse($bonCommande->lignesCommande as $ligne)
            <tr>
                <td style="font-size: 8.5px;">{{ $ligne->article?->code_article }}</td>
                <td>
                    <strong>{{ $ligne->article?->designation }}</strong>
                    @if($ligne->article?->reference_constructeur)
                        <div style="font-size: 8px; color: #6c757d;">
                            Réf. : {{ $ligne->article->reference_constructeur }}
                        </div>
                    @endif
                </td>
                <td class="centre">{{ $ligne->article?->unite_mesure }}</td>
                <td class="centre">{{ $ligne->quantite }}</td>
                <td class="num">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                <td class="centre">{{ rtrim(rtrim(number_format($ligne->taux_tva, 2, ',', ''), '0'), ',') }} %</td>
                <td class="num">{{ number_format($ligne->montant_ht, 0, ',', ' ') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="centre" style="padding: 12px;">Aucune ligne.</td></tr>
        @endforelse
    </tbody>
</table>

<table class="totaux">
    <tr>
        <td class="libelle">Total HT</td>
        <td class="valeur">{{ number_format($bonCommande->montant_ht, 0, ',', ' ') }} FCFA</td>
    </tr>
    <tr>
        <td class="libelle">TVA</td>
        <td class="valeur">{{ number_format($bonCommande->montant_tva, 0, ',', ' ') }} FCFA</td>
    </tr>
    <tr class="ttc">
        <td class="libelle" style="color: #0d2060; font-weight: bold;">Montant TTC</td>
        <td class="valeur">{{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} FCFA</td>
    </tr>
</table>

@if($bonCommande->commentaire)
    <div class="encadre" style="margin-top: 14px;">
        <div class="encadre-titre">Observations</div>
        <div>{{ $bonCommande->commentaire }}</div>
    </div>
@endif

<div class="mention">
    Arrêté le présent bon de commande à la somme de
    {{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} francs CFA toutes taxes comprises.
</div>

<table class="signatures">
    <tr>
        <td style="padding-right: 8px;">
            <div>Le service approvisionnement</div>
            <div class="cadre-signature"></div>
        </td>
        <td style="padding: 0 4px;">
            <div>Le responsable habilité</div>
            <div class="cadre-signature"></div>
        </td>
        <td style="padding-left: 8px;">
            <div>Le fournisseur (bon pour accord)</div>
            <div class="cadre-signature"></div>
        </td>
    </tr>
</table>

<div class="pied">
    Bon de commande {{ $bonCommande->numero_commande }} &mdash;
    édité le {{ now()->format('d/m/Y à H:i') }} &mdash;
    CHU Yalgado Ouédraogo, système de gestion du parc informatique.
</div>

</body>
</html>
