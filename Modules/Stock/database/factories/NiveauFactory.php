<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;

class NiveauFactory extends Factory
{
    protected $model = Niveau::class;

    public function definition(): array
    {
        return [
            'magasin_id' => Magasin::factory(),
            'article_id' => Article::factory()->consommable(),
            'quantite' => 0,
            'seuil' => null,
        ];
    }

    public function avecQuantite(float $quantite): static
    {
        return $this->state(fn () => ['quantite' => $quantite]);
    }
}
