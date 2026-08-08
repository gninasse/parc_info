<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CatalogueSkeletonTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
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

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('catalogue.articles.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_permission_gets_403(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('catalogue.articles.index'))
            ->assertStatus(403);
    }

    public function test_user_with_permission_can_access_articles_index(): void
    {
        $user = $this->makeUser();
        $user->givePermissionTo('catalogue.articles.index');

        $this->actingAs($user)
            ->get(route('catalogue.articles.index'))
            ->assertStatus(200)
            ->assertSee('Catalogue des articles');
    }

    public function test_sidebar_navigation_visible_only_with_permission(): void
    {
        $avec = $this->makeUser('avec@example.com');
        $avec->givePermissionTo('catalogue.articles.index');
        $this->assertArrayHasKey('catalogue', $avec->getModuleNavigation());

        $sans = $this->makeUser('sans@example.com');
        $this->assertArrayNotHasKey('catalogue', $sans->getModuleNavigation());
    }

    public function test_seeder_creates_the_sixteen_permissions_with_module_catalogue(): void
    {
        $attendues = array_keys(require module_path('Catalogue', 'config/permissions.php'));

        $this->assertCount(16, $attendues);
        $this->assertSame(
            16,
            \Spatie\Permission\Models\Permission::where('module', 'catalogue')
                ->whereIn('name', $attendues)->count()
        );
    }

    public function test_seeder_creates_the_three_roles_and_is_idempotent(): void
    {
        $this->seed(CataloguePermissionsSeeder::class);

        $this->assertSame(16, Role::findByName('Administrateur catalogue')->permissions->count());
        $this->assertSame(13, Role::findByName('Gestionnaire catalogue')->permissions->count());
        $this->assertSame(4, Role::findByName('Consultation catalogue')->permissions->count());
        $this->assertSame(3, Role::where('name', 'like', '%catalogue%')->count());
    }
}
