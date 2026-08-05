<?php

namespace Modules\Achat\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Services\PermissionService;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Synchronise les permissions du module (config/permissions.php) puis crée
 * les rôles du SFD §5 et leur attache les permissions. Idempotent : rejouable
 * sans doublon ni perte.
 */
class AchatPermissionsSeeder extends Seeder
{
    /**
     * Acheteur (SFD §5) : saisit, soumet, réceptionne les licences,
     * régularise, joint les pièces. Ne valide pas — la séparation
     * commande/visa est garantie par les permissions (RGC-11).
     */
    private const ACHETEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.update',
        'achat.bons_commande.destroy',
        'achat.bons_commande.soumettre',
        'achat.bons_commande.regulariser',
        'achat.licences.receptionner',
        'achat.documents.view',
        'achat.documents.store',
        'achat.reliquats.index',
        'achat.rapports.view',
    ];

    /** Validateur Achat : l'Acheteur en lecture + les actes de visa. */
    private const VALIDATEUR = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.valider',
        'achat.bons_commande.annuler',
        'achat.bons_commande.cloturer',
        'achat.documents.view',
        'achat.documents.delete',
        'achat.reliquats.index',
        'achat.rapports.view',
        'achat.rapports.export',
    ];

    /** Consultation Achat : lecture et rapports. */
    private const CONSULTATION = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.reliquats.index',
        'achat.rapports.view',
        'achat.rapports.export',
    ];

    /**
     * Les rôles Achat interrogent le Catalogue (articles, fournisseurs) et le
     * Stock (bons d'entrée liés) — SFD §5.
     */
    private const API_EXTERNES = ['catalogue.api.view', 'stock.api.view'];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        app(PermissionService::class)->syncModulePermissions('achat');

        $this->verifierDependances();

        $roles = [
            'Acheteur' => self::ACHETEUR,
            'Validateur Achat' => self::VALIDATEUR,
            'Consultation Achat' => self::CONSULTATION,
        ];

        foreach ($roles as $name => $permissions) {
            $role = Role::updateOrCreate(['name' => $name, 'guard_name' => 'web']);

            // syncPermissions ne retire que les permissions du module : un rôle
            // enrichi manuellement d'autres modules les conserve.
            $conservees = $role->permissions
                ->reject(fn ($p) => str_starts_with($p->name, 'achat.'))
                ->pluck('name')
                ->all();

            $role->syncPermissions(array_unique(array_merge(
                $conservees,
                $permissions,
                self::API_EXTERNES
            )));
        }

        // Réciproque du raccordement : les rôles Stock consultent les
        // commandes à livrer via l'API Achat (SFD §5).
        foreach (['Superviseur stock', 'Magasinier', 'Consultation stock'] as $roleStock) {
            Role::where('name', $roleStock)->first()?->givePermissionTo('achat.api.view');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Le module dépend du Catalogue et du Stock : sans leurs permissions
     * d'API, les rôles Achat seraient créés muets. On échoue tôt et en clair
     * plutôt que de produire une installation à moitié fonctionnelle.
     */
    private function verifierDependances(): void
    {
        foreach (self::API_EXTERNES as $permission) {
            if (! Permission::where('name', $permission)->exists()) {
                $module = str_contains($permission, 'catalogue') ? 'Catalogue' : 'Stock';

                throw new RuntimeException(
                    "La permission « {$permission} » est introuvable : le module Achat dépend du module {$module}. "
                    ."Installez et seedez le module {$module} d'abord (php artisan module:seed {$module})."
                );
            }
        }
    }
}
