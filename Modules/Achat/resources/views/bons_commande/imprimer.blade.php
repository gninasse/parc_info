<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aperçu &mdash; Bon de commande {{ $bonCommande->numero_commande }}</title>

    <link rel="stylesheet" href="{{ asset('plugins/source-sans-3/index.css') }}">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}">

    <style>
        body {
            background-color: #e9ecef;
            font-family: 'Source Sans 3', sans-serif;
            color: #212529;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Simulation d'une feuille A4 à l'écran */
        .feuille {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 24px auto;
            padding: 18mm 16mm;
            box-shadow: 0 4px 16px rgba(0,0,0,.12);
        }

        .barre-outils {
            background-color: #0d2060;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .titre-doc { color: #0d2060; text-transform: uppercase; }
        .table-lignes thead th {
            background-color: #0d2060 !important;
            color: #fff !important;
            font-size: .78rem;
            text-transform: uppercase;
        }
        .table-lignes td { font-size: .85rem; }
        .encadre { border: 1px solid #dee2e6; padding: .75rem 1rem; height: 100%; }
        .encadre-titre {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #6c757d;
            font-weight: 700;
        }
        .cadre-signature { border: 1px solid #dee2e6; height: 70px; }

        @media print {
            body { background-color: #fff; }
            .barre-outils { display: none !important; }
            .feuille { width: auto; margin: 0; padding: 0; box-shadow: none; min-height: 0; }
        }
    </style>
</head>
<body>

<div class="barre-outils py-2 px-3 d-flex justify-content-between align-items-center">
    <span class="text-white fw-semibold small">
        <i class="fas fa-file-invoice me-2"></i>Bon de commande {{ $bonCommande->numero_commande }}
    </span>
    <div class="d-flex gap-2">
        <a href="{{ route('achat.bons-commande.imprimer', $bonCommande) }}?pdf=1"
           class="btn btn-sm btn-info text-white rounded-1 px-3">
            <i class="fas fa-file-pdf me-1"></i>Télécharger le PDF
        </a>
        <button onclick="window.print();" class="btn btn-sm btn-success rounded-1 px-3">
            <i class="fas fa-print me-1"></i>Imprimer
        </button>
        <a href="{{ route('achat.bons-commande.show', $bonCommande) }}"
           class="btn btn-sm btn-secondary rounded-1 px-3">
            <i class="fas fa-times me-1"></i>Fermer
        </a>
    </div>
</div>

<div class="feuille">

    <div class="row align-items-center border-bottom border-2 pb-3 mb-4" style="border-color:#0d2060 !important">
        <div class="col-7">
            <div class="fw-bold" style="color:#0d2060; font-size:1.05rem; line-height:1.2">
                CENTRE HOSPITALIER UNIVERSITAIRE YALGADO OUÉDRAOGO
            </div>
            <div class="text-muted small">
                Direction des Systèmes d'Information &mdash; Service Approvisionnement
            </div>
        </div>
        <div class="col-5 text-end">
            <div class="titre-doc fw-bold fs-4">Bon de commande</div>
            <div class="fw-bold fs-6 font-monospace">{{ $bonCommande->numero_commande }}</div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="encadre">
                <div class="encadre-titre mb-1">Fournisseur</div>
                <div class="fw-bold">{{ $bonCommande->fournisseur?->nom }}</div>
                <div class="small">Code : {{ $bonCommande->fournisseur?->code }}</div>
                @if($bonCommande->fournisseur?->adresse)
                    <div class="small">{{ $bonCommande->fournisseur->adresse }}</div>
                @endif
                @if($bonCommande->fournisseur?->ville)
                    <div class="small">{{ $bonCommande->fournisseur->ville }}</div>
                @endif
            </div>
        </div>
        <div class="col-6">
            <div class="encadre">
                <div class="encadre-titre mb-1">Références</div>
                <div class="small"><strong>Date de commande :</strong> {{ $bonCommande->date_commande?->format('d/m/Y') }}</div>
                <div class="small"><strong>Statut :</strong> {{ $bonCommande->statut_label }}</div>
                @if($bonCommande->date_validation)
                    <div class="small"><strong>Validé le :</strong> {{ $bonCommande->date_validation->format('d/m/Y') }}</div>
                    <div class="small"><strong>Validé par :</strong> {{ $bonCommande->validateur?->name ?? '—' }}</div>
                @endif
            </div>
        </div>
    </div>

    <table class="table table-sm table-bordered table-lignes align-middle">
        <thead>
            <tr>
                <th style="width:11%">Code</th>
                <th style="width:36%">Désignation</th>
                <th style="width:8%" class="text-center">Unité</th>
                <th style="width:7%" class="text-center">Qté</th>
                <th style="width:13%" class="text-end">P.U. HT</th>
                <th style="width:9%" class="text-center">TVA</th>
                <th style="width:16%" class="text-end">Montant HT</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bonCommande->lignesCommande as $ligne)
                <tr>
                    <td class="font-monospace small">{{ $ligne->article?->code_article }}</td>
                    <td>
                        <strong>{{ $ligne->article?->designation }}</strong>
                        @if($ligne->article?->reference_constructeur)
                            <div class="text-muted" style="font-size:.72rem">
                                Réf. : {{ $ligne->article->reference_constructeur }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center">{{ $ligne->article?->unite_mesure }}</td>
                    <td class="text-center">{{ $ligne->quantite }}</td>
                    <td class="text-end font-monospace">{{ number_format($ligne->prix_unitaire, 0, ',', ' ') }}</td>
                    <td class="text-center">{{ rtrim(rtrim(number_format($ligne->taux_tva, 2, ',', ''), '0'), ',') }} %</td>
                    <td class="text-end font-monospace">{{ number_format($ligne->montant_ht, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-3">Aucune ligne.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="row">
        <div class="col-6 offset-6">
            <table class="table table-sm mb-0">
                <tr>
                    <td class="text-muted">Total HT</td>
                    <td class="text-end fw-bold">{{ number_format($bonCommande->montant_ht, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr>
                    <td class="text-muted">TVA</td>
                    <td class="text-end fw-bold">{{ number_format($bonCommande->montant_tva, 0, ',', ' ') }} FCFA</td>
                </tr>
                <tr style="border-top:2px solid #0d2060">
                    <td class="fw-bold" style="color:#0d2060">Montant TTC</td>
                    <td class="text-end fw-bold fs-6" style="color:#0d2060">
                        {{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} FCFA
                    </td>
                </tr>
            </table>
        </div>
    </div>

    @if($bonCommande->commentaire)
        <div class="encadre mt-3">
            <div class="encadre-titre mb-1">Observations</div>
            <div class="small">{{ $bonCommande->commentaire }}</div>
        </div>
    @endif

    <p class="text-muted fst-italic small mt-3">
        Arrêté le présent bon de commande à la somme de
        {{ number_format($bonCommande->montant_ttc, 0, ',', ' ') }} francs CFA toutes taxes comprises.
    </p>

    <div class="row g-3 mt-4">
        <div class="col-4">
            <div class="small mb-1">Le service approvisionnement</div>
            <div class="cadre-signature"></div>
        </div>
        <div class="col-4">
            <div class="small mb-1">Le responsable habilité</div>
            <div class="cadre-signature"></div>
        </div>
        <div class="col-4">
            <div class="small mb-1">Le fournisseur (bon pour accord)</div>
            <div class="cadre-signature"></div>
        </div>
    </div>

    <div class="text-muted border-top mt-4 pt-2" style="font-size:.68rem">
        Édité le {{ now()->format('d/m/Y à H:i') }} &mdash;
        CHU Yalgado Ouédraogo, système de gestion du parc informatique.
    </div>
</div>

</body>
</html>
