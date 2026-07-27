<?php

return [
    'name' => 'ParcInfo',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Parc Informatique', 'icon' => 'bi bi-laptop', 'route' => 'parc-info.dashboard', 'permission' => 'parcinfo.dashboard.view'],
    ],

];
