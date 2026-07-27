<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Stock\Models\Magasin;

class MagasinSeeder extends Seeder
{
    public function run(): void
    {
        // Magasin de réception des entrées automatiques Achat (config
        // stock.magasin_reception_defaut) : jamais créé à la volée.
        Magasin::firstOrCreate(
            ['code' => config('stock.magasin_reception_defaut', 'MAG-PRINCIPAL')],
            [
                'libelle' => 'Magasin principal',
                'description' => 'Magasin de réception par défaut des livraisons du module Achat.',
                'statut' => 'actif',
            ]
        );
    }
}
