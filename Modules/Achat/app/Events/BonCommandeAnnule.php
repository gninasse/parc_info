<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Achat\Models\BonCommande;

class BonCommandeAnnule
{
    use SerializesModels;

    public function __construct(
        public readonly BonCommande $bonCommande,
        public readonly int $userId,
    ) {}
}
