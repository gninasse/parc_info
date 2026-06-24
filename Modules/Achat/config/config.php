<?php

return [
    'name' => 'Achat',
    'icon' => 'fas fa-shopping-cart',
    'order' => 3,

    // Patterns de génération
    'code_inventaire_pattern' => env('ACHAT_CODE_INVENTAIRE_PATTERN', 'INV-{YYYY}-{SEQUENCE:4}'),
    'prefix_bon_commande' => env('ACHAT_PREFIX_BC', 'BC'),
    'prefix_bordereau_livraison' => env('ACHAT_PREFIX_BL', 'BL'),

    // Types d'articles
    'types_articles' => [
        'equipement' => 'Équipement',
        'consommable' => 'Consommable',
        'licence' => 'Licence',
        'prestation' => 'Prestation',
    ],

    // Statuts BC
    'statuts_bc' => [
        'brouillon' => ['label' => 'Brouillon', 'color' => 'secondary'],
        'valide' => ['label' => 'Validé', 'color' => 'primary'],
        'partiel' => ['label' => 'Partiel', 'color' => 'warning'],
        'livre' => ['label' => 'Livré', 'color' => 'success'],
        'annule' => ['label' => 'Annulé', 'color' => 'danger'],
    ],

    // Statuts BL
    'statuts_bl' => [
        'brouillon' => ['label' => 'Brouillon', 'color' => 'secondary'],
        'wizard' => ['label' => 'En cours', 'color' => 'warning'],
        'valide' => ['label' => 'Validé', 'color' => 'success'],
    ],

    // Seuils stock
    'seuils_stock' => [
        'rupture' => 0,      // 0 unités
        'critique' => 0.2,   // 20% du seuil d'alerte
        'alerte' => 0.5,     // 50% du seuil d'alerte
        'faible' => 1.0,     // 100% du seuil d'alerte
    ],
];
