<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\Magasin;

class InventaireFactory extends Factory
{
    protected $model = Inventaire::class;

    public function definition(): array
    {
        return [
            'date_document' => now()->toDateString(),
            'statut' => Inventaire::STATUT_EN_COURS,
            'magasin_id' => Magasin::factory(),
            'perimetre' => Inventaire::PERIMETRE_MAGASIN,
        ];
    }

    public function valide(): static
    {
        return $this->state(fn () => [
            'statut' => Inventaire::STATUT_VALIDE,
            'numero' => 'INV-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(9000, 9999), 4, '0', STR_PAD_LEFT),
            'motif_global' => 'Inventaire de test',
            'valide_le' => now(),
        ]);
    }
}
