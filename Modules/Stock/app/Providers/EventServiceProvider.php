<?php

namespace Modules\Stock\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

/**
 * PATTERNS §10 — La logique qui doit partager la transaction d'une opération
 * vit dans les services ; la table d'écoute ne référence que des écouteurs
 * réellement implémentés. Les notifications F8 (rupture, sous-seuil,
 * transfert en attente) seront enregistrées ici en P4/P5.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];

    protected static $shouldDiscoverEvents = false;
}
