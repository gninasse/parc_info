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
    */
    'parametres_defaut' => [
        'prefixe_numerotation' => 'BC',
        'delai_alerte_reliquat_jours' => '30',
        'seuil_ecart_prix_pct' => '20',
        'taille_max_piece_mo' => '5',
        'regularisation_active' => '0',
        'intermede_debut' => '',
        'intermede_fin' => '',
    ],
];
