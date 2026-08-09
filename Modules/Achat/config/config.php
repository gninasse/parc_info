<?php

return [
    'name' => 'Achat',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Achats', 'icon' => 'bi bi-cart-check', 'route' => 'achat.dashboard', 'permission' => 'achat.dashboard.view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Constantes techniques (SFD §6.2 : le métier paramétrable vit en base,
    | table achat_parametres, écran A-08 — jamais ici)
    |--------------------------------------------------------------------------
    */

    // Types de pièces jointes (SFD §6.2)
    'types_documents' => [
        'bc_signe' => 'BC signé',
        'bordereau_fournisseur' => 'Bordereau fournisseur',
        'facture_proforma' => 'Facture pro forma',
        'autre' => 'Autre',
    ],

    // Extensions acceptées pour les pièces (la taille max est un paramètre A-08)
    'documents' => [
        'extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'xls', 'xlsx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Valeurs par défaut des paramètres métier (semées dans achat_parametres)
    |--------------------------------------------------------------------------
    | Servent au seeder et de repli si une clé venait à manquer en base.
    | Le TYPE de chaque clé est déclaré dans Services\AchatParametres : c'est
    | lui qui garantit qu'un booléen se relit en booléen et un entier en entier.
    */
    'parametres_defaut' => [
        'prefixe_numerotation' => 'BC',
        'delai_alerte_reliquat_jours' => '30',
        'seuil_ecart_prix_pct' => '20',
        'taille_max_piece_mo' => '10',

        /*
         * D-21 — quantité proposée quand on commande depuis une alerte de
         * seuil : `seuil × facteur`. C'est un point de départ, pas une
         * prescription. Calculer un réapprovisionnement optimal demanderait
         * une consommation historique et des délais fournisseurs dont on ne
         * dispose pas ; l'acheteur ajuste, il connaît le marché.
         */
        'facteur_reapprovisionnement' => '2',

        /*
         * Régularisation ACTIVE à l'installation : le plan de mise en service
         * (SFD §9.2) prévoit la saisie des BC d'intérim en « semaine 0 », avant
         * l'ouverture générale — le module doit donc ouvrir la porte, pas la
         * fermer. Elle se refermera SEULE quand la dette atteindra zéro
         * (extinction automatique A15), et sa réouverture sera un acte
         * d'administration journalisé (SW-06).
         */
        'regularisation_active' => '1',

        // Bornes de l'intérim : du retrait des modules v1 (SFD §1.1) à la mise
        // en service. Ajustables depuis l'écran A-08.
        'intermede_debut' => '2026-07-27',
        'intermede_fin' => '',

        // Motifs d'observation proposés à la saisie d'un BC (pilules A-03),
        // stockés en JSON : la liste est métier, donc administrable.
        'motifs_observation' => '{"urgent":"Urgent","renouvellement":"Renouvellement périodique","sur_demande":"Sur demande de service","autre":"Autre"}',
    ],
];
