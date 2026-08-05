<?php

namespace Modules\Achat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the application.
     *
     * Vide et assumé : l'intégration Achat ⇄ Stock est synchrone et
     * transactionnelle (SFD §1.7). Aucun événement déclaré non dispatché,
     * aucun listener vide (leçon AN-08).
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [];

    /**
     * Indicates if events should be discovered.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = true;

    /**
     * Configure the proper event listeners for email verification.
     */
    protected function configureEmailVerification(): void {}
}
