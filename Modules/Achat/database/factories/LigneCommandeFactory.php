<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;

class LigneCommandeFactory extends Factory
{
    protected $model = LigneCommande::class;

    public function definition(): array
    {
        return [
            'bon_commande_id' => BonCommande::factory(),
            'article_id' => Article::factory(),
            'designation' => ucfirst($this->faker->words(3, true)),
            'nature' => Article::NATURE_CONSOMMABLE,
            'prix_unitaire_ht' => $this->faker->numberBetween(1, 500) * 1000,
            'taux_tva' => 18.00,
            'quantite' => $this->faker->numberBetween(1, 20),
            'quantite_livree' => 0,
        ];
    }

    /** Ligne partiellement livrée : le reliquat est le cœur du module. */
    public function partiellementLivree(float $livree): static
    {
        return $this->state(fn (array $attributs) => [
            'quantite_livree' => min($livree, (float) $attributs['quantite']),
        ]);
    }

    public function soldee(): static
    {
        return $this->state(fn (array $attributs) => [
            'quantite_livree' => $attributs['quantite'],
        ]);
    }

    public function licence(): static
    {
        return $this->state(fn () => ['nature' => Article::NATURE_LICENCE]);
    }
}
