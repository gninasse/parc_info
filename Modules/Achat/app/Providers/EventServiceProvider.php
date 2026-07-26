<?php

namespace Modules\Achat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Achat\Events\BonCommandeAnnule;
use Modules\Achat\Events\BonCommandeCloture;
use Modules\Achat\Events\BonCommandeValide;
use Modules\Achat\Events\BordereauLivraisonValide;
use Modules\Achat\Listeners\JournaliserEvenementAchat;

/**
 * Correction AN-08 : la table d'écoute ne référence plus que des écouteurs
 * réellement implémentés. Les quatre stubs vides de la version précédente
 * (CreerEquipementsDansParcInfo, CreerLicences, MettreAJourStockConsommables,
 * HistoriserAcquisition) ont été supprimés : leur logique s'exécute dans
 * WizardValidationService, à l'intérieur de la transaction d'intégration.
 */
class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BonCommandeValide::class => [
            JournaliserEvenementAchat::class,
        ],

        BonCommandeAnnule::class => [
            JournaliserEvenementAchat::class,
        ],

        BonCommandeCloture::class => [
            JournaliserEvenementAchat::class,
        ],

        BordereauLivraisonValide::class => [
            JournaliserEvenementAchat::class,
        ],
    ];

    protected static $shouldDiscoverEvents = false;
}
