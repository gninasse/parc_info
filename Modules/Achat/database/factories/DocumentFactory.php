<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'bon_commande_id' => BonCommande::factory(),
            'type' => 'bc_signe',
            'chemin' => 'achat/documents/'.$this->faker->uuid().'.pdf',
            'nom_original' => 'bon-signe.pdf',
            'mime' => 'application/pdf',
            'taille' => $this->faker->numberBetween(1024, 512000),
            'est_supprime' => false,
        ];
    }

    /**
     * Pierre tombale : le fichier est effacé, la ligne reste, motivée et
     * signée (A16). Le CHECK en base impose motif ET horodatage.
     */
    public function pierreTombale(string $motif = 'Pièce erronée (test)'): static
    {
        return $this->state(fn () => [
            'chemin' => null,
            'est_supprime' => true,
            'supprime_le' => now(),
            'motif_suppression' => $motif,
        ]);
    }
}
