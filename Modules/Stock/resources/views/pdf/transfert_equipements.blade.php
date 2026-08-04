{{--
    Modèle 2 — BORDEREAU DE TRANSPORT : une ligne par unité déplacée, avec
    son code d'inventaire, son modèle, son numéro de série et son état.
    Sert de feuille de route au transporteur et de contrôle à l'arrivée.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $transfert->numero }} — équipements</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_transfert', [
        'titre' => 'Bordereau de transport —',
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
                <th style="width:96px;">Reçu</th>
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
                    {{-- Case cochée à l'arrivée, au contrôle physique --}}
                    <td class="centre">☐</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vide">Aucun équipement sur ce transfert.</td></tr>
            @endforelse
        </tbody>
    </table>

    <table class="meta">
        <tr>
            <td>
                <div class="libelle">Nombre d'unités transportées</div>
                <strong>{{ $unites->count() }}</strong>
            </td>
            <td>
                <div class="libelle">Trajet</div>
                {{ $transfert->magasinSource?->libelle }} → {{ $transfert->magasinCible?->libelle }}
            </td>
        </tr>
    </table>

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
