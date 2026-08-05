<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;

class IntegrationReceptionFactory extends Factory
{
    protected $model = IntegrationReception::class;

    public function definition(): array
    {
        return [
            'bon_commande_id' => BonCommande::factory()->valide(),
            'sens' => IntegrationReception::SENS_RECEPTION,
            'entree_id' => $this->faker->unique()->numberBetween(1, 100000),
            'mouvement_id' => null,
            'reference' => 'ENT-'.now()->year.'-'.str_pad((string) $this->faker->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'detail' => [],
        ];
    }

    public function contrePassation(): static
    {
        return $this->state(fn () => [
            'sens' => IntegrationReception::SENS_CONTRE_PASSATION,
            'entree_id' => null,
            'mouvement_id' => $this->faker->unique()->numberBetween(1, 100000),
        ]);
    }
}
