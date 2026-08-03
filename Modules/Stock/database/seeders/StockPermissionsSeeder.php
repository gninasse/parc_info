<?php

namespace Modules\Stock\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Services\PermissionService;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class StockPermissionsSeeder extends Seeder
{
    /**
     * Réservées au Superviseur stock (SFD §5, TESTS §"rôles seedés" :
     * gestion du référentiel magasins, seuils, contre-mouvements,
     * validation d'inventaire).
     */
    private const RESERVEES_SUPERVISEUR = [
        'stock.magasins.store',
        'stock.magasins.update',
        'stock.magasins.destroy',
        'stock.magasins.toggle-status',
        'stock.niveaux.seuil',
        'stock.mouvements.contre',
        'stock.inventaires.valider',
    ];

    /**
     * Synchronise les permissions du module (config/permissions.php) puis
     * crée les rôles du SFD §5 et leur attache les permissions.
     * Idempotent : rejouable sans doublon ni perte.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionService::class)->syncModulePermissions('stock');

        // Les rôles Stock reçoivent aussi catalogue.api.view (SFD §5) : les
        // sélecteurs d'articles interrogent /catalogue/api/articles (S11).
        if (! Permission::where('name', 'catalogue.api.view')->exists()) {
            throw new RuntimeException(
                'La permission « catalogue.api.view » est introuvable : le module Stock dépend du Catalogue. '
                .'Installez et seedez le module Catalogue d\'abord (php artisan module:seed Catalogue).'
            );
        }

        $permissions = array_keys(require module_path('Stock', 'config/permissions.php'));

        $consultation = array_values(array_filter(
            $permissions,
            fn (string $name) => str_ends_with($name, '.index')
                || in_array($name, ['stock.dashboard.view', 'stock.rapports.view', 'stock.rapports.export', 'stock.api.view'], true)
        ));

        $magasinier = array_values(array_diff($permissions, self::RESERVEES_SUPERVISEUR));

        $roles = [
            'Consultation stock' => $consultation,
            'Magasinier' => $magasinier,
            'Superviseur stock' => $permissions,
        ];

        foreach ($roles as $name => $rolePermissions) {
            $role = Role::updateOrCreate(['name' => $name, 'guard_name' => 'web']);

            // syncPermissions ne retire que les permissions du module : un rôle
            // enrichi manuellement de permissions d'autres modules les conserve.
            $conservees = $role->permissions
                ->reject(fn ($p) => str_starts_with($p->name, 'stock.'))
                ->pluck('name')
                ->all();

            $role->syncPermissions(array_unique(array_merge($conservees, $rolePermissions, ['catalogue.api.view'])));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
