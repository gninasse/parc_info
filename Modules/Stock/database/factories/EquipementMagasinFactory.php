<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;

class EquipementMagasinFactory extends Factory
{
    protected $model = EquipementMagasin::class;

    public function definition(): array
    {
        return [
            'equipement_id' => ParcInfoDeTest::equipement()->id,
            'magasin_id' => Magasin::factory(),
            'date_rattachement' => now(),
        ];
    }
}
