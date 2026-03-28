<?php

namespace Modules\Grh\Database\Factories;

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
        return [];
    }
}
