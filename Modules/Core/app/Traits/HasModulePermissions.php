<?php

namespace Modules\Core\Traits;

use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

trait HasModulePermissions
{
    /**
     * Vérifier si l'utilisateur a accès à un module
     */
    public function hasModuleAccess(string $module): bool
    {
        $permissions = Permission::where('module', $module)->pluck('name');

        foreach ($permissions as $permission) {
            if ($this->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtenir tous les modules accessibles
     */
    public function getAccessibleModules(): Collection
    {
        return $this->getAllPermissions()
            ->pluck('module')
            ->unique()
            ->filter()
            ->values();
    }

    /**
     * Obtenir les permissions d'un module spécifique
     */
    public function getModulePermissions(string $module): Collection
    {
        return $this->getAllPermissions()
            ->where('module', $module)
            ->pluck('name');
    }

    /**
     * Vérifier si l'utilisateur a toutes les permissions d'un module
     */
    public function hasAllModulePermissions(string $module): bool
    {
        $modulePermissions = Permission::where('module', $module)->get();

        foreach ($modulePermissions as $permission) {
            if (! $this->can($permission->name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assigner toutes les permissions d'un module
     */
    public function giveModulePermissions(string $module): void
    {
        $permissions = Permission::where('module', $module)->get();
        $this->givePermissionTo($permissions);
    }

    /**
     * Retirer toutes les permissions d'un module
     */
    public function revokeModulePermissions(string $module): void
    {
        $permissions = Permission::where('module', $module)->pluck('name');
        $this->revokePermissionTo($permissions);
    }

    /**
     * Vérifier si l'utilisateur peut accéder à une ressource du module
     */
    public function canAccessModuleResource(string $module, string $resource, string $action): bool
    {
        return $this->can("{$module}.{$resource}.{$action}");
    }

    /**
     * Obtenir un menu de navigation basé sur les modules accessibles.
     *
     * Chaque module déclare une clé `navigation` dans son config/config.php
     * (publiée sous config('{module_minuscule}.navigation')) : liste d'items
     * {label, icon, route, permission}. Seuls les items dont la route existe
     * et dont la permission est accordée sont retournés.
     */
    public function getModuleNavigation(): array
    {
        $navigation = [];

        foreach (array_keys(\Nwidart\Modules\Facades\Module::allEnabled()) as $module) {
            $items = config(strtolower((string) $module).'.navigation');

            if (! $items) {
                continue;
            }

            $visibles = array_values(array_filter(
                $items,
                fn (array $item) => \Illuminate\Support\Facades\Route::has($item['route'] ?? '')
                    && (empty($item['permission']) || $this->can($item['permission']))
            ));

            if ($visibles !== []) {
                $navigation[strtolower((string) $module)] = $visibles;
            }
        }

        return $navigation;
    }
}
