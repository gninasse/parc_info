<?php

namespace Modules\Catalogue\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\ContactFournisseur;
use Modules\Catalogue\Models\Fournisseur;

class ContactFournisseurFactory extends Factory
{
    protected $model = ContactFournisseur::class;

    public function definition(): array
    {
        return [
            'fournisseur_id' => Fournisseur::factory(),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'fonction' => $this->faker->randomElement(['Commercial', 'SAV', 'Comptabilité', 'Direction']),
            'telephone' => $this->faker->numerify('+226 ## ## ## ##'),
            'email' => $this->faker->safeEmail(),
            'notes' => null,
            'est_principal' => false,
            'est_actif' => true,
        ];
    }

    public function principal(): static
    {
        return $this->state(fn () => ['est_principal' => true]);
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['est_actif' => false]);
    }
}
