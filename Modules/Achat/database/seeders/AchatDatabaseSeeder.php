<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;

class AchatDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            AchatPermissionsSeeder::class,
            AchatParametresSeeder::class,
        ]);
    }
}
