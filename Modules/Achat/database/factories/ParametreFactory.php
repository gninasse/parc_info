<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\Parametre;

class ParametreFactory extends Factory
{
    protected $model = Parametre::class;

    public function definition(): array
    {
        return [
            'cle' => $this->faker->unique()->slug(2),
            'valeur' => (string) $this->faker->numberBetween(1, 100),
        ];
    }
}
