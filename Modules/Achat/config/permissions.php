<?php

// Liste EXACTE du SFD §5 — synchronisée par `cores:sync-permissions achat`.
// Toute permission déclarée correspond à une fonctionnalité effective et est
// seedée (leçon AN-01). Aucune permission de modification des documents
// validés : l'absence documente l'immutabilité (convention Stock).
return [
    // Tableau de bord
    'achat.dashboard.view' => 'Accéder au module et au tableau de bord',

    // Bons de commande (index couvre listes, fiches et PDF en lecture)
    'achat.bons_commande.index' => 'Voir les bons de commande et leur PDF',
    'achat.bons_commande.store' => 'Créer un bon de commande',
    'achat.bons_commande.update' => 'Modifier un bon de commande en brouillon',
    'achat.bons_commande.destroy' => 'Supprimer un bon de commande en brouillon',
    'achat.bons_commande.soumettre' => 'Soumettre un bon de commande au visa',
    'achat.bons_commande.valider' => 'Valider ou renvoyer un bon de commande',
    'achat.bons_commande.annuler' => 'Annuler un bon de commande sans réception',
    'achat.bons_commande.cloturer' => 'Clôturer le reliquat d\'un bon de commande',
    'achat.bons_commande.regulariser' => 'Créer un bon de régularisation et rattacher des équipements',

    // Licences et prestations
    'achat.licences.receptionner' => 'Réceptionner des licences et constater le service fait',

    // Pièces jointes
    'achat.documents.view' => 'Consulter et télécharger les pièces jointes',
    'achat.documents.store' => 'Déposer une pièce jointe',
    'achat.documents.delete' => 'Supprimer une pièce jointe',

    // Reliquats
    'achat.reliquats.index' => 'Consulter les reliquats',

    // Rapports
    'achat.rapports.view' => 'Consulter les rapports',
    'achat.rapports.export' => 'Exporter les rapports',
    'achat.rapports.signaux' => 'Consulter le rapport Signaux',

    // Administration
    'achat.administration.manage' => 'Administrer les paramètres du module',

    // API inter-modules
    'achat.api.view' => 'Consulter les commandes via l\'API inter-modules',
];
