<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\BonCommande;
use Modules\Catalogue\Models\Fournisseur;

class BonCommandeFactory extends Factory
{
    protected $model = BonCommande::class;

    public function definition(): array
    {
        return [
            'statut' => BonCommande::STATUT_BROUILLON,
            'fournisseur_id' => Fournisseur::factory(),
            'date_document' => now()->toDateString(),
            'est_regularisation' => false,
        ];
    }

    /** État par défaut, nommé pour les jeux de données de test. */
    public function brouillon(): static
    {
        return $this->state(fn () => ['statut' => BonCommande::STATUT_BROUILLON]);
    }

    public function soumis(): static
    {
        return $this->state(fn () => [
            'statut' => BonCommande::STATUT_SOUMIS,
            'soumis_le' => now(),
        ]);
    }

    /**
     * Un bon engagé porte TOUJOURS un numéro (CHECK en base) : les états
     * validés de la factory doivent donc en produire un.
     */
    public function valide(): static
    {
        return $this->state(fn () => [
            'statut' => BonCommande::STATUT_VALIDE,
            'numero' => $this->numeroDeTest(),
            'fournisseur_libelle' => 'Fournisseur de test',
            'valide_le' => now(),
        ]);
    }

    public function partiel(): static
    {
        return $this->valide()->state(fn () => ['statut' => BonCommande::STATUT_PARTIEL]);
    }

    public function livre(): static
    {
        return $this->valide()->state(fn () => ['statut' => BonCommande::STATUT_LIVRE]);
    }

    public function cloture(string $motif = 'Reliquat abandonné (test)'): static
    {
        return $this->valide()->state(fn () => [
            'statut' => BonCommande::STATUT_CLOTURE,
            'motif_cloture' => $motif,
        ]);
    }

    /** Un bon annulé n'a jamais reçu de numéro (SFD §1.4). */
    public function annule(string $motif = 'Annulé (test)'): static
    {
        return $this->state(fn () => [
            'statut' => BonCommande::STATUT_ANNULE,
            'numero' => null,
            'motif_annulation' => $motif,
        ]);
    }

    public function regularisation(): static
    {
        return $this->state(fn () => ['est_regularisation' => true]);
    }

    private function numeroDeTest(): string
    {
        return 'BC-'.now()->year.'-'.str_pad(
            (string) $this->faker->unique()->numberBetween(9000, 9999),
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
