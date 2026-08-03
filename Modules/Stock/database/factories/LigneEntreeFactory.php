<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;

class LigneEntreeFactory extends Factory
{
    protected $model = LigneEntree::class;

    public function definition(): array
    {
        return [
            'entree_id' => Entree::factory(),
            'article_id' => Article::factory()->consommable(),
            'equipement_id' => null,
            'quantite' => $this->faker->numberBetween(1, 20),
            'cout_unitaire' => $this->faker->numberBetween(1, 500) * 1000,
        ];
    }

    public function pourEquipement(): static
    {
        return $this->state(fn () => [
            'article_id' => null,
            'equipement_id' => ParcInfoDeTest::equipement()->id,
            'quantite' => 1,
        ]);
    }
}
