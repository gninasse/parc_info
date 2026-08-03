<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\Sortie;

class LigneSortieFactory extends Factory
{
    protected $model = LigneSortie::class;

    public function definition(): array
    {
        return [
            'sortie_id' => Sortie::factory(),
            'article_id' => Article::factory()->consommable(),
            'quantite' => $this->faker->numberBetween(1, 10),
        ];
    }
}
