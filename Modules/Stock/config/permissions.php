<?php

/*
|--------------------------------------------------------------------------
| Permissions du module Stock
|--------------------------------------------------------------------------
| Format plat imposé par Core\Services\PermissionService, synchronisé par
| `php artisan cores:sync-permissions stock`.
|
| Décision d'architecture : la matrice de droits par magasin de la spec F1
| (stock_droits_magasin) est abandonnée — la sécurité repose exclusivement
| sur spatie/laravel-permission. RG-F5-04 est portée par
| stock.transferts.valider.
|
| Règle EF-ADM-05 : toute permission déclarée ici est effectivement
| contrôlée dans le code. Aucune permission en attente de fonctionnalité.
*/

return [
    'stock.dashboard.view' => 'Consulter le tableau de bord des stocks',

    'stock.magasins.view' => 'Consulter les magasins',
    'stock.magasins.create' => 'Créer un magasin',
    'stock.magasins.edit' => 'Modifier un magasin',
    'stock.magasins.admin' => 'Administrer les magasins (activer, désactiver, supprimer, responsables)',

    'stock.articles.view' => 'Consulter le stock par article',
    'stock.articles.admin' => 'Initialiser un article en stock',

    'stock.entrees.view' => 'Consulter les entrées de stock',
    'stock.entrees.create' => 'Enregistrer une entrée de stock',
    'stock.entrees.admin' => 'Supprimer une entrée manuelle récente',

    'stock.sorties.view' => 'Consulter les sorties de stock',
    'stock.sorties.create' => 'Enregistrer une sortie de stock',
    'stock.sorties.admin' => 'Enregistrer une régularisation négative',

    'stock.transferts.view' => 'Consulter les transferts inter-magasins',
    'stock.transferts.create' => 'Créer un transfert inter-magasins',
    'stock.transferts.valider' => 'Valider ou rejeter un transfert',
    'stock.transferts.admin' => 'Annuler un transfert',

    'stock.inventaires.view' => 'Consulter les inventaires',
    'stock.inventaires.create' => 'Ouvrir un inventaire et saisir les comptages',
    'stock.inventaires.admin' => 'Valider ou annuler un inventaire',

    'stock.valorisation.view' => 'Consulter la valorisation du stock',
    'stock.valorisation.admin' => 'Créer un snapshot et recalculer la valorisation',

    'stock.rapports.view' => 'Consulter et exporter les rapports de stock',
];
