<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockMagasinsSeeder;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Modules\Stock\Models\Magasin;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockSkeletonTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Liste EXACTE du SFD §5 — toute divergence (manquante ou en trop) dans
     * config/permissions.php doit faire échouer la suite.
     */
    private const PERMISSIONS_SFD_5 = [
        'stock.dashboard.view',
        'stock.magasins.index',
        'stock.magasins.store',
        'stock.magasins.update',
        'stock.magasins.destroy',
        'stock.magasins.toggle-status',
        'stock.niveaux.index',
        'stock.niveaux.seuil',
        'stock.entrees.index',
        'stock.entrees.store',
        'stock.entrees.update',
        'stock.entrees.destroy',
        'stock.sorties.index',
        'stock.sorties.store',
        'stock.sorties.update',
        'stock.sorties.destroy',
        'stock.transferts.index',
        'stock.transferts.store',
        'stock.transferts.update',
        'stock.transferts.destroy',
        'stock.mouvements.index',
        'stock.mouvements.contre',
        'stock.inventaires.index',
        'stock.inventaires.store',
        'stock.inventaires.saisie',
        'stock.inventaires.valider',
        'stock.inventaires.annuler',
        'stock.rapports.view',
        'stock.rapports.export',
        'stock.api.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
    }

    private function makeUser(string $email = 'test@example.com'): User
    {
        return User::create([
            'name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);
    }

    public function test_declared_permissions_match_sfd_5_exactly(): void
    {
        $declarees = array_keys(require module_path('Stock', 'config/permissions.php'));

        sort($declarees);
        $attendues = self::PERMISSIONS_SFD_5;
        sort($attendues);

        $this->assertSame(
            $attendues,
            $declarees,
            'config/permissions.php diverge du SFD §5 (permission manquante ou en trop).'
        );
    }

    public function test_seeder_creates_the_thirty_permissions_with_module_stock(): void
    {
        $this->assertSame(
            count(self::PERMISSIONS_SFD_5),
            Permission::where('module', 'stock')
                ->whereIn('name', self::PERMISSIONS_SFD_5)->count()
        );
    }

    public function test_seeder_creates_the_three_roles_with_sfd_distribution(): void
    {
        // Toutes les permissions Stock + catalogue.api.view (S11)
        $superviseur = Role::findByName('Superviseur stock');
        $this->assertSame(31, $superviseur->permissions->count());

        // Tout sauf les 7 réservées superviseur (magasins ×4, seuil, contre, valider inventaire)
        $magasinier = Role::findByName('Magasinier');
        $this->assertSame(24, $magasinier->permissions->count());
        $this->assertFalse($magasinier->hasPermissionTo('stock.inventaires.valider'));
        $this->assertFalse($magasinier->hasPermissionTo('stock.mouvements.contre'));
        $this->assertFalse($magasinier->hasPermissionTo('stock.niveaux.seuil'));

        // Lecture seule : index + dashboard/rapports/api
        $consultation = Role::findByName('Consultation stock');
        $this->assertSame(12, $consultation->permissions->count());
        $this->assertTrue(
            $consultation->permissions->pluck('name')
                ->reject(fn ($name) => $name === 'catalogue.api.view')
                ->every(fn ($name) => str_ends_with($name, '.index')
                    || in_array($name, ['stock.dashboard.view', 'stock.rapports.view', 'stock.rapports.export', 'stock.api.view'], true))
        );

        foreach (['Superviseur stock', 'Magasinier', 'Consultation stock'] as $roleName) {
            $this->assertTrue(
                Role::findByName($roleName)->hasPermissionTo('catalogue.api.view'),
                "{$roleName} doit recevoir catalogue.api.view (S11)."
            );
        }
    }

    public function test_permissions_seeder_is_idempotent(): void
    {
        $this->seed(StockPermissionsSeeder::class);

        $this->assertSame(3, Role::whereIn('name', ['Superviseur stock', 'Magasinier', 'Consultation stock'])->count());
        $this->assertSame(31, Role::findByName('Superviseur stock')->permissions->count());
    }

    public function test_magasins_seeder_creates_the_two_chuyo_magasins_and_is_idempotent(): void
    {
        $this->seed(StockMagasinsSeeder::class);
        $this->seed(StockMagasinsSeeder::class);

        $this->assertSame(2, Magasin::count());

        $principal = Magasin::where('code', 'MAG-SITE-PRINCIPAL')->first();
        $this->assertNotNull($principal);
        $this->assertTrue($principal->est_actif);
        $this->assertSame('SITE-PRINCIPAL', $principal->site->code);

        $this->assertNotNull(Magasin::where('code', 'MAG-SITE-GERIATRIE')->first());
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('stock.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('stock.dashboard'))
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_access_dashboard(): void
    {
        $user = $this->makeUser();
        $user->givePermissionTo('stock.dashboard.view');

        $this->actingAs($user)
            ->get(route('stock.dashboard'))
            ->assertStatus(200)
            ->assertSee('Module Stock');
    }

    public function test_sidebar_navigation_visible_only_with_permission(): void
    {
        $avec = $this->makeUser('avec@example.com');
        $avec->givePermissionTo('stock.dashboard.view');
        $this->assertArrayHasKey('stock', $avec->getModuleNavigation());

        $sans = $this->makeUser('sans@example.com');
        $this->assertArrayNotHasKey('stock', $sans->getModuleNavigation());
    }
}
