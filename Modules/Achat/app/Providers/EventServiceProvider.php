<?php

namespace Modules\Achat\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Achat\Events\BonCommandeAnnule;
use Modules\Achat\Events\BonCommandeValide;
use Modules\Achat\Events\BordereauLivraisonValide;
use Modules\Achat\Events\EquipementCreeViaAchat;
use Modules\Achat\Listeners\CreerEquipementsDansParcInfo;
use Modules\Achat\Listeners\CreerLicences;
use Modules\Achat\Listeners\HistoriserAcquisition;
use Modules\Achat\Listeners\MettreAJourStockConsommables;
use Modules\Achat\Listeners\NotifierUtilisateurs;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BonCommandeValide::class => [
            NotifierUtilisateurs::class,
        ],

        BonCommandeAnnule::class => [
            NotifierUtilisateurs::class,
        ],

        BordereauLivraisonValide::class => [
            CreerEquipementsDansParcInfo::class,
            MettreAJourStockConsommables::class,
            CreerLicences::class,
            HistoriserAcquisition::class,
            NotifierUtilisateurs::class,
        ],

        EquipementCreeViaAchat::class => [
            HistoriserAcquisition::class,
        ],
    ];
}
