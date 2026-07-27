<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\LigneLivraison;

class LigneLivraisonFactory extends Factory
{
    protected $model = LigneLivraison::class;

    public function definition(): array
    {
        return [
            'quantite_livree' => $this->faker->numberBetween(1, 10),
            'quantite_refusee' => 0,
        ];
    }
}
