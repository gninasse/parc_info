<?php

namespace Modules\Catalogue\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeLicence;

class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'nom' => ucfirst($this->faker->unique()->words(3, true)),
            'nature' => Article::NATURE_CONSOMMABLE,
            'categorie_id' => Categorie::factory(),
            'unite_stock' => $this->faker->randomElement(['unité', 'boîte', 'lot', 'cartouche']),
            'prix_indicatif' => $this->faker->numberBetween(5, 2500) * 1000, // FCFA
            'taux_tva' => 18.00,
            'seuil_defaut' => $this->faker->optional()->numberBetween(1, 20),
            'est_actif' => true,
        ];
    }

    public function consommable(): static
    {
        return $this->state(fn () => ['nature' => Article::NATURE_CONSOMMABLE]);
    }

    public function piece(): static
    {
        return $this->state(fn () => ['nature' => Article::NATURE_PIECE]);
    }

    public function equipement(): static
    {
        return $this->state(fn () => [
            'nature' => Article::NATURE_EQUIPEMENT,
            'categorie_equipement_id' => CategorieEquipement::firstOrCreate(
                ['code' => 'ORDI'],
                ['libelle' => 'Ordinateurs']
            )->id,
        ]);
    }

    public function licence(): static
    {
        return $this->state(fn () => [
            'nature' => Article::NATURE_LICENCE,
            'logiciel_id' => static::logicielDeReference()->id,
            'seuil_defaut' => null,
        ]);
    }

    public function avecMarque(): static
    {
        return $this->state(fn () => [
            'marque_id' => Marque::firstOrCreate(['libelle' => $this->faker->randomElement(['HP', 'Dell', 'Brother'])])->id,
            'reference_constructeur' => strtoupper($this->faker->unique()->bothify('REF-####??')),
        ]);
    }

    public static function logicielDeReference(): Logiciel
    {
        return Logiciel::firstOrCreate(
            ['code' => 'LOG-DEMO'],
            [
                'nom' => 'Logiciel de démonstration',
                'type_licence_id' => TypeLicence::firstOrCreate(
                    ['code' => 'PER'],
                    ['libelle' => 'Perpétuelle']
                )->id,
                'editeur_id' => Editeur::firstOrCreate(
                    ['code' => 'MS'],
                    ['nom' => 'Microsoft']
                )->id,
            ]
        );
    }
}
