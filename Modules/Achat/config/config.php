<?php

return [
    'name' => 'Achat',
    'icon' => 'fas fa-shopping-cart',
    'order' => 3,

    /*
    |--------------------------------------------------------------------------
    | Numérotation
    |--------------------------------------------------------------------------
    | Les préfixes sont surchargeables en base via la table achat_parametres
    | (Parametre::getVal). Les valeurs ci-dessous ne servent que de repli.
    */
    'prefix_bon_commande' => env('ACHAT_PREFIX_BC', 'BC'),
    'prefix_bordereau_livraison' => env('ACHAT_PREFIX_BL', 'BL'),
    'code_inventaire_pattern' => env('ACHAT_CODE_INVENTAIRE_PATTERN', 'INV-{YYYY}-{SEQUENCE:4}'),

    /*
    |--------------------------------------------------------------------------
    | Fiscalité
    |--------------------------------------------------------------------------
    | RG-BC-05 : le taux de TVA appliqué est celui de l'article, figé sur la
    | ligne de commande au moment de la création du bon. Le taux ci-dessous
    | n'est que la valeur proposée par défaut au référencement d'un article.
    */
    'taux_tva_defaut' => env('ACHAT_TAUX_TVA', 18.00),

    /*
    |--------------------------------------------------------------------------
    | Types d'articles
    |--------------------------------------------------------------------------
    | 'wizard' : le type impose une saisie d'inventaire unitaire (RG-WZ-03).
    | 'stock'  : le type alimente le stock physique à la réception.
    */
    'types_articles' => [
        'equipement' => 'Équipement',
        'consommable' => 'Consommable',
        'licence' => 'Licence',
        'prestation' => 'Prestation',
    ],

    'types_avec_wizard' => ['equipement', 'licence'],

    'types_avec_stock' => ['consommable'],

    /*
    |--------------------------------------------------------------------------
    | Statuts
    |--------------------------------------------------------------------------
    */
    'statuts_bc' => [
        'brouillon' => ['label' => 'Brouillon', 'color' => 'secondary', 'icon' => 'fa-edit'],
        'valide' => ['label' => 'Validé', 'color' => 'primary', 'icon' => 'fa-check-circle'],
        'partiel' => ['label' => 'Livré partiel', 'color' => 'warning', 'icon' => 'fa-truck-loading'],
        'livre' => ['label' => 'Livré complet', 'color' => 'success', 'icon' => 'fa-truck'],
        'annule' => ['label' => 'Annulé', 'color' => 'danger', 'icon' => 'fa-times-circle'],
        'cloture' => ['label' => 'Clôturé', 'color' => 'dark', 'icon' => 'fa-lock'],
    ],

    'statuts_bl' => [
        'brouillon' => ['label' => 'Brouillon', 'color' => 'secondary', 'icon' => 'fa-edit'],
        'wizard' => ['label' => 'En cours d\'intégration', 'color' => 'warning', 'icon' => 'fa-magic'],
        'valide' => ['label' => 'Validé & intégré', 'color' => 'success', 'icon' => 'fa-check-double'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seuils de stock (EF-STK-07)
    |--------------------------------------------------------------------------
    | Exprimés en ratio du seuil d'alerte de l'article. Évalués dans l'ordre.
    */
    'seuils_stock' => [
        'rupture' => ['max_ratio' => 0.0, 'label' => 'Rupture', 'color' => 'danger'],
        'critique' => ['max_ratio' => 0.5, 'label' => 'Critique', 'color' => 'danger'],
        'alerte' => ['max_ratio' => 1.0, 'label' => 'Alerte', 'color' => 'warning'],
        'normal' => ['max_ratio' => null, 'label' => 'Stock correct', 'color' => 'success'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Intégration inter-modules
    |--------------------------------------------------------------------------
    | EF-STK-05 — Le module Stock (mouvements + lots FIFO) est le référentiel
    | UNIQUE des quantités et de la valorisation. Les anciens compteurs
    | (achat_articles.stock_actuel, parc_info_consommables.quantite_stock_actuel)
    | ont été supprimés ; les écrans lisent StockQueryInterface.
    */
    'integration' => [
        'stock' => env('ACHAT_INTEGRATION_STOCK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Documents joints
    |--------------------------------------------------------------------------
    */
    'documents' => [
        'taille_max_ko' => 10240,
        'disque' => 'public',
        'repertoire' => 'achat_documents',
        'mimes' => ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restitutions
    |--------------------------------------------------------------------------
    */
    'rapports' => [
        'mois_glissants' => 12,
        'top_fournisseurs' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reliquats (EF-BC-19)
    |--------------------------------------------------------------------------
    */
    'reliquat_alerte_jours' => env('ACHAT_RELIQUAT_ALERTE_JOURS', 60),

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Achats & Appro.', 'icon' => 'bi bi-cart', 'route' => 'achat.dashboard.index', 'permission' => 'achat.dashboard.view'],
    ],

];
