<?php

return [
    'name' => 'Stock',

    /*
    |--------------------------------------------------------------------------
    | Magasin de réception par défaut
    |--------------------------------------------------------------------------
    | Code du magasin qui reçoit les entrées automatiques issues de la
    | validation des bordereaux de livraison (module Achat). Le magasin est
    | créé par le seeder du module ; il n'est jamais créé à la volée.
    */
    'magasin_reception_defaut' => env('STOCK_MAGASIN_RECEPTION', 'MAG-PRINCIPAL'),

    /*
    |--------------------------------------------------------------------------
    | Statuts d'alerte (F2 / F8)
    |--------------------------------------------------------------------------
    | qte > seuil : OK — 0 < qte <= seuil : ALERTE — qte = 0 : RUPTURE
    */
    'statuts_alerte' => [
        'OK' => ['label' => 'OK', 'color' => 'success'],
        'ALERTE' => ['label' => 'Sous seuil', 'color' => 'warning'],
        'RUPTURE' => ['label' => 'Rupture', 'color' => 'danger'],
    ],

    'statuts_transfert' => [
        'EN_ATTENTE' => ['label' => 'En attente', 'color' => 'warning'],
        'VALIDE' => ['label' => 'Validé', 'color' => 'success'],
        'REJETE' => ['label' => 'Rejeté', 'color' => 'danger'],
        'ANNULE' => ['label' => 'Annulé', 'color' => 'secondary'],
    ],

    'statuts_inventaire' => [
        'EN_COURS' => ['label' => 'En cours', 'color' => 'warning'],
        'CLOTURE' => ['label' => 'Clôturé', 'color' => 'success'],
        'ANNULE' => ['label' => 'Annulé', 'color' => 'secondary'],
    ],

    'types_mouvement' => [
        'ENTREE' => ['label' => 'Entrée', 'color' => 'success', 'sens' => 1],
        'SORTIE' => ['label' => 'Sortie', 'color' => 'danger', 'sens' => -1],
        'TRANSFERT_ENTRANT' => ['label' => 'Transfert entrant', 'color' => 'info', 'sens' => 1],
        'TRANSFERT_SORTANT' => ['label' => 'Transfert sortant', 'color' => 'info', 'sens' => -1],
        'REGULARISATION_PLUS' => ['label' => 'Régularisation +', 'color' => 'success', 'sens' => 1],
        'REGULARISATION_MOINS' => ['label' => 'Régularisation −', 'color' => 'danger', 'sens' => -1],
        'INVENTAIRE_PLUS' => ['label' => 'Écart inventaire +', 'color' => 'success', 'sens' => 1],
        'INVENTAIRE_MOINS' => ['label' => 'Écart inventaire −', 'color' => 'danger', 'sens' => -1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (consommée en P9 par la sidebar Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Tableau de bord', 'icon' => 'fas fa-tachometer-alt', 'route' => 'stock.dashboard.index', 'permission' => 'stock.dashboard.view'],
        ['label' => 'Magasins', 'icon' => 'fas fa-store', 'route' => 'stock.magasins.index', 'permission' => 'stock.magasins.view'],
        ['label' => 'Stock par article', 'icon' => 'fas fa-boxes', 'route' => 'stock.articles.index', 'permission' => 'stock.articles.view'],
        ['label' => 'Entrées', 'icon' => 'fas fa-arrow-circle-down', 'route' => 'stock.entrees.index', 'permission' => 'stock.entrees.view'],
        ['label' => 'Sorties', 'icon' => 'fas fa-arrow-circle-up', 'route' => 'stock.sorties.index', 'permission' => 'stock.sorties.view'],
        ['label' => 'Transferts', 'icon' => 'fas fa-exchange-alt', 'route' => 'stock.transferts.index', 'permission' => 'stock.transferts.view'],
        ['label' => 'Inventaires', 'icon' => 'fas fa-clipboard-check', 'route' => 'stock.inventaires.index', 'permission' => 'stock.inventaires.view'],
        ['label' => 'Valorisation', 'icon' => 'fas fa-coins', 'route' => 'stock.valorisation.index', 'permission' => 'stock.valorisation.view'],
        ['label' => 'Rapports', 'icon' => 'fas fa-chart-bar', 'route' => 'stock.rapports.index', 'permission' => 'stock.rapports.view'],
    ],
];
