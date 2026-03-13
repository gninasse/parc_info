<?php

namespace Modules\Core\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;
class SeedPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les permissions de base
        $permissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
            'dashboard.view'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Créer le rôle Admin et lui assigner toutes les permissions
        $roleAdmin = Role::findOrCreate('Admin');
        $roleAdmin->givePermissionTo(Permission::all());

        // Créer l'utilisateur Admin par défaut
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Administrateur',
                'password' => bcrypt('azerty'),
            ]
        );
        $admin->assignRole($roleAdmin);
    }
}
