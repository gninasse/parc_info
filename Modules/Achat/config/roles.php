<?php

/*
|--------------------------------------------------------------------------
| Rôles métier du module Achat
|--------------------------------------------------------------------------
| Séparé de permissions.php, dont le format plat est imposé par
| Core\Services\PermissionService.
|
| Exigence EXI-ORG-01 / RGC-11 : la saisie d'un bon de commande et sa
| validation relèvent de deux habilitations distinctes. Le rôle « acheteur »
| ne reçoit donc jamais achat.bons_commande.valider ni .annuler.
|
| Le motif « xxx.* » développe toutes les permissions du préfixe.
*/

return [
    'acheteur' => [
        'name' => 'Acheteur',
        'description' => 'Référence les articles, saisit les bons de commande et suit les reliquats',
        'permissions' => [
            'achat.dashboard.view',
            'achat.articles.*',
            'achat.bons_commande.view',
            'achat.bons_commande.create',
            'achat.bons_commande.edit',
            'achat.bons_commande.delete',
            'achat.bordereaux.view',
            'achat.stocks.view',
            'achat.documents.view',
            'achat.documents.create',
            'achat.rapports.view',
        ],
    ],

    'validateur_achat' => [
        'name' => 'Validateur Achat',
        'description' => 'Contrôle, valide, annule et clôture les bons de commande',
        'permissions' => [
            'achat.dashboard.view',
            'achat.articles.view',
            'achat.bons_commande.view',
            'achat.bons_commande.valider',
            'achat.bons_commande.annuler',
            'achat.bons_commande.cloturer',
            'achat.bordereaux.view',
            'achat.documents.view',
            'achat.documents.create',
            'achat.rapports.view',
            'achat.rapports.export',
        ],
    ],

    'magasinier' => [
        'name' => 'Magasinier',
        'description' => 'Réceptionne les livraisons et intègre le matériel au parc',
        'permissions' => [
            'achat.dashboard.view',
            'achat.articles.view',
            'achat.bons_commande.view',
            'achat.bordereaux.*',
            'achat.stocks.view',
            'achat.documents.view',
            'achat.documents.create',
            'achat.documents.delete',
        ],
    ],
];
