<?php

return [
    'name' => 'Core',
    'user_default_password' => 'password',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Administration', 'icon' => 'bi bi-gear', 'route' => 'cores.dashboard', 'permission' => 'cores.dashboard.view'],
    ],

];
