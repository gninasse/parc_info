<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Achat\Models\BordereauLivraison;

class BordereauLivraisonValide
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public BordereauLivraison $bordereau,
        public array $equipements = [],
        public array $licences = []
    ) {}
}
