<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BordereauLivraison;

class BordereauLivraisonFactory extends Factory
{
    protected $model = BordereauLivraison::class;

    public function definition(): array
    {
        return [
            'numero_livraison' => sprintf('BL-%d-%s', date('Y'), str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT)),
            'date_livraison' => now()->toDateString(),
            'ref_bordereau_physique' => 'BLPHY-'.$this->faker->unique()->numberBetween(1000, 99999),
            'statut' => 'brouillon',
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => [
            'statut' => 'valide',
            'date_validation' => now(),
        ]);
    }
}
