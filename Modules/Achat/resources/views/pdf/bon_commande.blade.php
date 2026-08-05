{{--
    Gabarit PDF du bon de commande (SPEC_UX §17, LIV-04).

    « Le logiciel produit le papier » (CTR-07) : ce document est l'objet
    juridique signé. Il n'effectue AUCUN calcul : les montants viennent de la
    base, dénormalisés à la validation (IA-1) — le gabarit affiche, il ne
    recompte pas.

    Reçoit : $bon, $filigrane (?string), $qrSvg (?string), $decomposition,
    $montantEnLettres.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $bon->numero_affiche }}</title>
    <style>
        @page { margin: 15mm 15mm 22mm 15mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }

        /* En-tête bandeau */
        .entete { border-bottom: 2px solid #111; padding-bottom: 8px; margin-bottom: 10px; }
        .entete table { width: 100%; border-collapse: collapse; }
        .entete .organisme { font-size: 12px; font-weight: bold; }
        .entete h1 { font-size: 15px; margin: 2px 0 0 0; }
        .entete .numero { font-size: 17px; font-weight: bold; font-family: DejaVu Sans Mono, monospace; }
        /* 22 × 22 mm ≈ 83 px à 96 dpi (SPEC_UX §17) */
        .qr { width: 83px; height: 83px; }

        /* Parties et références */
        table.blocs { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.blocs td { width: 50%; vertical-align: top; padding: 4px 8px 4px 0; }
        .cadre { border: 0.5px solid #555; padding: 6px; }
        .libelle { color: #444; text-transform: uppercase; font-size: 8px; }

        /* Tableau des lignes : thead répété à chaque page par dompdf */
        table.donnees { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.donnees th, table.donnees td { border: 0.5px solid #555; padding: 3px 5px; }
        table.donnees th { background: #e8e8e8; text-align: left; text-transform: uppercase; font-size: 8.5px; }
        table.donnees thead { display: table-header-group; }
        .num { text-align: right; }
        .centre { text-align: center; }
        .mono { font-family: DejaVu Sans Mono, monospace; }

        /* Totaux encadrés */
        table.totaux { width: 45%; margin-left: 55%; border-collapse: collapse; margin-bottom: 6px; }
        table.totaux td { border: 0.5px solid #555; padding: 4px 6px; }
        table.totaux .grand-total td { font-weight: bold; font-size: 12px; background: #f2f2f2; }
        .en-lettres { border: 0.5px solid #555; padding: 6px; margin-bottom: 14px; font-style: italic; }

        /* Signatures : un seul cadre validateur (D3) */
        table.signature { width: 100%; margin-top: 16px; border-collapse: collapse; }
        table.signature td { width: 50%; vertical-align: top; padding: 4px; }
        .cadre-signature { border: 1px solid #111; height: 80px; padding: 5px; }
        .titre-cadre { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-bottom: 3px; }

        /* Filigrane : diagonale, gris 15 % (SPEC_UX §17) */
        .filigrane {
            position: fixed;
            top: 40%;
            left: 0;
            width: 100%;
            text-align: center;
            transform: rotate(-30deg);
            font-size: 44px;
            font-weight: bold;
            color: rgba(0, 0, 0, 0.15);
            z-index: -1;
        }

        /* Pied paginé (script dompdf ci-dessous) */
        .pied {
            position: fixed;
            bottom: -12mm;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 8px;
            color: #444;
            border-top: 0.5px solid #999;
            padding-top: 3px;
        }
    </style>
</head>
<body>

@if($filigrane)
    <div class="filigrane">{{ $filigrane }}</div>
@endif

{{-- ── En-tête bandeau ─────────────────────────────────────────────────── --}}
<div class="entete">
    <table>
        <tr>
            <td style="width:60%;">
                <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
                <h1>Bon de commande</h1>
                <div class="numero">{{ $bon->numero ?? $bon->numero_affiche }}</div>
            </td>
            <td style="width:40%; text-align:right;">
                @if($qrSvg)
                    <img class="qr" src="{{ $qrSvg }}" alt="QR code du numéro {{ $bon->numero }}">
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ── Parties ─────────────────────────────────────────────────────────── --}}
<table class="blocs">
    <tr>
        <td>
            <div class="cadre">
                <div class="libelle">Émetteur</div>
                <strong>CHU-YO — Service approvisionnement</strong><br>
                Acheteur : {{ $bon->createur?->name ?? '—' }}
            </div>
        </td>
        <td>
            <div class="cadre">
                <div class="libelle">Fournisseur</div>
                {{-- Libellé FIGÉ à la validation : le document dit ce qui a
                     été engagé, même si le référentiel a changé depuis. --}}
                <strong>{{ $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale ?? '—' }}</strong><br>
                @if($bon->fournisseur?->telephone) Tél. : {{ $bon->fournisseur->telephone }}<br> @endif
                @if($bon->fournisseur?->email) {{ $bon->fournisseur->email }} @endif
            </div>
        </td>
    </tr>
</table>

{{-- ── Références ──────────────────────────────────────────────────────── --}}
<table class="blocs">
    <tr>
        <td>
            <span class="libelle">Date du document :</span> {{ $bon->date_document?->format('d/m/Y') ?? '—' }}<br>
            @if($bon->valide_le)
                <span class="libelle">Validé le :</span> {{ $bon->valide_le->format('d/m/Y') }}
                par {{ $bon->validateur?->name ?? '—' }}
            @endif
        </td>
        <td>
            @if($bon->service_demandeur_libelle || $bon->serviceDemandeur)
                <span class="libelle">Service demandeur :</span>
                {{ $bon->service_demandeur_libelle ?? $bon->serviceDemandeur?->libelle }}<br>
            @endif
            @if($bon->reference_demande)
                <span class="libelle">Réf. demande :</span> {{ $bon->reference_demande }}<br>
            @endif
            @if($bon->observation_type || $bon->observation_texte)
                <span class="libelle">Observation :</span>
                {{ trim(($bon->observation_type ? $bon->observation_type.' — ' : '').($bon->observation_texte ?? '')) }}
            @endif
        </td>
    </tr>
</table>

{{-- ── Tableau des lignes ──────────────────────────────────────────────── --}}
<table class="donnees">
    <thead>
        <tr>
            <th class="centre" style="width:24px;">N°</th>
            <th style="width:80px;">Code</th>
            <th>Désignation</th>
            <th class="num" style="width:48px;">Qté</th>
            <th class="num" style="width:80px;">PU HT</th>
            <th class="num" style="width:44px;">TVA %</th>
            <th class="num" style="width:92px;">Montant HT</th>
        </tr>
    </thead>
    <tbody>
        @foreach($bon->lignes as $ligne)
            <tr>
                <td class="centre">{{ $loop->iteration }}</td>
                <td class="mono">{{ $ligne->article?->code ?? '—' }}</td>
                <td>{{ $ligne->designation }}</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                <td class="num">{{ number_format((float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}</td>
                <td class="num">{{ rtrim(rtrim(number_format((float) $ligne->taux_tva, 2, ',', ' '), '0'), ',') }}</td>
                <td class="num">{{ number_format((float) $ligne->quantite * (float) $ligne->prix_unitaire_ht, 0, ',', ' ') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totaux (les montants de la BASE — IA-1) ─────────────────────────── --}}
<table class="totaux">
    <tr>
        <td>Total HT</td>
        <td class="num">{{ number_format((float) $bon->montant_ht, 0, ',', ' ') }} FCFA</td>
    </tr>
    @foreach($decomposition as $tranche)
        <tr>
            <td>TVA {{ rtrim(rtrim(number_format($tranche['taux'], 2, ',', ' '), '0'), ',') }} %</td>
            <td class="num">{{ number_format($tranche['tva'], 0, ',', ' ') }} FCFA</td>
        </tr>
    @endforeach
    <tr class="grand-total">
        <td>Total TTC</td>
        <td class="num">{{ number_format((float) $bon->montant_ttc, 0, ',', ' ') }} FCFA</td>
    </tr>
</table>

<div class="en-lettres">
    Arrêté le présent bon de commande à la somme de : <strong>{{ $montantEnLettres }}</strong> (TTC).
</div>

{{-- ── Signatures : un seul cadre validateur (D3) ──────────────────────── --}}
<table class="signature">
    <tr>
        <td>
            <div class="cadre-signature">
                <div class="titre-cadre">L'Acheteur</div>
                {{ $bon->createur?->name ?? '—' }}<br>
                <span class="libelle">Date : {{ $bon->soumis_le?->format('d/m/Y') ?? '________' }}</span>
            </div>
        </td>
        <td>
            <div class="cadre-signature">
                <div class="titre-cadre">Le Validateur</div>
                {{ $bon->validateur?->name ?? '—' }}<br>
                <span class="libelle">Date : {{ $bon->valide_le?->format('d/m/Y') ?? '________' }}</span>
            </div>
        </td>
    </tr>
</table>

{{-- ── Pied paginé ─────────────────────────────────────────────────────── --}}
<div class="pied">
    {{ $bon->numero ?? $bon->numero_affiche }} ·
    édité le {{ now()->format('d/m/Y à H\hi') }} par {{ auth()->user()?->name ?? '—' }} ·
    CHU-YO Module Achat · <span class="numero-page"></span>
</div>

<script type="text/php">
    // Pagination « page X/Y » : seul dompdf connaît le nombre final de pages.
    if (isset($pdf)) {
        $pdf->page_text(
            297, 823,
            "page {PAGE_NUM}/{PAGE_COUNT}",
            null, 8, [0.27, 0.27, 0.27]
        );
    }
</script>

</body>
</html>
