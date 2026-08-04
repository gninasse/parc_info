{{--
    Modèle 2 — FICHE DES ÉQUIPEMENTS RÉCEPTIONNÉS : une ligne par unité
    physique, avec son code d'inventaire, son modèle, son numéro de série et
    son état (saisis au référencement — D10). Sert de bordereau de
    vérification et d'étiquetage ; aucune donnée financière.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $entree->numero }} — équipements</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_entree', [
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
                <th style="width:80px;">État</th>
                <th style="width:96px;">Vérifié</th>
            </tr>
        </thead>
        <tbody>
            @forelse($unites as $unite)
                <tr>
                    <td class="centre">{{ $loop->iteration }}</td>
                    <td class="mono">{{ $unite['code_inventaire'] }}</td>
                    <td>{{ $unite['modele'] }}</td>
                    <td class="mono">{{ $unite['numero_serie'] }}</td>
                    <td>{{ ucfirst($unite['etat'] ?? '—') }}</td>
                    {{-- Case à cocher à la main lors du contrôle physique --}}
                    <td class="centre">☐</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vide">Aucun équipement sérialisé sur ce bon.</td></tr>
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
                <div class="libelle">Magasin de rattachement</div>
                {{ $entree->magasin?->libelle }} ({{ $entree->magasin?->code }})
            </td>
        </tr>
    </table>

    <table class="signature">
        <tr>
            <td>
                <div class="titre-cadre">Réceptionné par — magasinier</div>
                <div class="cadre-signature">{{ $entree->createur?->name }}</div>
            </td>
            <td>
                <div class="titre-cadre">Vérifié par</div>
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
