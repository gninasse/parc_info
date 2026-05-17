<?php

namespace Modules\ParcInfo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\ParcInfo\Models\Licence;

class VerifierExpirationLicences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Licences expirant dans 30 jours
        Licence::with(['logiciel', 'fournisseur'])
            ->whereDate('date_expiration', '<=', now()->addDays(30))
            ->where('date_expiration', '>=', now())
            ->where('actif', true)
            ->each(function ($licence) {
                if ($licence->statut !== 'en_alerte') {
                    $licence->update(['statut' => 'en_alerte']);

                    activity('licence')
                        ->performedOn($licence)
                        ->log('Passage en statut alerte (expiration proche)');
                }
            });

        // Licences expirées
        Licence::where('date_expiration', '<', now())
            ->where('actif', true)
            ->where('statut', '!=', 'expire')
            ->each(function ($licence) {
                $licence->update([
                    'statut' => 'expire',
                    'actif' => false,
                ]);

                activity('licence')
                    ->performedOn($licence)
                    ->log('Désactivation automatique (licence expirée)');
            });
    }
}
