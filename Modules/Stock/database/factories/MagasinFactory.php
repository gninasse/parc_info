<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Magasin;

class MagasinFactory extends Factory
{
    protected $model = Magasin::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->bothify('MAG-##'),
            'nom' => $this->faker->word().' Warehouse',
            'type' => $this->faker->randomElement(['TECHNIQUE', 'CONSOMMABLE', 'REBUT']),
            'description' => $this->faker->sentence(),
            'est_actif' => true,
        ];
    }
}
