<?php

namespace Modules\Grh\database\factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Grh\Models\Employe::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'matricule' => $this->faker->unique()->bothify('EMP###'),
            'nom' => $this->faker->lastName(),
            'prenom' => $this->faker->firstName(),
            'date_naissance' => $this->faker->date(),
            'genre' => $this->faker->randomElement(['M', 'F']),
            'date_embauche' => $this->faker->date(),
            'poste' => $this->faker->jobTitle(),
            'est_actif' => true,
            'niveau_rattachement' => 'direction',
        ];
    }
}
