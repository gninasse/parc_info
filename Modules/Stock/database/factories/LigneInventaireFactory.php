<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\LigneInventaire;

class LigneInventaireFactory extends Factory
{
    protected $model = LigneInventaire::class;

    public function definition(): array
    {
        return [
            'inventaire_id' => Inventaire::factory(),
            'article_id' => Article::factory()->consommable(),
            'quantite_theorique' => $this->faker->numberBetween(0, 30),
        ];
    }

    public function pourEquipement(): static
    {
        return $this->state(fn () => [
            'article_id' => null,
            'equipement_id' => ParcInfoDeTest::equipement()->id,
            'quantite_theorique' => 1,
        ]);
    }
}
