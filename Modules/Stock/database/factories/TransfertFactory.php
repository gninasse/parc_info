<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Transfert;

class TransfertFactory extends Factory
{
    protected $model = Transfert::class;

    public function definition(): array
    {
        return [
            'date_document' => now()->toDateString(),
            'statut' => Transfert::STATUT_BROUILLON,
            'magasin_source_id' => Magasin::factory(),
            'magasin_cible_id' => Magasin::factory(),
        ];
    }

    public function enPointage(): static
    {
        return $this->state(fn () => ['statut' => Transfert::STATUT_POINTAGE]);
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => Transfert::STATUT_VALIDE,
            'numero' => 'TRF-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(9000, 9999), 4, '0', STR_PAD_LEFT),
            'valide_le' => now(),
        ]);
    }
}
