<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permissions et rôles du module Achat.
 *
 * EF-ADM-05 — Toutes les permissions déclarées sont effectivement contrôlées
 * dans le code. La lecture se fait sur le format plat de config/permissions.php,
 * identique à celui attendu par Core\Services\PermissionService.
 */
class PermissionsAchatSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->creerPermissions();
        $this->creerRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function creerPermissions(): void
    {
        foreach (config('achat.permissions', []) as $nom => $libelle) {
            $segments = explode('.', $nom);

            Permission::updateOrCreate(
                ['name' => $nom, 'guard_name' => 'web'],
                [
                    'label' => $libelle,
                    'description' => $libelle,
                    'module' => 'achat',
                    'category' => end($segments),
                    'group' => $segments[1] ?? 'general',
                    'is_visible' => true,
                    'sort_order' => 0,
                ]
            );
        }
    }

    protected function creerRoles(): void
    {
        $permissionsDuModule = Permission::where('module', 'achat')->get();

        // Administrateur : accès complet au module.
        Role::findOrCreate('Admin')->givePermissionTo($permissionsDuModule);

        foreach (config('achat.roles', []) as $definition) {
            $role = Role::findOrCreate($definition['name']);

            if (isset($definition['description'])) {
                $role->forceFill(['description' => $definition['description']])->save();
            }

            $accordees = collect($definition['permissions'])
                ->flatMap(function (string $motif) use ($permissionsDuModule) {
                    if (str_ends_with($motif, '.*')) {
                        $prefixe = substr($motif, 0, -1); // conserve le point final

                        return $permissionsDuModule->filter(
                            fn (Permission $p) => str_starts_with($p->name, $prefixe)
                        );
                    }

                    return $permissionsDuModule->where('name', $motif);
                })
                ->unique('id')
                ->values();

            // syncPermissions ne touche qu'aux permissions du module : un rôle
            // partagé avec d'autres modules conserve les siennes.
            $autresModules = $role->permissions()->where('module', '!=', 'achat')->get();

            $role->syncPermissions($accordees->merge($autresModules));
        }
    }
}
