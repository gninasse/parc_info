<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Services\PermissionService;

class PermissionsStockSeeder extends Seeder
{
    public function run(): void
    {
        // Le contrat de déclaration est config/permissions.php (format plat) ;
        // la synchronisation passe par le service Core, comme la commande
        // `php artisan cores:sync-permissions stock`.
        app(PermissionService::class)->syncModulePermissions('stock');
    }
}
