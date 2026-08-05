<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Achat\Models\Parametre;

/**
 * Paramètres métier initiaux (SFD §6.2) — idempotent : ne réécrit jamais une
 * valeur déjà administrée depuis l'écran A-08.
 *
 * `regularisation_active` est semé à FAUX : la porte de régularisation ne
 * s'ouvre que par un acte d'administration explicite, avec ses bornes
 * d'intérim (A15/IA-11).
 */
class AchatParametresSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('achat.parametres_defaut', []) as $cle => $valeur) {
            Parametre::query()->firstOrCreate(['cle' => $cle], ['valeur' => $valeur]);
        }
    }
}
