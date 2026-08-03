<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\LigneTransfert;
use Modules\Stock\Models\Transfert;

class LigneTransfertFactory extends Factory
{
    protected $model = LigneTransfert::class;

    public function definition(): array
    {
        return [
            'transfert_id' => Transfert::factory(),
            'article_id' => Article::factory()->consommable(),
            'quantite' => $this->faker->numberBetween(1, 10),
        ];
    }
}
