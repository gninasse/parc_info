<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\Article;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'code_article' => 'ART-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'designation' => $this->faker->words(3, true),
            'type_article' => 'equipement',
            'prix_indicatif' => $this->faker->numberBetween(10000, 500000),
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'actif' => true,
        ];
    }

    public function consommable(): static
    {
        return $this->state(fn () => [
            'type_article' => 'consommable',
            'categorie_equipement_id' => null,
            'prix_indicatif' => $this->faker->numberBetween(1000, 20000),
        ]);
    }

    public function licence(): static
    {
        return $this->state(fn () => [
            'type_article' => 'licence',
            'categorie_equipement_id' => null,
            'duree_validite_mois' => 12,
        ]);
    }

    public function prestation(): static
    {
        return $this->state(fn () => [
            'type_article' => 'prestation',
            'categorie_equipement_id' => null,
        ]);
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['actif' => false]);
    }
}
