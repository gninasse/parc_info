{{-- Bon de transfert PDF (UX §5 / S9) — DOUBLE cadre de signature
     « Départ — magasinier source / transporteur » · « Arrivée — magasinier cible ». --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $transfert->numero }}</title>
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
        .signature { width: 100%; margin-top: 18px; border-collapse: collapse; }
        .signature td { width: 50%; vertical-align: top; padding: 4px; }
        .cadre-signature { border: 1px solid #111; height: 60px; padding: 4px; }
        .titre-cadre { font-weight: bold; font-size: 9px; text-transform: uppercase; margin-bottom: 2px; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="organisme">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
        <h1>Bon de transfert {{ $transfert->numero }}</h1>
    </div>

    <table class="meta">
        <tr>
            <td>
                <div class="libelle">Magasin source</div>
                {{ $transfert->magasinSource?->libelle }} ({{ $transfert->magasinSource?->code }})
            </td>
            <td>
                <div class="libelle">Magasin cible</div>
                {{ $transfert->magasinCible?->libelle }} ({{ $transfert->magasinCible?->code }})
            </td>
            <td>
                <div class="libelle">Transporté par</div>
                {{ $transfert->transporte_par_nom ?? '—' }}
                @if($transfert->transporteParEmploye) ({{ trim($transfert->transporteParEmploye->prenom.' '.$transfert->transporteParEmploye->nom) }}) @endif
            </td>
            <td>
                <div class="libelle">Daté du</div>
                {{ $transfert->date_document?->format('d/m/Y') }}
                <div class="libelle" style="margin-top:3px;">Validé le</div>
                {{ $transfert->valide_le?->format('d/m/Y H:i') }}
            </td>
        </tr>
    </table>

    @php
        $quantitatives = $transfert->lignes->filter(fn ($l) => $l->article?->nature !== 'equipement');
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
        {{-- Références complètes des unités transférées --}}
        <table class="donnees section-equipements">
            <thead>
                <tr><th>Code inventaire</th><th>Modèle</th><th>N° de série</th></tr>
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

    <table class="signature">
        <tr>
            <td>
                <div class="titre-cadre">Départ — magasinier source / transporteur</div>
                <div class="cadre-signature">
                    {{ $transfert->createur?->name }}
                    @if($transfert->transporte_par_nom) / {{ $transfert->transporte_par_nom }} @endif
                </div>
            </td>
            <td>
                <div class="titre-cadre">Arrivée — magasinier cible</div>
                <div class="cadre-signature"></div>
            </td>
        </tr>
    </table>

    @include('stock::pdf._cartouche', [
        'numero' => $transfert->numero,
        'creePar' => $transfert->createur?->name,
        'creeLe' => $transfert->created_at,
        'validePar' => $transfert->valideur?->name,
        'valideLe' => $transfert->valide_le,
    ])
</body>
</html>
