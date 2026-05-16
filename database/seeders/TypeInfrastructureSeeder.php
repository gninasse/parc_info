<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\ParcInfo\Models\TypeInfrastructure;

class TypeInfrastructureSeeder extends Seeder
{
    public function run(): void
    {
        $types = ['Onduleur', 'Baie & Rack', 'Brassage'];
        foreach ($types as $type) {
            TypeInfrastructure::firstOrCreate(['libelle' => $type]);
        }
    }
}
