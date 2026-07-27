<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\LigneCommande;

class LigneCommandeFactory extends Factory
{
    protected $model = LigneCommande::class;

    public function definition(): array
    {
        return [
            'quantite' => $this->faker->numberBetween(1, 10),
            'prix_unitaire' => $this->faker->numberBetween(10000, 500000),
            'taux_tva' => 18,
            'quantite_livree' => 0,
        ];
    }
}
