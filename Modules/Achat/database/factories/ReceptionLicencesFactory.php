<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\ReceptionLicences;

class ReceptionLicencesFactory extends Factory
{
    protected $model = ReceptionLicences::class;

    public function definition(): array
    {
        return [
            'ligne_commande_id' => LigneCommande::factory()->licence(),
            'quantite' => $this->faker->numberBetween(1, 25),
            'statut' => ReceptionLicences::STATUT_EN_COURS,
        ];
    }

    public function finalisee(): static
    {
        return $this->state(fn () => [
            'statut' => ReceptionLicences::STATUT_FINALISEE,
            'finalisee_le' => now(),
        ]);
    }

    public function abandonnee(): static
    {
        return $this->state(fn () => ['statut' => ReceptionLicences::STATUT_ABANDONNEE]);
    }
}
