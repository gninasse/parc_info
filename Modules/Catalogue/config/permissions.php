<?php

return [
    // Articles
    'catalogue.articles.index' => 'Voir la liste des articles',
    'catalogue.articles.store' => 'Créer un article',
    'catalogue.articles.update' => 'Modifier un article',
    'catalogue.articles.destroy' => 'Supprimer un article',
    'catalogue.articles.toggle-status' => 'Activer/Désactiver un article',

    // Catégories
    'catalogue.categories.index' => 'Voir la liste des catégories',
    'catalogue.categories.store' => 'Créer une catégorie',
    'catalogue.categories.update' => 'Modifier une catégorie',
    'catalogue.categories.destroy' => 'Supprimer une catégorie',
    'catalogue.categories.toggle-status' => 'Activer/Désactiver une catégorie',

    // Fournisseurs
    'catalogue.fournisseurs.index' => 'Voir la liste des fournisseurs',
    'catalogue.fournisseurs.store' => 'Créer un fournisseur',
    'catalogue.fournisseurs.update' => 'Modifier un fournisseur',
    'catalogue.fournisseurs.destroy' => 'Supprimer un fournisseur',
    'catalogue.fournisseurs.toggle-status' => 'Activer/Désactiver un fournisseur',

    // Contacts d'un fournisseur.
    // Permissions DISTINCTES de celles du fournisseur : tenir un carnet
    // d'interlocuteurs à jour est un travail courant, alors que modifier la
    // raison sociale ou le code d'un fournisseur engage le référentiel.
    // Confondre les deux obligerait à donner le second pour permettre le
    // premier.
    'catalogue.contacts.index' => "Voir les contacts d'un fournisseur",
    'catalogue.contacts.store' => 'Ajouter un contact fournisseur',
    'catalogue.contacts.update' => 'Modifier un contact fournisseur',
    'catalogue.contacts.destroy' => 'Supprimer un contact fournisseur',

    // API inter-modules
    'catalogue.api.view' => 'Consulter le catalogue via l\'API inter-modules',
];
