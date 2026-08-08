<?php

namespace Modules\Catalogue\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategorieFactory extends Factory
{
    protected $model = \Modules\Catalogue\Models\Categorie::class;

    public function definition(): array
    {
        return [
            'libelle' => ucfirst($this->faker->unique()->words(2, true)),
            'parent_id' => null,
            'est_actif' => true,
        ];
    }

    public function sousCategorieDe(\Modules\Catalogue\Models\Categorie $parent): static
    {
        return $this->state(fn () => ['parent_id' => $parent->id]);
    }
}
