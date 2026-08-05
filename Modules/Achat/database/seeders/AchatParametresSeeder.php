<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Achat\Models\Parametre;
use Modules\Achat\Services\AchatParametres;

/**
 * Paramètres métier initiaux (SFD §6.2) — idempotent : ne réécrit jamais une
 * valeur déjà administrée depuis l'écran A-08.
 *
 * `regularisation_active` est semé à VRAI : le plan de mise en service
 * (SFD §9.2) prévoit la saisie des BC d'intérim en « semaine 0 », avant
 * l'ouverture générale. La porte se refermera seule à dette zéro (extinction
 * automatique A15), et sa réouverture sera un acte d'administration journalisé.
 */
class AchatParametresSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('achat.parametres_defaut', []) as $cle => $valeur) {
            Parametre::query()->firstOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }

        // Le seeder écrit en base sans passer par le service : on invalide le
        // cache, sinon une lecture antérieure resterait servie pendant l'heure.
        app(AchatParametres::class)->oublierTout();
    }
}
