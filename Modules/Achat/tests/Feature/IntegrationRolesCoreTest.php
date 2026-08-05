<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\Role;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Intégration à la gestion des rôles de Core.
 *
 * Les permissions du module ne vivent pas seulement dans son code : elles
 * sont administrées depuis les écrans Core (liste des rôles, matrice
 * rôles × permissions). Ces tests vérifient que le module s'y présente
 * correctement — module renseigné, libellé lisible, catégorie exploitable —
 * et que la matrice affiche la séparation commande/visa du SFD §5.
 */
class IntegrationRolesCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    private ?User $superAdmin = null;

    /** Mémorisé : plusieurs appels dans un même test doivent rendre le même compte. */
    private function superAdmin(): User
    {
        if ($this->superAdmin !== null) {
            return $this->superAdmin;
        }

        $user = User::create([
            'name' => 'Admin', 'last_name' => 'Test', 'user_name' => 'admin_achat',
            'email' => 'admin@example.com', 'password' => bcrypt('password'),
        ]);

        $user->assignRole(Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']));

        return $this->superAdmin = $user;
    }

    // ── Présentation dans les écrans Core ──────────────────────────────────

    /**
     * La matrice Core filtre par `module` : sans cette colonne, les
     * permissions du module seraient invisibles dans l'administration.
     */
    public function test_toutes_les_permissions_portent_le_module_achat(): void
    {
        $permissions = Permission::where('name', 'LIKE', 'achat.%')->get();

        $this->assertCount(20, $permissions);

        foreach ($permissions as $permission) {
            $this->assertSame('achat', $permission->module, "Module absent sur {$permission->name}");
        }
    }

    /** Le libellé est ce que lit l'administrateur : jamais le nom technique seul. */
    public function test_chaque_permission_a_un_libelle_lisible(): void
    {
        foreach (Permission::where('module', 'achat')->get() as $permission) {
            $this->assertNotEmpty($permission->label, "Libellé absent sur {$permission->name}");
            $this->assertNotSame($permission->name, $permission->label);
        }
    }

    /** La catégorie est dérivée par PermissionService : elle doit être posée. */
    public function test_chaque_permission_a_une_categorie(): void
    {
        foreach (Permission::where('module', 'achat')->get() as $permission) {
            $this->assertNotEmpty($permission->category, "Catégorie absente sur {$permission->name}");
        }
    }

    public function test_le_module_apparait_dans_la_liste_des_modules_de_la_matrice(): void
    {
        $modules = Permission::distinct()->pluck('module')->filter()->all();

        $this->assertContains('achat', $modules);
    }

    // ── Écrans Core réellement rendus ──────────────────────────────────────

    public function test_la_matrice_core_affiche_les_permissions_achat(): void
    {
        $reponse = $this->actingAs($this->superAdmin())
            ->get(route('cores.permissions.index', ['modules' => ['achat']]))
            ->assertOk();

        $reponse->assertSee('achat.bons_commande.valider');
        $reponse->assertSee('achat.rapports.signaux');
    }

    public function test_la_liste_des_roles_core_expose_les_roles_achat(): void
    {
        $roles = collect(
            $this->actingAs($this->superAdmin())
                ->getJson(route('cores.roles.data', ['limit' => 100]))
                ->assertOk()
                ->json('rows')
        )->pluck('name');

        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $role) {
            $this->assertContains($role, $roles->all());
        }
    }

    /**
     * L'écran Core permet d'accorder une permission à un rôle : la bascule
     * doit fonctionner sur les permissions du module, sinon l'administration
     * serait en lecture seule pour Achat.
     */
    public function test_la_bascule_core_accorde_et_revoque_une_permission_achat(): void
    {
        $role = Role::findByName('Consultation Achat');
        $permission = Permission::findByName('achat.rapports.signaux');

        $this->assertFalse($role->hasPermissionTo($permission));

        $this->actingAs($this->superAdmin())
            ->postJson(route('cores.permissions.toggle'), [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'attach' => true,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertTrue($role->fresh()->hasPermissionTo($permission));

        $this->actingAs($this->superAdmin())
            ->postJson(route('cores.permissions.toggle'), [
                'role_id' => $role->id,
                'permission_id' => $permission->id,
                'attach' => false,
            ])
            ->assertOk();

        $this->assertFalse($role->fresh()->hasPermissionTo($permission));
    }

    // ── La matrice reflète la doctrine du SFD §5 ───────────────────────────

    /**
     * RGC-11 — aucun rôle par défaut ne cumule la saisie et le visa : la
     * séparation est structurelle, pas une consigne d'usage.
     */
    public function test_aucun_role_par_defaut_ne_cumule_saisie_et_visa(): void
    {
        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $nom) {
            $role = Role::findByName($nom);

            $cumul = $role->hasPermissionTo('achat.bons_commande.store')
                && $role->hasPermissionTo('achat.bons_commande.valider');

            $this->assertFalse($cumul, "{$nom} cumule la création et le visa (RGC-11).");
        }
    }

    /** Un utilisateur cumulant les deux rôles est possible, mais c'est un acte explicite. */
    public function test_le_cumul_reste_possible_mais_resulte_d_un_choix_explicite(): void
    {
        $user = User::create([
            'name' => 'Cumul', 'last_name' => 'Test', 'user_name' => 'cumul',
            'email' => 'cumul@example.com', 'password' => bcrypt('password'),
        ]);

        $user->assignRole('Acheteur');
        $this->assertFalse($user->can('achat.bons_commande.valider'));

        $user->assignRole('Validateur Achat');
        $this->assertTrue($user->fresh()->can('achat.bons_commande.valider'));
        $this->assertTrue($user->fresh()->can('achat.bons_commande.store'));
    }

    /** Le module doit rester accessible via le trait Core HasModulePermissions. */
    public function test_l_acces_au_module_est_detecte_par_le_trait_core(): void
    {
        $user = User::create([
            'name' => 'Acheteur', 'last_name' => 'Test', 'user_name' => 'acheteur',
            'email' => 'acheteur@example.com', 'password' => bcrypt('password'),
        ]);
        $user->assignRole('Acheteur');

        $this->assertTrue($user->hasModuleAccess('achat'));
        $this->assertContains('achat', $user->getAccessibleModules()->all());
        $this->assertNotEmpty($user->getModulePermissions('achat'));
    }

    /**
     * Le super-admin passe par la Gate de Core : il ne doit jamais être
     * bloqué par une permission Achat manquante.
     */
    public function test_le_super_admin_accede_au_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('achat.dashboard'))
            ->assertOk();
    }
}
