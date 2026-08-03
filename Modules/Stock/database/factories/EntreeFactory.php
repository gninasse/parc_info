<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Magasin;

class EntreeFactory extends Factory
{
    protected $model = Entree::class;

    public function definition(): array
    {
        return [
            'date_document' => now()->toDateString(),
            'statut' => Entree::STATUT_BROUILLON,
            'magasin_id' => Magasin::factory(),
            'nature' => Entree::NATURE_LIVRAISON,
            'observation_type' => 'livraison_conforme',
        ];
    }

    public function enReferencement(): static
    {
        return $this->state(fn () => ['statut' => Entree::STATUT_REFERENCEMENT]);
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => Entree::STATUT_VALIDE,
            'numero' => 'ENT-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(9000, 9999), 4, '0', STR_PAD_LEFT),
            'valide_le' => now(),
        ]);
    }

    public function annulee(): static
    {
        return $this->state(fn () => ['statut' => Entree::STATUT_ANNULE]);
    }

    public function retour(): static
    {
        return $this->state(fn () => ['nature' => Entree::NATURE_RETOUR, 'observation_type' => null]);
    }
}
