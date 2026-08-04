<?php

return [
    'name' => 'Stock',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Gestion des stocks', 'icon' => 'bi bi-box-seam', 'route' => 'stock.dashboard', 'permission' => 'stock.dashboard.view'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuration métier (SFD §1.9, §3.4, §3.5 — UX §3.2, §4.2)
    |--------------------------------------------------------------------------
    */

    // Alerte informative si le coût saisi s'écarte du prix indicatif de ±20 %
    'seuil_alerte_cout' => 0.20,

    // Pastille superviseur « Non validés > N jours » du tableau de bord
    'jours_alerte_non_valides' => 30,

    // Motifs de sortie (pilules — « urgence_hors_ouverture » déplie la
    // date/heure réelle de remise, « autre » déplie un texte requis)
    'motifs_sortie' => [
        'dotation_periodique' => 'Dotation périodique',
        'remplacement' => 'Remplacement',
        'reparation' => 'Réparation',
        'urgence_hors_ouverture' => 'Urgence hors ouverture',
        'autre' => 'Autre',
    ],

    // Motifs types d'observation d'entrée (imprimés sur le bon)
    'motifs_observation_entree' => [
        'livraison_conforme' => 'Livraison conforme',
        'ecart_bl' => 'Écart BL — réclamation',
        'refus_livraison' => 'Refus à la livraison',
        'autre' => 'Autre',
    ],

    // Taille maximale de la file d'attente des scans (écrans douchette — S6)
    'file_scans_max' => 50,

    // Pièces jointes des bons (BL scanné, photo du colis, courrier…)
    'documents' => [
        'taille_max_ko' => 5120, // 5 Mo par fichier
        'extensions' => ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ],
];
