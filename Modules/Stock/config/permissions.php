<?php

// Liste EXACTE du SFD §5 — synchronisée par `cores:sync-permissions stock`.
// Aucune permission de modification des documents validés : l'absence
// documente l'immutabilité (corrections par contre-mouvement).
return [
    // Tableau de bord
    'stock.dashboard.view' => 'Accéder au module et au tableau de bord',

    // Magasins
    'stock.magasins.index' => 'Voir la liste des magasins',
    'stock.magasins.store' => 'Créer un magasin',
    'stock.magasins.update' => 'Modifier un magasin',
    'stock.magasins.destroy' => 'Supprimer un magasin',
    'stock.magasins.toggle-status' => 'Activer/Désactiver un magasin',

    // État des stocks
    'stock.niveaux.index' => 'Consulter l\'état des stocks',
    'stock.niveaux.seuil' => 'Ajuster un seuil local',

    // Entrées (store couvre création, référencement, validation, PDF)
    'stock.entrees.index' => 'Voir la liste des entrées',
    'stock.entrees.store' => 'Créer et valider une entrée',
    'stock.entrees.update' => 'Modifier une entrée non validée',
    'stock.entrees.destroy' => 'Supprimer une entrée non validée',

    // Sorties
    'stock.sorties.index' => 'Voir la liste des sorties',
    'stock.sorties.store' => 'Créer et valider une sortie',
    'stock.sorties.update' => 'Modifier une sortie non validée',
    'stock.sorties.destroy' => 'Supprimer une sortie non validée',

    // Transferts
    'stock.transferts.index' => 'Voir la liste des transferts',
    'stock.transferts.store' => 'Créer et valider un transfert',
    'stock.transferts.update' => 'Modifier un transfert non validé',
    'stock.transferts.destroy' => 'Supprimer un transfert non validé',

    // Historique des mouvements
    'stock.mouvements.index' => 'Consulter l\'historique des mouvements',
    'stock.mouvements.contre' => 'Créer un contre-mouvement',

    // Inventaires
    'stock.inventaires.index' => 'Voir la liste des inventaires',
    'stock.inventaires.store' => 'Ouvrir un inventaire',
    'stock.inventaires.saisie' => 'Saisir un comptage d\'inventaire',
    'stock.inventaires.valider' => 'Valider un inventaire',
    'stock.inventaires.annuler' => 'Annuler un inventaire',

    // Rapports
    'stock.rapports.view' => 'Consulter les rapports',
    'stock.rapports.export' => 'Exporter les rapports',

    // API inter-modules
    'stock.api.view' => 'Consulter le stock via l\'API inter-modules',
];
