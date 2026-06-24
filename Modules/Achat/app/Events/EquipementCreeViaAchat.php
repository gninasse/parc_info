<?php

namespace Modules\Achat\Events;

use Illuminate\Queue\SerializesModels;
use Modules\ParcInfo\Models\Equipement;

class EquipementCreeViaAchat
{
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Equipement $equipement) {}
}
