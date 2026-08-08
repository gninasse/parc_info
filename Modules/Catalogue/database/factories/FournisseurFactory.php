<?php

namespace Modules\Catalogue\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class FournisseurFactory extends Factory
{
    protected $model = \Modules\Catalogue\Models\Fournisseur::class;

    public function definition(): array
    {
        return [
            'raison_sociale' => $this->faker->unique()->company(),
            'contact' => $this->faker->name(),
            'adresse' => 'Ouagadougou, '.$this->faker->streetAddress(),
            'telephone' => '+226 '.$this->faker->numerify('7# ## ## ##'),
            'email' => $this->faker->unique()->companyEmail(),
            'est_actif' => true,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
