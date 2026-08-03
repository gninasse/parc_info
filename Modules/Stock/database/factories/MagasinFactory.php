<?php

namespace Modules\Stock\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Organisation\Models\Site;
use Modules\Stock\Models\Magasin;

class MagasinFactory extends Factory
{
    protected $model = Magasin::class;

    public function definition(): array
    {
        // Un magasin par site (D2, site_id unique) : chaque magasin de test
        // naît avec son propre site — Organisation n'a pas de factory.
        $site = Site::query()->create([
            'code' => 'SITE-TEST-'.strtoupper($this->faker->unique()->bothify('??##')),
            'libelle' => 'Site '.$this->faker->unique()->city(),
        ]);

        return [
            'code' => 'MAG-'.$site->code,
            'libelle' => 'Magasin '.$site->libelle,
            'site_id' => $site->id,
            'est_actif' => true,
        ];
    }

    public function inactif(): static
    {
        return $this->state(fn () => ['est_actif' => false]);
    }
}
