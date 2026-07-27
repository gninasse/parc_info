<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Magasin;

class MagasinFactory extends Factory
{
    protected $model = Magasin::class;

    public function definition(): array
    {
        return [
            'code' => 'MAG-'.str_pad((string) $this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'libelle' => 'Magasin '.$this->faker->city(),
            'statut' => 'actif',
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['statut' => 'inactif']);
    }
}
