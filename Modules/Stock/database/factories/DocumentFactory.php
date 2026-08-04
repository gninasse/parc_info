<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $nom = $this->faker->unique()->bothify('BL-####.pdf');

        return [
            'documentable_type' => Entree::class,
            'documentable_id' => Entree::factory(),
            'nom_original' => $nom,
            'chemin' => Document::DOSSIER.'/entrees/tests/'.$nom,
            'mime' => 'application/pdf',
            'taille' => $this->faker->numberBetween(20_000, 2_000_000),
        ];
    }
}
