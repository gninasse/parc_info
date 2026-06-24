<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsAchatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = config('achat.permissions.permissions', []);

        foreach ($permissions as $name => $label) {
            $parts = explode('.', $name);
            $category = count($parts) >= 3 ? end($parts) : null;
            $group = count($parts) >= 2 ? $parts[1] : 'general';

            Permission::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $label,
                    'module' => 'Achat',
                    'category' => $category,
                    'description' => $label,
                    'group' => $group,
                    'is_visible' => true,
                    'sort_order' => 0,
                ]
            );
        }

        // Assigner toutes les permissions Achat au rôle Admin
        $roleAdmin = Role::findOrCreate('Admin');
        $roleAdmin->givePermissionTo(Permission::where('module', 'Achat')->get());

        // Créer les rôles spécifiques et leur assigner les permissions configurées
        $roles = config('achat.permissions.roles', []);
        foreach ($roles as $roleData) {
            $role = Role::findOrCreate($roleData['name']);
            if (isset($roleData['description'])) {
                $role->update(['description' => $roleData['description']]);
            }

            $assignedPerms = [];
            foreach ($roleData['permissions'] as $permPattern) {
                if (str_ends_with($permPattern, '.*')) {
                    $prefix = substr($permPattern, 0, -2);
                    $rolePerms = Permission::where('name', 'like', $prefix.'.%')->get();
                    foreach ($rolePerms as $rp) {
                        $assignedPerms[] = $rp;
                    }
                } else {
                    $rolePerm = Permission::where('name', $permPattern)->first();
                    if ($rolePerm) {
                        $assignedPerms[] = $rolePerm;
                    }
                }
            }
            $role->syncPermissions($assignedPerms);
        }
    }
}
