{{--
    Modèle 1 — BON DE TRANSFERT (détail des lignes) : libellé de chaque
    ligne, quantités et unité. Couvre TOUTES les lignes (articles
    quantitatifs et lignes « modèle × N »).
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $transfert->numero }}</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_transfert', [
        'titre' => 'Bon de transfert',
        'sousTitre' => 'Détail des articles et quantités transférés',
    ])

    <table class="donnees">
        <thead>
            <tr>
                <th class="centre" style="width:34px;">Nature</th>
                <th>Article</th>
                <th class="num" style="width:70px;">Quantité</th>
                <th style="width:70px;">Unité</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transfert->lignes as $ligne)
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
                </tr>
            @empty
                <tr><td colspan="4" class="vide">Aucune ligne sur ce bon.</td></tr>
            @endforelse
        </tbody>
    </table>

    @if($transfert->observation)
        <div class="observation">
            <div class="libelle">Observation</div>
            <div>{{ $transfert->observation }}</div>
        </div>
    @endif

    @include('stock::pdf._signature_transfert')

    @include('stock::pdf._cartouche', [
        'numero' => $transfert->numero,
        'creePar' => $transfert->createur?->name,
        'creeLe' => $transfert->created_at,
        'validePar' => $transfert->valideur?->name,
        'valideLe' => $transfert->valide_le,
    ])
</body>
</html>
