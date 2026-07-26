<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Achat\Models\BonCommande;

/**
 * EF-BC-18 — Reliquat d'un bon partiellement livré abandonné puis clôturé.
 */
class BonCommandeCloture
{
    use SerializesModels;

    public function __construct(
        public readonly BonCommande $bonCommande,
        public readonly int $userId,
    ) {}
}
