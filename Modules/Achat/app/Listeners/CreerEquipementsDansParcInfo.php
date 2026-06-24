<?php

namespace Modules\Achat\Listeners;

use Modules\Achat\Events\BordereauLivraisonValide;

class CreerEquipementsDansParcInfo
{
    /**
     * Handle the event.
     */
    public function handle(BordereauLivraisonValide $event): void
    {
        // Handled inline in WizardValidationService to preserve transactional integrity and ease UI feedback.
    }
}
