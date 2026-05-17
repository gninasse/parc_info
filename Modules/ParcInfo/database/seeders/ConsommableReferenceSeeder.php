<?php

namespace Modules\ParcInfo\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeConsommable;

class ConsommableReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'code' => 'TONER',
                'nom' => 'Toner Imprimante',
                'categorie' => 'Impression',
                'sous_categorie' => 'Encre',
                'unite_stock' => 'Cartouche',
                'seul_reapprovisionnement' => 5,
            ],
            [
                'code' => 'PAPIER-A4',
                'nom' => 'Papier A4 80g',
                'categorie' => 'Fournitures Bureau',
                'sous_categorie' => 'Papier',
                'unite_stock' => 'Rame',
                'seul_reapprovisionnement' => 20,
            ],
            [
                'code' => 'RJ45-3M',
                'nom' => 'Câble RJ45 3m',
                'categorie' => 'Reseau',
                'sous_categorie' => 'Câblage',
                'unite_stock' => 'Unité',
                'seul_reapprovisionnement' => 10,
            ],
            [
                'code' => 'BAT-UPS',
                'nom' => 'Batterie Onduleur 12V 7Ah',
                'categorie' => 'Maintenance',
                'sous_categorie' => 'Batteries',
                'unite_stock' => 'Unité',
                'seul_reapprovisionnement' => 2,
            ],
        ];

        foreach ($types as $type) {
            TypeConsommable::updateOrCreate(['code' => $type['code']], $type);
        }

        // Marque si besoin pour tests
        Marque::firstOrCreate(['libelle' => 'HP']);
        Marque::firstOrCreate(['libelle' => 'Brother']);
        Marque::firstOrCreate(['libelle' => 'Generic']);
    }
}
