{{--
    Modèle 2 — FICHE DES ÉQUIPEMENTS REMIS : une ligne par unité sortie, avec
    son code d'inventaire, son modèle, son numéro de série et l'affectation
    ParcInfo créée (D8). Sert de décharge signée par le porteur.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $sortie->numero }} — équipements</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_sortie', [
        'titre' => 'Fiche des équipements —',
        'sousTitre' => 'Codes d\'inventaire, modèles et numéros de série',
    ])

    <table class="donnees section-equipements">
        <thead>
            <tr>
                <th class="centre" style="width:34px;">#</th>
                <th style="width:130px;">Code inventaire</th>
                <th>Modèle</th>
                <th style="width:150px;">N° de série</th>
                <th style="width:110px;">Affectation</th>
                <th style="width:70px;">Remis</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unites as $unite)
                <tr>
                    <td class="centre">{{ $loop->iteration }}</td>
                    <td class="mono">{{ $unite['code_inventaire'] }}</td>
                    <td>{{ $unite['modele'] }}</td>
                    <td class="mono">{{ $unite['numero_serie'] }}</td>
                    <td class="mono">{{ $unite['affectation_code'] ?? '—' }}</td>
                    {{-- Case cochée à la main à la remise physique --}}
                    <td class="centre">☐</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vide">Aucun équipement sur ce bon.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="libelle">Nombre d'unités</div>
                <strong>{{ $unites->count() }}</strong>
            </td>
            <td>
                <div class="libelle">Bénéficiaire</div>
                {{ $sortie->beneficiaire_libelle ?? $sortie->beneficiaire_type }}
            </td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td>
                <div class="titre-cadre">Remis par — magasinier</div>
                <div class="cadre-signature">{{ $sortie->createur?->name }}</div>
            </td>
            <td>
                <div class="titre-cadre">Reçu par — {{ $sortie->remis_a_nom ?? 'bénéficiaire' }}</div>
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
