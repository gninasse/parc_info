<?php

namespace Modules\Achat\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Achat\Models\ReceptionLicences;
use Modules\Achat\Models\TamponLicence;

class TamponLicenceFactory extends Factory
{
    protected $model = TamponLicence::class;

    public function definition(): array
    {
        return [
            'reception_id' => ReceptionLicences::factory(),
            'cle' => strtoupper($this->faker->unique()->bothify('????-####-????-####')),
            'date_activation' => now()->toDateString(),
            'date_expiration' => now()->addYear()->toDateString(),
        ];
    }
}
