<?php

return [
    'name' => 'Catalogue',

    /*
    |--------------------------------------------------------------------------
    | Navigation inter-modules (sidebar commune Core)
    |--------------------------------------------------------------------------
    */
    'navigation' => [
        ['label' => 'Catalogue', 'icon' => 'bi bi-journal-bookmark', 'route' => 'catalogue.articles.index', 'permission' => 'catalogue.articles.index'],
    ],

];
