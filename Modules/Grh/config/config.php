<?php

return [
    'name' => 'Grh',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Gestion RH', 'icon' => 'bi bi-person-badge', 'route' => 'grh.dashboard', 'permission' => 'grh.dashboard.view'],
    ],

];
