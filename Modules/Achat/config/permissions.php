<?php

return [
    // Permissions du module Achat
    'permissions' => [
        // Dashboard
        'achat.dashboard.view' => 'Voir le tableau de bord Achat',

        // Catalogue
        'achat.articles.view' => 'Voir les articles',
        'achat.articles.create' => 'Créer des articles',
        'achat.articles.edit' => 'Modifier les articles',
        'achat.articles.delete' => 'Supprimer les articles',

        // Bons de Commande
        'achat.bons_commande.view' => 'Voir les bons de commande',
        'achat.bons_commande.create' => 'Créer des bons de commande',
        'achat.bons_commande.edit' => 'Modifier les bons de commande (brouillon)',
        'achat.bons_commande.valider' => 'Valider les bons de commande',
        'achat.bons_commande.annuler' => 'Annuler les bons de commande',

        // Bordereaux de Livraison
        'achat.bordereaux.view' => 'Voir les bordereaux de livraison',
        'achat.bordereaux.create' => 'Créer des bordereaux de livraison',
        'achat.bordereaux.edit' => 'Modifier les bordereaux (brouillon)',
        'achat.bordereaux.valider' => 'Valider les bordereaux via wizard',

        // Stocks
        'achat.stocks.view' => 'Voir les stocks consommables',
        'achat.stocks.entree' => 'Enregistrer des entrées de stock',
        'achat.stocks.sortie' => 'Enregistrer des sorties de stock',
        'achat.stocks.inventaire' => 'Effectuer un inventaire',

        // Licences
        'achat.licences.view' => 'Voir les licences',
        'achat.licences.manage' => 'Gérer les licences',

        // Rapports
        'achat.rapports.view' => 'Voir les rapports',
        'achat.rapports.export' => 'Exporter les rapports',
    ],

    // Rôles suggérés
    'roles' => [
        'acheteur' => [
            'name' => 'Acheteur',
            'description' => 'Peut créer et gérer les commandes',
            'permissions' => [
                'achat.dashboard.view',
                'achat.articles.*',
                'achat.bons_commande.view',
                'achat.bons_commande.create',
                'achat.bons_commande.edit',
                'achat.bordereaux.view',
                'achat.stocks.view',
                'achat.licences.view',
                'achat.rapports.view',
            ],
        ],
        'validateur_achat' => [
            'name' => 'Validateur Achat',
            'description' => 'Peut valider et annuler les commandes',
            'permissions' => [
                'achat.dashboard.view',
                'achat.articles.view',
                'achat.bons_commande.*',
                'achat.bordereaux.view',
                'achat.rapports.view',
                'achat.rapports.export',
            ],
        ],
        'magasinier' => [
            'name' => 'Magasinier',
            'description' => 'Peut réceptionner les livraisons et gérer les stocks',
            'permissions' => [
                'achat.dashboard.view',
                'achat.articles.view',
                'achat.bons_commande.view',
                'achat.bordereaux.*',
                'achat.stocks.*',
                'achat.licences.view',
            ],
        ],
    ],
];
