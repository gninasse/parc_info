<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impression Bon de Commande {{ $bonCommande->numero_commande }}</title>
    <!-- Fonts -->
    <link rel="stylesheet" href="{{ asset('plugins/source-sans-3/index.css') }}" />
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}" />
    <!-- Bootstrap Icons & FontAwesome -->
    <link rel="stylesheet" href="{{ asset('plugins/bootstrap-icons/font/bootstrap-icons.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome/css/all.min.css') }}" />
    
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Source Sans 3', 'Helvetica Neue', Arial, sans-serif;
            color: #334155;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Simuler une page A4 sur écran */
        .print-preview-container {
            background-color: #ffffff;
            width: 210mm;
            min-height: 297mm;
            margin: 30px auto;
            padding: 20mm;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            border-radius: 4px;
            position: relative;
        }

        .no-print-bar {
            background-color: #1e293b;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .hospital-logo {
            max-height: 70px;
            width: auto;
        }

        .border-blue-custom {
            border: 2px solid #1a73e8 !important;
        }

        .border-blue-light {
            border-top: 2px solid #1a73e8;
        }

        .bg-blue-light {
            background-color: #eff6ff !important;
        }

        .text-blue-custom {
            color: #1a73e8 !important;
        }

        .table-custom th {
            background-color: #1a73e8 !important;
            color: #ffffff !important;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .table-custom td {
            font-size: 0.85rem;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background-color: #f8fafc;
            padding: 15px;
            height: 100%;
        }

        .info-card-title {
            font-weight: 700;
            color: #1e3a8a;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .signature-box {
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 15px;
            min-height: 140px;
        }

        .signature-title {
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-align: center;
            margin-bottom: 70px;
            text-decoration: underline;
        }

        .print-footer {
            border-top: 1px solid #cbd5e1;
            padding-top: 10px;
            font-size: 0.75rem;
            color: #64748b;
            text-align: center;
        }

        /* Styles d'impression */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print {
                display: none !important;
            }

            .print-preview-container {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }

            /* Forcer le rendu des couleurs d'arrière-plan sur les navigateurs */
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- Barre d'outils d'impression (non imprimable) -->
    <div class="no-print no-print-bar py-3 px-4 text-white d-flex justify-content-between align-items-center sticky-top">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-file-invoice text-info fs-4"></i>
            <span class="fw-bold fs-5">Aperçu du Bon de Commande - {{ $bonCommande->numero_commande }}</span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('achat.bons-commande.imprimer', ['bon_commande' => $bonCommande->id, 'pdf' => 1]) }}" class="btn btn-sm btn-info text-white px-3 rounded-1">
                <i class="fas fa-download me-1"></i>Télécharger PDF
            </a>
            <button onclick="window.print();" class="btn btn-sm btn-success px-3 rounded-1">
                <i class="fas fa-print me-1"></i>Imprimer
            </button>
            <button onclick="window.close();" class="btn btn-sm btn-secondary px-3 rounded-1">
                <i class="fas fa-times me-1"></i>Fermer
            </button>
        </div>
    </div>

    <!-- Conteneur Simulant la Page A4 -->
    <div class="print-preview-container">
        
        <!-- En-tête -->
        <div class="row align-items-start mb-4">
            <div class="col-7">
                <div class="d-flex align-items-center gap-3 mb-2">
                    @if(file_exists(public_path('images/chuyo_logo.png')))
                        <img class="hospital-logo" src="{{ asset('images/chuyo_logo.png') }}" alt="Logo CHU-YO">
                    @endif
                    <div>
                        <h4 class="fw-bold text-dark mb-0 uppercase">CHU-YO Achat</h4>
                        <div class="text-muted fw-bold small" style="font-size: 0.75rem;">CENTRE HOSPITALIER UNIVERSITAIRE SOUROU SANOU</div>
                    </div>
                </div>
                <div class="small text-secondary" style="font-size: 0.8rem; line-height: 1.4;">
                    BP 1024, Avenue Houari Boumédiène<br>
                    Bobo-Dioulasso, Burkina Faso<br>
                    Tél: (+226) 20 97 00 44 / 45
                </div>
            </div>
            
            <div class="col-5 text-end">
                <h3 class="fw-black text-blue-custom uppercase mb-2" style="letter-spacing: 0.5px;">Bon de Commande</h3>
                <div class="d-inline-block text-start border-blue-custom bg-blue-light p-3 rounded-1" style="min-width: 220px;">
                    <table class="w-100 table-sm mb-0 table-borderless" style="font-size: 0.85rem;">
                        <tr>
                            <td class="text-muted fw-bold pe-2">N° Commande:</td>
                            <td class="fw-bold text-dark font-monospace">{{ $bonCommande->numero_commande }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold pe-2">Date:</td>
                            <td class="fw-bold text-dark">{{ $bonCommande->date_commande->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted fw-bold pe-2">Statut:</td>
                            <td class="fw-bold" style="color: {{ $bonCommande->statut === 'valide' ? '#16a34a' : ($bonCommande->statut === 'annule' ? '#dc2626' : '#4b5563') }};">
                                {{ strtoupper($bonCommande->statut) }}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="divider border-blue-light mb-4"></div>

        <!-- Section Infos Fournisseur et Livraison -->
        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="info-card">
                    <div class="info-card-title">Fournisseur</div>
                    <div class="small text-dark" style="line-height: 1.5;">
                        <h6 class="fw-bold text-dark mb-1">{{ $bonCommande->fournisseur->nom }}</h6>
                        <span class="text-muted">Code:</span> {{ $bonCommande->fournisseur->code }}<br>
                        <span class="text-muted">Ville:</span> {{ $bonCommande->fournisseur->ville }}<br>
                        <span class="text-muted">Téléphone:</span> {{ $bonCommande->fournisseur->telephone ?: '-' }}
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="info-card">
                    <div class="info-card-title">Adresse de Livraison</div>
                    <div class="small text-dark" style="line-height: 1.5;">
                        <h6 class="fw-bold text-dark mb-1">CHU Sourou Sanou</h6>
                        <span class="text-muted">Service:</span> Service des Achats & Approvisionnements<br>
                        <span class="text-muted">Bâtiment:</span> Direction Générale, Rez-de-chaussée<br>
                        <span class="text-muted">Ville:</span> Bobo-Dioulasso, Burkina Faso
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Observations -->
        @if($bonCommande->commentaire)
            <div class="card bg-light border-0 p-3 mb-4 rounded-1">
                <div class="fw-bold text-secondary mb-1" style="font-size: 0.8rem;">Observations / Notes :</div>
                <div class="small text-dark" style="white-space: pre-wrap;">{{ $bonCommande->commentaire }}</div>
            </div>
        @endif

        <!-- Tableau des Lignes -->
        <table class="table table-bordered table-striped table-custom align-middle mb-4">
            <thead>
                <tr>
                    <th style="width: 15%;">Code Article</th>
                    <th style="width: 45%;">Désignation</th>
                    <th style="width: 15%;">Catégorie</th>
                    <th style="width: 10%;" class="text-center">Qté</th>
                    <th style="width: 15%;" class="text-end">Prix Unit. HT</th>
                    <th style="width: 15%;" class="text-end">Total HT</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bonCommande->lignesCommande as $l)
                    <tr>
                        <td class="font-monospace text-muted">{{ $l->article->code_article }}</td>
                        <td>
                            <div class="fw-bold text-dark">{{ $l->article->designation }}</div>
                        </td>
                        <td>
                            <span class="badge bg-light text-secondary border">{{ config("achat.types_articles.{$l->article->type_article}", $l->article->type_article) }}</span>
                        </td>
                        <td class="text-center fw-bold">{{ $l->quantite }}</td>
                        <td class="text-end font-monospace">{{ number_format($l->prix_unitaire, 0, ',', ' ') }}</td>
                        <td class="text-end font-monospace fw-bold">{{ number_format($l->quantite * $l->prix_unitaire, 0, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="row justify-content-end mb-5">
            <div class="col-5">
                <table class="table table-sm table-borderless mb-0" style="font-size: 0.9rem;">
                    <tr class="border-bottom">
                        <td class="text-muted py-2">Sous-total HT :</td>
                        <td class="text-end fw-semibold text-dark py-2 font-monospace">{{ number_format($bonCommande->montant_total, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr class="border-bottom">
                        <td class="text-muted py-2">TVA (18%) :</td>
                        <td class="text-end fw-semibold text-dark py-2 font-monospace">{{ number_format($bonCommande->montant_total * 0.18, 0, ',', ' ') }} FCFA</td>
                    </tr>
                    <tr class="bg-blue-light border-blue-custom border-2 rounded-1">
                        <td class="fw-bold text-blue-custom py-2 ps-2">Montant Total TTC :</td>
                        <td class="text-end fw-black text-blue-custom py-2 pe-2 font-monospace fs-5">
                            {{ number_format($bonCommande->montant_total * 1.18, 0, ',', ' ') }} FCFA
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Signatures -->
        <div class="row g-4 mb-5">
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-title">L'Acheteur (Signature & Cachet)</div>
                    <div class="text-center small text-secondary">
                        @if($bonCommande->validateur)
                            Validé par: <strong class="text-dark">{{ $bonCommande->validateur->name }}</strong><br>
                            Le: {{ $bonCommande->date_validation ? $bonCommande->date_validation->format('d/m/Y à H:i') : '' }}
                        @else
                            &nbsp;
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="signature-box">
                    <div class="signature-title">Le Fournisseur (Bon pour Accord)</div>
                    <div class="text-center small text-secondary">
                        Date: ____/____/________<br>
                        Signature & Cachet
                    </div>
                </div>
            </div>
        </div>

        <!-- Pied de page -->
        <div class="print-footer mt-5">
            Les conditions générales d'achat du CHU-YO s'appliquent à cette commande. Le paiement sera effectué à 30 jours fin de mois après réception conforme du matériel et de la facture.
            <div class="mt-2 text-muted font-monospace" style="font-size: 0.65rem;">
                Page 1/1 - Généré le {{ now()->format('d/m/Y H:i') }} - CHU-YO Achats
            </div>
        </div>

    </div>

</body>
</html>
