<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;

class StockDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsStockSeeder::class,
            MagasinSeeder::class,
        ]);
    }
}
