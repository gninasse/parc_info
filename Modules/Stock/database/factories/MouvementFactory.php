<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;

/**
 * Réservée aux tests de contraintes et de lecture : en application, seul
 * MouvementService écrit des mouvements (S2) — il maintient aussi les
 * niveaux, ce que cette factory ne fait pas.
 */
class MouvementFactory extends Factory
{
    protected $model = Mouvement::class;

    public function definition(): array
    {
        return [
            'entree_id' => Entree::factory()->validee(),
            'magasin_id' => Magasin::factory(),
            'type' => Mouvement::TYPE_ENTREE,
            'sens' => Mouvement::SENS_ENTREE,
            'article_id' => Article::factory()->consommable(),
            'quantite' => $this->faker->numberBetween(1, 10),
            'created_at' => now(),
        ];
    }
}
