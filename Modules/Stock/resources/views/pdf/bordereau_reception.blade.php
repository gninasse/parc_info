{{--
    BR-02 — Bordereau de réception (PDF A4).

    Document de QUAI : il se signe devant le livreur. Les montants en sont
    absents par défaut (stock.afficher_couts_bordereau) — le livreur n'a pas
    à lire les prix négociés par l'établissement.

    Deux cadres de signature : le magasinier (nom pré-imprimé, il est connu)
    et le livreur (à remplir, il ne l'est pas). Sans cette contradiction, une
    réclamation ultérieure ne pèse rien.
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bordereau de réception {{ $entree->numero }}</title>
    <style>
        @page { margin: 14mm 12mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #212529; }

        .entete { width: 100%; border-bottom: 1.2px solid #212529; padding-bottom: 3mm; margin-bottom: 4mm; }
        .entete td { vertical-align: top; }
        .titre { font-size: 17px; font-weight: bold; margin: 0; }
        .numero { font-size: 13px; font-family: DejaVu Sans Mono, monospace; margin-top: 1mm; }
        .etablissement { font-size: 11px; font-weight: bold; }

        .references { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
        .references td { border: 0.4px solid #adb5bd; padding: 1.8mm 2.5mm; }
        .references .cle { background: #f1f3f5; font-size: 8px; text-transform: uppercase; width: 26mm; }

        h2 { font-size: 11px; margin: 4mm 0 1.5mm; }
        table.lignes { width: 100%; border-collapse: collapse; }
        table.lignes th, table.lignes td { border: 0.4px solid #adb5bd; padding: 1.6mm 2mm; }
        table.lignes th { background: #e9ecef; font-size: 8px; text-transform: uppercase; text-align: left; }
        td.nombre { text-align: right; }

        .observation { border: 0.4px solid #adb5bd; padding: 2.5mm; margin-top: 4mm; min-height: 12mm; }
        .observation .type { font-weight: bold; }

        .signatures { width: 100%; margin-top: 8mm; border-collapse: collapse; }
        .signatures td { width: 50%; border: 0.4px solid #212529; height: 30mm; vertical-align: top; padding: 2mm; }
        .signatures .role { font-size: 8px; text-transform: uppercase; color: #495057; }
        .signatures .nom { font-size: 10px; font-weight: bold; margin-top: 1mm; }
        .signatures .mention { font-size: 7px; color: #6c757d; margin-top: 1mm; }

        .filigrane {
            position: fixed; top: 42%; left: 8%;
            font-size: 52px; color: rgba(220, 53, 69, .13);
            transform: rotate(-28deg); font-weight: bold;
        }

        .pied { position: fixed; bottom: -12mm; left: 0; right: 0; font-size: 7px; color: #6c757d; text-align: center; }
        .bl { font-size: 8px; color: #495057; margin-top: 1.5mm; }
    </style>
</head>
<body>

@if($contrePassation)
    {{-- Le document qui circule DOIT dire qu'il a été partiellement défait. --}}
    <div class="filigrane">CONTRE-PASSÉ PARTIELLEMENT</div>
@endif

<table class="entete">
    <tr>
        <td style="width:62%;">
            <div class="etablissement">CHU-YO — Centre Hospitalier Universitaire Yalgado Ouédraogo</div>
            <p class="titre">Bordereau de réception</p>
            <div class="numero">N° {{ $entree->numero }}</div>
        </td>
        <td style="width:38%; text-align:right;">
            @if($qr)
                <img src="{{ $qr }}" alt="QR du numéro {{ $entree->numero }}" style="width:26mm; height:26mm;">
            @endif
        </td>
    </tr>
</table>

<table class="references">
    <tr>
        <td class="cle">Date de livraison</td>
        <td>{{ $entree->date_document?->format('d/m/Y') ?? '—' }}</td>
        <td class="cle">Magasin</td>
        <td>{{ $entree->magasin?->libelle ?? '—' }}</td>
    </tr>
    <tr>
        <td class="cle">Fournisseur</td>
        <td>{{ $entree->fournisseur?->raison_sociale ?? '—' }}</td>
        <td class="cle">N° de BL fournisseur</td>
        <td>{{ $entree->reference_externe ?? '—' }}</td>
    </tr>
    <tr>
        <td class="cle">Bon de commande</td>
        <td>
            {{-- Le BC lié : magasinier et livreur voient la même référence. --}}
            @if($entree->bonCommande?->numero)
                <strong style="font-family: DejaVu Sans Mono, monospace;">{{ $entree->bonCommande->numero }}</strong>
            @else
                <span style="color:#6c757d;">Livraison hors commande</span>
            @endif
        </td>
        <td class="cle">Validé le</td>
        <td>{{ $entree->valide_le?->format('d/m/Y à H:i') ?? '—' }}</td>
    </tr>
</table>

{{-- ── Lignes REÇUES ───────────────────────────────────────────────────── --}}
@if($quantitatives->isNotEmpty())
    <h2>Articles reçus</h2>
    <table class="lignes">
        <thead>
            <tr>
                <th style="width:22mm;">Code</th>
                <th>Désignation</th>
                <th style="width:24mm;" class="nombre">Quantité</th>
                @if($afficherCouts)
                    <th style="width:26mm;" class="nombre">Coût unitaire</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($quantitatives as $ligne)
                <tr>
                    <td style="font-family: DejaVu Sans Mono, monospace;">{{ $ligne->article?->code }}</td>
                    <td>{{ $ligne->article?->nom }}</td>
                    <td class="nombre">
                        {{ rtrim(rtrim(number_format((float) $ligne->quantite, 2, ',', ' '), '0'), ',') }}
                        {{ $ligne->article?->unite_stock }}
                    </td>
                    @if($afficherCouts)
                        <td class="nombre">
                            {{ $ligne->cout_unitaire !== null ? number_format((float) $ligne->cout_unitaire, 0, ',', ' ').' FCFA' : '—' }}
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ── Annexe : les numéros de série (nature E) ─────────────────────────── --}}
@if($unites->isNotEmpty())
    <h2>Annexe — équipements sérialisés ({{ $unites->count() }})</h2>
    <table class="lignes">
        <thead>
            <tr>
                <th style="width:30mm;">Code inventaire</th>
                <th>Modèle</th>
                <th style="width:45mm;">N° de série</th>
                <th style="width:22mm;">État</th>
            </tr>
        </thead>
        <tbody>
            @foreach($unites as $unite)
                <tr>
                    <td style="font-family: DejaVu Sans Mono, monospace;">{{ $unite->code_inventaire }}</td>
                    <td>{{ $unite->modele }}</td>
                    <td style="font-family: DejaVu Sans Mono, monospace;">{{ $unite->numero_serie }}</td>
                    <td>{{ ucfirst($unite->etat ?? '—') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- ── Observation typée ───────────────────────────────────────────────── --}}
<div class="observation">
    <span class="type">
        Observation :
        {{ config('stock.motifs_observation_entree.'.$entree->observation_type, $entree->observation_type ? ucfirst($entree->observation_type) : 'Livraison conforme') }}
    </span>
    @if($entree->observation)
        <div style="margin-top:1.5mm;">{{ $entree->observation }}</div>
    @endif

    @if($contrePassation)
        <div style="margin-top:2mm; color:#dc3545;">
            Contre-passation : {{ $contrePassation['nombre'] }} mouvement(s) annulé(s)
            (réf. {{ $contrePassation['references'] }}){{ $contrePassation['motif'] ? ' — '.$contrePassation['motif'] : '' }}.
        </div>
    @endif

    @if($blFournisseurs->isNotEmpty())
        <div class="bl">
            📎 Bordereau(x) du fournisseur joint(s) :
            {{ $blFournisseurs->pluck('nom_original')->implode(' · ') }}
        </div>
    @endif
</div>

{{-- ── Les deux signatures : sans contradiction, pas de réclamation ────── --}}
<table class="signatures">
    <tr>
        <td>
            <div class="role">Le magasinier</div>
            <div class="nom">{{ $entree->valideur?->name ?? $entree->createur?->name ?? '' }}</div>
            <div class="mention">Certifie avoir reçu et compté les articles ci-dessus.</div>
        </td>
        <td>
            <div class="role">Le livreur</div>
            <div class="nom">&nbsp;</div>
            <div class="mention">Nom, date et signature.</div>
        </td>
    </tr>
</table>

<div class="pied">
    CHU-YO — Bordereau de réception {{ $entree->numero }} ·
    édité le {{ $genereLe->format('d/m/Y à H:i') }}
    @unless($afficherCouts)
        · document de quai, sans mention de coûts
    @endunless
</div>

</body>
</html>
