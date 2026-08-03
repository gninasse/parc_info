<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Sortie;

class SortieFactory extends Factory
{
    protected $model = Sortie::class;

    public function definition(): array
    {
        return [
            'date_document' => now()->toDateString(),
            'statut' => Sortie::STATUT_BROUILLON,
            'magasin_id' => Magasin::factory(),
            'motif_type' => 'dotation_periodique',
            'beneficiaire_type' => 'service',
            'beneficiaire_libelle' => 'Service de test',
        ];
    }

    public function enPointage(): static
    {
        return $this->state(fn () => ['statut' => Sortie::STATUT_POINTAGE]);
    }

    public function validee(): static
    {
        return $this->state(fn () => [
            'statut' => Sortie::STATUT_VALIDE,
            'numero' => 'SOR-'.now()->year.'-'.str_pad((string) $this->faker->unique()->numberBetween(9000, 9999), 4, '0', STR_PAD_LEFT),
            'valide_le' => now(),
        ]);
    }

    public function annulee(): static
    {
        return $this->state(fn () => ['statut' => Sortie::STATUT_ANNULE]);
    }
}
