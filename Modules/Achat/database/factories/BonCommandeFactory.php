<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;

class BonCommandeFactory extends Factory
{
    protected $model = BonCommande::class;

    public function definition(): array
    {
        // La numérotation officielle passe par GeneratesDocumentNumbers dans le
        // service ; la factory pose un numéro unique arbitraire du même format.
        return [
            'numero_commande' => sprintf('BC-%d-%s', date('Y'), str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT)),
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
            'montant_ht' => 0,
            'montant_tva' => 0,
            'montant_ttc' => 0,
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
