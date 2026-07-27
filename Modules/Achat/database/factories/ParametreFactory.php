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
            'valeur' => $this->faker->word(),
            'libelle' => $this->faker->sentence(3),
            'type_valeur' => 'string',
            'modifiable' => true,
        ];
    }
}
