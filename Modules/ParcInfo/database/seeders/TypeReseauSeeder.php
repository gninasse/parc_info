<?php

namespace Modules\ParcInfo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ParcInfo\Models\TypeReseau;

class TypeReseauSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            'Switch Couche 2',
            'Switch Couche 3',
            'Routeur',
            'Pare-feu',
            'Hub réseau',
            'Point d\'accès WiFi',
            'Modem',
            'Convertisseur de média',
            'Agrégateur WAN',
            'Équipement PoE',
        ];

        foreach ($types as $type) {
            TypeReseau::firstOrCreate(['libelle' => $type]);
        }
    }
}
