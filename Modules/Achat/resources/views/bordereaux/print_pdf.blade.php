<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau de livraison {{ $bordereau->numero_livraison }}</title>
    <style>
        @page { margin: 15mm 14mm 18mm 14mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; margin: 0; }

        .entete { width: 100%; border-bottom: 2px solid #0d2060; padding-bottom: 8px; margin-bottom: 14px; }
        .entete td { vertical-align: middle; }
        .etablissement { font-size: 14px; font-weight: bold; color: #0d2060; }
        .sous-titre { font-size: 9px; color: #6c757d; }
        .titre-document {
            text-align: right; font-size: 16px; font-weight: bold;
            color: #0d2060; text-transform: uppercase;
        }
        .numero { text-align: right; font-size: 12px; font-weight: bold; }

        .bloc { width: 100%; margin-bottom: 12px; }
        .bloc td { vertical-align: top; width: 50%; }
        .encadre { border: 1px solid #dee2e6; padding: 8px 10px; }
        .encadre-titre {
            font-size: 8px; text-transform: uppercase; letter-spacing: .5px;
            color: #6c757d; font-weight: bold; margin-bottom: 4px;
        }

        table.lignes { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.lignes th {
            background-color: #0d2060; color: #ffffff; font-size: 9px;
            text-transform: uppercase; padding: 6px 5px; text-align: left;
        }
        table.lignes td { padding: 5px; border-bottom: 1px solid #e9ecef; }
        table.lignes tr:nth-child(even) td { background-color: #f8f9fa; }
        .centre { text-align: center; }

        .recapitulatif {
            margin-top: 12px; border: 1px solid #dee2e6;
            padding: 8px 10px; background-color: #f8f9fa;
        }

        .signatures { width: 100%; margin-top: 28px; }
        .signatures td { width: 50%; padding-top: 6px; font-size: 9px; }
        .cadre-signature { border: 1px solid #dee2e6; height: 55px; margin-top: 4px; }

        .pied {
            position: fixed; bottom: -10mm; left: 0; right: 0;
            font-size: 7.5px; color: #6c757d;
            border-top: 1px solid #dee2e6; padding-top: 4px;
        }
        .reserve { margin-top: 10px; border-left: 3px solid #ffc107; padding: 6px 10px; background: #fff9e6; }
    </style>
</head>
<body>

<table class="entete">
    <tr>
        <td style="width: 60%;">
            <div class="etablissement">CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO</div>
            <div class="sous-titre">Direction des Systèmes d'Information &mdash; Magasin</div>
        </td>
        <td style="width: 40%;">
            <div class="titre-document">Bordereau de réception</div>
            <div class="numero">{{ $bordereau->numero_livraison }}</div>
        </td>
    </tr>
</table>

<table class="bloc">
    <tr>
        <td style="padding-right: 6px;">
            <div class="encadre">
                <div class="encadre-titre">Fournisseur</div>
                <div style="font-weight: bold; font-size: 11px;">
                    {{ $bordereau->bonCommande?->fournisseur?->nom }}
                </div>
                <div>Code : {{ $bordereau->bonCommande?->fournisseur?->code }}</div>
                <div style="margin-top: 6px;">
                    <strong>Bon de commande :</strong> {{ $bordereau->bonCommande?->numero_commande }}
                </div>
            </div>
        </td>
        <td style="padding-left: 6px;">
            <div class="encadre">
                <div class="encadre-titre">Réception</div>
                <div><strong>Date de livraison :</strong> {{ $bordereau->date_livraison?->format('d/m/Y') }}</div>
                <div><strong>Réf. bordereau fournisseur :</strong> {{ $bordereau->ref_bordereau_physique }}</div>
                <div><strong>Statut :</strong> {{ $bordereau->statut_label }}</div>
                <div><strong>Réceptionné par :</strong> {{ $bordereau->creator?->name ?? '—' }}</div>
            </div>
        </td>
    </tr>
</table>

<table class="lignes">
    <thead>
        <tr>
            <th style="width: 13%;">Code</th>
            <th style="width: 41%;">Désignation</th>
            <th style="width: 13%;" class="centre">Type</th>
            <th style="width: 11%;" class="centre">Commandé</th>
            <th style="width: 11%;" class="centre">Reçu</th>
            <th style="width: 11%;" class="centre">Refusé</th>
        </tr>
    </thead>
    <tbody>
        @php $totalRecu = 0; @endphp
        @forelse($bordereau->lignesLivraison as $ligne)
            @php
                $totalRecu += $ligne->quantite_livree;
                $ligneCommande = $bordereau->bonCommande?->lignesCommande
                    ->firstWhere('article_id', $ligne->article_id);
            @endphp
            <tr>
                <td style="font-size: 8.5px;">{{ $ligne->article?->code_article }}</td>
                <td><strong>{{ $ligne->article?->designation }}</strong></td>
                <td class="centre">{{ $ligne->article?->type_label }}</td>
                <td class="centre">{{ $ligneCommande?->quantite ?? '—' }}</td>
                <td class="centre" style="font-weight: bold;">{{ $ligne->quantite_livree }}</td>
                <td class="centre">{{ $ligne->quantite_refusee ?: '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="centre" style="padding: 12px;">Aucune ligne.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="recapitulatif">
    <strong>Total reçu :</strong> {{ $totalRecu }} unité(s)
    &nbsp;&nbsp;|&nbsp;&nbsp;
    <strong>Nombre de références :</strong> {{ $bordereau->lignesLivraison->count() }}
</div>

@foreach($bordereau->lignesLivraison->where('quantite_refusee', '>', 0) as $ligne)
    <div class="reserve">
        <strong>Réserve &mdash; {{ $ligne->article?->designation }} :</strong>
        {{ $ligne->quantite_refusee }} unité(s) refusée(s).
        {{ $ligne->motif_refus ? 'Motif : '.$ligne->motif_refus : '' }}
    </div>
@endforeach

@if($bordereau->commentaire)
    <div class="encadre" style="margin-top: 12px;">
        <div class="encadre-titre">Observations de réception</div>
        <div>{{ $bordereau->commentaire }}</div>
    </div>
@endif

<table class="signatures">
    <tr>
        <td style="padding-right: 8px;">
            <div>Le magasinier</div>
            <div class="cadre-signature"></div>
        </td>
        <td style="padding-left: 8px;">
            <div>Le livreur / transporteur</div>
            <div class="cadre-signature"></div>
        </td>
    </tr>
</table>

<div class="pied">
    Bordereau {{ $bordereau->numero_livraison }} &mdash;
    édité le {{ now()->format('d/m/Y à H:i') }} &mdash;
    CHU Yalgado Ouédraogo, système de gestion du parc informatique.
</div>

</body>
</html>
