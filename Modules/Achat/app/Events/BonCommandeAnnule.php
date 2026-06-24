<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Achat\Models\BonCommande;

class BonCommandeAnnule
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public BonCommande $bonCommande) {}
}
