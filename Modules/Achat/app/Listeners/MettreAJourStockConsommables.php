<?php

namespace Modules\Achat\Listeners;

use Modules\Achat\Events\BordereauLivraisonValide;

class MettreAJourStockConsommables
{
    /**
     * Handle the event.
     */
    public function handle(BordereauLivraisonValide $event): void
    {
        // Handled inline in WizardValidationService.
    }
}
