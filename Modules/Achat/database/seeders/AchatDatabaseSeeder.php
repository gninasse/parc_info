<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;

class AchatDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsAchatSeeder::class,
            ParametresSeeder::class,
        ]);

        // ArticlesSeeder n'est pas appelé automatiquement : il alimente le
        // catalogue avec des données de démonstration.
        // Lancer explicitement : php artisan db:seed --class="Modules\Achat\Database\Seeders\ArticlesSeeder"
    }
}
