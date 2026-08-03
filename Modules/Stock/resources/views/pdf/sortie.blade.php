{{-- Bon de sortie PDF (UX §4.4 / S9) — cadre signature du porteur (S7 : N&B). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $sortie->numero }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        .entete { border-bottom: 2px solid #111; padding-bottom: 6px; margin-bottom: 10px; }
        .entete h1 { font-size: 15px; margin: 0; }
        .entete .organisme { font-size: 11px; font-weight: bold; }
        .meta { width: 100%; margin-bottom: 10px; }
        .meta td { padding: 2px 6px 2px 0; vertical-align: top; }
        .libelle { color: #444; text-transform: uppercase; font-size: 8px; }
        table.donnees { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.donnees th, table.donnees td { border: 0.5px solid #555; padding: 3px 5px; }
        table.donnees th { background: #e8e8e8; text-align: left; text-transform: uppercase; font-size: 8.5px; }
        .num { text-align: right; }
        .mono { font-family: DejaVu Sans Mono, monospace; }
        .section-equipements th { background: repeating-linear-gradient(45deg, #f2f2f2, #f2f2f2 3px, #ddd 3px, #ddd 6px); }
        .signature { width: 100%; margin-top: 18px; }
        .signature td { width: 50%; vertical-align: top; padding: 4px; }
        .cadre-signature { border: 1px solid #111; height: 70px; padding: 4px; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
        <h1>Bon de sortie {{ $sortie->numero }}</h1>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="libelle">Magasin</div>
                {{ $sortie->magasin?->libelle }} ({{ $sortie->magasin?->code }})
            </td>
            <td>
                <div class="libelle">Bénéficiaire</div>
                {{ $sortie->beneficiaire_libelle ?? $sortie->beneficiaire_type }}
            </td>
            <td>
                <div class="libelle">Remis à</div>
                {{ $sortie->remis_a_nom ?? '—' }}
                @if($sortie->remisAEmploye) ({{ trim($sortie->remisAEmploye->prenom.' '.$sortie->remisAEmploye->nom) }}) @endif
            </td>
        </tr>
        <tr>
            <td>
                <div class="libelle">Motif</div>
                {{ config('stock.motifs_sortie')[$sortie->motif_type] ?? $sortie->motif_type }}
                @if($sortie->motif_texte) — {{ $sortie->motif_texte }} @endif
            </td>
            <td>
                <div class="libelle">Date du bon</div>
                {{ $sortie->date_document?->format('d/m/Y') }}
                @if($sortie->remise_reelle_le)
                    <div class="libelle" style="margin-top:3px;">Remise réelle</div>
                    {{ $sortie->remise_reelle_le->format('d/m/Y H:i') }}
                @endif
            </td>
            <td>
                <div class="libelle">Validé le</div>
                {{ $sortie->valide_le?->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    @php
        $quantitatives = $sortie->lignes->filter(fn ($l) => $l->article?->nature !== 'equipement');
    @endphp

    @if($quantitatives->isNotEmpty())
        <table class="donnees">
            <thead>
                <tr><th>Article</th><th class="num">Quantité</th><th>Unité</th></tr>
            </thead>
            <tbody>
                @foreach($quantitatives as $ligne)
                    <tr>
                        <td><span class="mono">{{ $ligne->article->code }}</span> — {{ $ligne->article->nom }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                        <td>{{ $ligne->article->unite_stock }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($unites->isNotEmpty())
        <table class="donnees section-equipements">
            <thead>
                <tr><th>Code inventaire</th><th>Modèle</th><th>N° de série</th><th>Affectation</th></tr>
            </thead>
            <tbody>
                @foreach($unites as $unite)
                    <tr>
                        <td class="mono">{{ $unite['code_inventaire'] }}</td>
                        <td>{{ $unite['modele'] }}</td>
                        <td class="mono">{{ $unite['numero_serie'] }}</td>
                        <td class="mono">{{ $unite['affectation_code'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="signature">
        <tr>
            <td>
                <div class="libelle">Magasinier</div>
                <div class="cadre-signature">{{ $sortie->createur?->name }}</div>
            </td>
            <td>
                {{-- Cadre signature du PORTEUR (UX §4.4) --}}
                <div class="libelle">Signature du porteur — {{ $sortie->remis_a_nom ?? 'bénéficiaire' }}</div>
                <div class="cadre-signature"></div>
            </td>
        </tr>
    </table>

    @include('stock::pdf._cartouche', [
        'numero' => $sortie->numero,
        'creePar' => $sortie->createur?->name,
        'creeLe' => $sortie->created_at,
        'validePar' => $sortie->valideur?->name,
        'valideLe' => $sortie->valide_le,
    ])
</body>
</html>
