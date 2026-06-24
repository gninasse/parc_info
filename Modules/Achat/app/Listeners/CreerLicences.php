<?php

namespace Modules\Achat\Listeners;

use Modules\Achat\Events\BordereauLivraisonValide;

class CreerLicences
{
    /**
     * Handle the event.
     */
    public function handle(BordereauLivraisonValide $event): void
    {
        // Handled inline in WizardValidationService.
    }
}
