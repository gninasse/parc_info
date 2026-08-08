<?php

namespace Modules\Catalogue\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Services\PermissionService;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CataloguePermissionsSeeder extends Seeder
{
    /**
     * Synchronise les permissions du module (config/permissions.php) puis
     * crée les rôles du SFD §5.3 et leur attache les permissions.
     * Idempotent : rejouable sans doublon ni perte.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionService::class)->syncModulePermissions('catalogue');

        $permissions = array_keys(require module_path('Catalogue', 'config/permissions.php'));

        $consultation = array_values(array_filter(
            $permissions,
            fn (string $name) => str_ends_with($name, '.index') || $name === 'catalogue.api.view'
        ));

        $gestion = array_values(array_filter(
            $permissions,
            fn (string $name) => ! str_ends_with($name, '.destroy')
        ));

        $roles = [
            'Consultation catalogue' => $consultation,
            'Gestionnaire catalogue' => $gestion,
            'Administrateur catalogue' => $permissions,
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::updateOrCreate(['name' => $name, 'guard_name' => 'web']);

            // syncPermissions ne retire que les permissions du module : un rôle
            // enrichi manuellement de permissions d'autres modules les conserve.
            $conservees = $role->permissions
                ->reject(fn ($p) => str_starts_with($p->name, 'catalogue.'))
                ->pluck('name')
                ->all();

            $role->syncPermissions(array_merge($conservees, $rolePermissions));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
