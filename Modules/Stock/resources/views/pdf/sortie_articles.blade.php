{{--
    Modèle 1 — BON DE SORTIE (détail des lignes) : libellé de chaque ligne,
    quantités, unité, emplacement de destination. Couvre TOUTES les lignes
    (articles quantitatifs et lignes « modèle × N »).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $sortie->numero }}</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_sortie', [
        'titre' => 'Bon de sortie',
        'sousTitre' => 'Détail des articles et quantités remis',
    ])

    <table class="donnees">
        <thead>
            <tr>
                <th class="centre" style="width:34px;">Nature</th>
                <th>Article</th>
                <th class="num" style="width:70px;">Quantité</th>
                <th style="width:70px;">Unité</th>
                <th style="width:150px;">Emplacement</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sortie->lignes as $ligne)
                <tr>
                    <td class="centre">{{ strtoupper(substr($ligne->article->nature, 0, 1)) }}</td>
                    <td>
                        <span class="mono">{{ $ligne->article->code }}</span> — {{ $ligne->article->nom }}
                        @if($ligne->article->nature === 'equipement')
                            <div class="libelle">Modèle × {{ (int) $ligne->quantite }} — numéros de série au modèle « Fiche des équipements »</div>
                        @endif
                    </td>
                    <td class="num">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                    <td>{{ $ligne->article->unite_stock }}</td>
                    <td>{{ $ligne->emplacementLocal?->libelle ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="vide">Aucune ligne sur ce bon.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($sortie->observation)
        <div class="observation">
            <div class="libelle">Observation</div>
            <div>{{ $sortie->observation }}</div>
        </div>
    @endif

    <table class="signature">
        <tr>
            <td>
                <div class="titre-cadre">Magasinier</div>
                <div class="cadre-signature">{{ $sortie->createur?->name }}</div>
            </td>
            <td>
                {{-- Cadre signature du PORTEUR (UX §4.4) --}}
                <div class="titre-cadre">Signature du porteur — {{ $sortie->remis_a_nom ?? 'bénéficiaire' }}</div>
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
