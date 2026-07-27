<?php

/*
|--------------------------------------------------------------------------
| Permissions du module Achat
|--------------------------------------------------------------------------
| Format plat { nom => libellé } imposé par Core\Services\PermissionService,
| qui itère directement sur ce tableau (cores:sync-permissions achat).
|
| Convention : {module}.{ressource}.{action}
| Actions standard : view, create, edit, delete, valider, annuler, export
|
| Règle EF-ADM-05 : toute permission déclarée ici est effectivement contrôlée
| dans le code. Aucune permission « en attente de fonctionnalité ».
*/

return [
    // Tableau de bord
    'achat.dashboard.view' => 'Voir le tableau de bord Achat',

    // Catalogue des articles
    'achat.articles.view' => 'Voir le catalogue des articles',
    'achat.articles.create' => 'Créer un article',
    'achat.articles.edit' => 'Modifier un article',
    'achat.articles.delete' => 'Supprimer un article',

    // Bons de commande
    'achat.bons_commande.view' => 'Voir les bons de commande',
    'achat.bons_commande.create' => 'Créer un bon de commande',
    'achat.bons_commande.edit' => 'Modifier un bon de commande en brouillon',
    'achat.bons_commande.delete' => 'Supprimer un bon de commande en brouillon',
    'achat.bons_commande.valider' => 'Valider un bon de commande',
    'achat.bons_commande.annuler' => 'Annuler un bon de commande',
    'achat.bons_commande.cloturer' => 'Clôturer le reliquat d\'un bon de commande',

    // Bordereaux de livraison
    'achat.bordereaux.view' => 'Voir les bordereaux de livraison',
    'achat.bordereaux.create' => 'Créer un bordereau de livraison',
    'achat.bordereaux.edit' => 'Modifier un bordereau de livraison en brouillon',
    'achat.bordereaux.delete' => 'Supprimer un bordereau de livraison en brouillon',
    'achat.bordereaux.valider' => 'Valider un bordereau et intégrer au parc',

    // Documents joints
    'achat.documents.view' => 'Consulter et télécharger les documents joints',
    'achat.documents.create' => 'Joindre un document',
    'achat.documents.delete' => 'Supprimer un document joint',

    // Rapports
    'achat.rapports.view' => 'Consulter les états et statistiques',
    'achat.rapports.export' => 'Exporter les états en PDF',

    // Paramètres (EF-ADM)
    'achat.parametres.view' => 'Consulter les paramètres du module',
    'achat.parametres.edit' => 'Modifier les paramètres du module',
];
