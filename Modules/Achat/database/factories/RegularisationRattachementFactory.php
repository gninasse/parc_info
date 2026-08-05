<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\RegularisationRattachement;

class RegularisationRattachementFactory extends Factory
{
    protected $model = RegularisationRattachement::class;

    public function definition(): array
    {
        return [
            'bon_commande_id' => BonCommande::factory()->regularisation(),
            'equipement_id' => null, // fourni par le test (ParcInfoDeTest)
        ];
    }
}
