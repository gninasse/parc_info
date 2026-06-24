<?php

namespace Modules\Achat\Listeners;

use Illuminate\Support\Facades\Log;

class NotifierUtilisateurs
{
    /**
     * Handle the event.
     */
    public function handle(mixed $event): void
    {
        // Log the event to standard Laravel logs for audit
        Log::info("Notification : Événement d'achat déclenché - ".get_class($event));
    }
}
