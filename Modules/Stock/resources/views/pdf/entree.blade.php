{{-- Bon d'entrée PDF (UX §3.4 / S9) — A4 portrait, lisible N&B laser (S7). --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $entree->numero }}</title>
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
        /* Hachures N&B (S7) pour distinguer les sections sans couleur */
        .section-equipements th { background: repeating-linear-gradient(45deg, #f2f2f2, #f2f2f2 3px, #ddd 3px, #ddd 6px); }
        .observation { border: 0.5px solid #555; padding: 6px; margin-bottom: 12px; }
        .signature { width: 100%; margin-top: 18px; }
        .signature td { width: 50%; vertical-align: top; padding: 4px; }
        .cadre-signature { border: 1px solid #111; height: 70px; padding: 4px; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
        <h1>Bon d'entrée {{ $entree->numero }}</h1>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="libelle">Magasin</div>
                {{ $entree->magasin?->libelle }} ({{ $entree->magasin?->code }})
            </td>
            <td>
                <div class="libelle">Nature</div>
                {{ $entree->nature === 'retour' ? 'Retour' : 'Livraison' }}
            </td>
            <td>
                <div class="libelle">Date de livraison</div>
                {{ $entree->date_document?->format('d/m/Y') }}
            </td>
            <td>
                <div class="libelle">Date de validation</div>
                {{ $entree->valide_le?->format('d/m/Y H:i') }}
            </td>
        </tr>
        <tr>
            <td>
                <div class="libelle">Fournisseur</div>
                {{ $entree->fournisseur?->raison_sociale ?? '—' }}
            </td>
            <td colspan="3">
                <div class="libelle">Référence externe</div>
                {{ $entree->reference_externe ?? '—' }}
            </td>
        </tr>
    </table>

    @php
        $quantitatives = $entree->lignes->filter(fn ($l) => $l->article_id !== null && $l->article?->nature !== 'equipement');
    @endphp

    @if($quantitatives->isNotEmpty())
        <table class="donnees">
            <thead>
                <tr>
                    <th>Article</th>
                    <th class="num">Quantité</th>
                    <th>Unité</th>
                    <th class="num">Coût unitaire (FCFA)</th>
                    <th class="num">Sous-total (FCFA)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quantitatives as $ligne)
                    <tr>
                        <td><span class="mono">{{ $ligne->article->code }}</span> — {{ $ligne->article->nom }}</td>
                        <td class="num">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                        <td>{{ $ligne->article->unite_stock }}</td>
                        <td class="num">{{ $ligne->cout_unitaire !== null ? number_format($ligne->cout_unitaire, 0, ',', ' ') : '—' }}</td>
                        <td class="num">{{ $ligne->cout_unitaire !== null ? number_format($ligne->quantite * $ligne->cout_unitaire, 0, ',', ' ') : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($unites->isNotEmpty())
        <table class="donnees section-equipements">
            <thead>
                <tr>
                    <th>Code inventaire</th>
                    <th>Modèle</th>
                    <th>N° de série</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unites as $unite)
                    <tr>
                        <td class="mono">{{ $unite['code_inventaire'] }}</td>
                        <td>{{ $unite['modele'] }}</td>
                        <td class="mono">{{ $unite['numero_serie'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($entree->observation_type || $entree->observation)
        <div class="observation">
            <div class="libelle">Observation</div>
            @if($entree->observation_type)
                <strong>{{ config('stock.motifs_observation_entree')[$entree->observation_type] ?? $entree->observation_type }}</strong>
            @endif
            @if($entree->observation)
                <div>{{ $entree->observation }}</div>
            @endif
        </div>
    @endif

    <table class="signature">
        <tr>
            <td>
                <div class="libelle">Signature du livreur</div>
                <div class="cadre-signature"></div>
            </td>
            <td>
                <div class="libelle">Signature du magasinier</div>
                <div class="cadre-signature"></div>
            </td>
        </tr>
    </table>

    @include('stock::pdf._cartouche', [
        'numero' => $entree->numero,
        'creePar' => $entree->createur?->name,
        'creeLe' => $entree->created_at,
        'validePar' => $entree->valideur?->name,
        'valideLe' => $entree->valide_le,
    ])
</body>
</html>
