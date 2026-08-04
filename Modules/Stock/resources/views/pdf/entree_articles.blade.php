{{--
    Modèle 1 — BON DE RÉCEPTION (valorisation) : libellé de chaque ligne,
    quantités, coût unitaire, sous-totaux et total général. Couvre TOUTES les
    lignes du bon (articles quantitatifs, lignes « modèle × N » et unités
    rattachées) pour que le total corresponde au bon complet.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $entree->numero }}</title>
    @include('stock::pdf._styles_bon')
</head>
<body>
    @include('stock::pdf._entete_entree', [
        'titre' => 'Bon d\'entrée',
        'sousTitre' => 'Détail des articles, quantités et valorisation',
    ])

    @php
        $totalGeneral = 0;
    @endphp

    <table class="donnees">
        <thead>
            <tr>
                <th class="centre" style="width:34px;">Nature</th>
                <th>Article / Unité</th>
                <th class="num" style="width:64px;">Quantité</th>
                <th style="width:64px;">Unité</th>
                <th class="num" style="width:88px;">Coût unitaire</th>
                <th class="num" style="width:96px;">Sous-total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entree->lignes as $ligne)
                @php
                    $estRattachement = $ligne->equipement_id !== null;
                    $sousTotal = (float) $ligne->quantite * (float) ($ligne->cout_unitaire ?? 0);
                    $totalGeneral += $sousTotal;
                    $nature = $estRattachement ? 'E' : strtoupper(substr($ligne->article->nature, 0, 1));
                @endphp
                <tr>
                    <td class="centre">{{ $nature }}</td>
                    <td>
                        @if($estRattachement)
                            <span class="mono">{{ $ligne->equipement?->code_inventaire }}</span> — {{ $ligne->equipement?->modele }}
                            <div class="libelle">Rattachement d'une unité existante</div>
                        @else
                            <span class="mono">{{ $ligne->article->code }}</span> — {{ $ligne->article->nom }}
                            @if($ligne->article->nature === 'equipement')
                                <div class="libelle">Modèle × {{ (int) $ligne->quantite }} — numéros de série au modèle « Fiche des équipements »</div>
                            @endif
                        @endif
                    </td>
                    <td class="num">{{ rtrim(rtrim(number_format($ligne->quantite, 2, ',', ' '), '0'), ',') }}</td>
                    <td>{{ $estRattachement ? 'unité' : $ligne->article->unite_stock }}</td>
                    <td class="num">{{ $ligne->cout_unitaire !== null ? number_format($ligne->cout_unitaire, 0, ',', ' ') : '—' }}</td>
                    <td class="num">{{ $ligne->cout_unitaire !== null ? number_format($sousTotal, 0, ',', ' ') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vide">Aucune ligne sur ce bon.</td></tr>
            @endforelse
        </tbody>
        @if($entree->lignes->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="5" class="num">Total général (FCFA)</td>
                    <td class="num">{{ number_format($totalGeneral, 0, ',', ' ') }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

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
                <div class="titre-cadre">Signature du livreur</div>
                <div class="cadre-signature"></div>
            </td>
            <td>
                <div class="titre-cadre">Signature du magasinier</div>
                <div class="cadre-signature">{{ $entree->createur?->name }}</div>
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
