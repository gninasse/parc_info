<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\TamponEquipement;

class TamponEquipementFactory extends Factory
{
    protected $model = TamponEquipement::class;

    public function definition(): array
    {
        // Par défaut : référencement d'entrée (n° de série saisi, unité pas encore créée)
        return [
            'ligne_entree_id' => LigneEntree::factory(),
            'numero_serie' => 'SN-'.strtoupper($this->faker->unique()->bothify('????####')),
        ];
    }

    public function pointageSortie(): static
    {
        return $this->state(fn () => [
            'ligne_entree_id' => null,
            'ligne_sortie_id' => LigneSortie::factory(),
            'numero_serie' => null,
            'equipement_id' => ParcInfoDeTest::equipement()->id,
        ]);
    }
}
