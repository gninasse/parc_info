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

    /**
     * Le nombre n'est plus figé dans le NOM du test : il a changé à chaque
     * ajout légitime de permission (16 → 20 avec le carnet de contacts), et
     * un intitulé qui ment est pire qu'un intitulé vague. Ce qui compte est
     * que TOUTE permission déclarée soit effectivement seedée avec son
     * module — c'est cela qu'on vérifie.
     */
    public function test_seeder_creates_every_declared_permission_with_module_catalogue(): void
    {
        $attendues = array_keys(require module_path('Catalogue', 'config/permissions.php'));

        $this->assertNotEmpty($attendues);
        $this->assertSame(
            count($attendues),
            \Spatie\Permission\Models\Permission::where('module', 'catalogue')
                ->whereIn('name', $attendues)->count()
        );
    }

    /**
     * Les rôles se DÉDUISENT des permissions (voir le seeder) : on recalcule
     * donc les effectifs attendus au lieu de les recopier, sinon le test
     * devrait être réécrit à chaque ajout — et le recopiage finit toujours
     * par masquer une erreur de répartition.
     */
    public function test_seeder_creates_the_three_roles_and_is_idempotent(): void
    {
        $this->seed(CataloguePermissionsSeeder::class);

        $permissions = array_keys(require module_path('Catalogue', 'config/permissions.php'));

        $consultation = array_filter(
            $permissions,
            fn (string $nom) => str_ends_with($nom, '.index') || $nom === 'catalogue.api.view'
        );
        $gestion = array_filter($permissions, fn (string $nom) => ! str_ends_with($nom, '.destroy'));

        $this->assertSame(count($permissions), Role::findByName('Administrateur catalogue')->permissions->count());
        $this->assertSame(count($gestion), Role::findByName('Gestionnaire catalogue')->permissions->count());
        $this->assertSame(count($consultation), Role::findByName('Consultation catalogue')->permissions->count());
        $this->assertSame(3, Role::where('name', 'like', '%catalogue%')->count());

        // Le rôle de consultation ne doit JAMAIS pouvoir écrire : c'est la
        // seule propriété de fond ici, et elle mérite d'être affirmée
        // explicitement plutôt que déduite d'un décompte.
        $lecture = Role::findByName('Consultation catalogue')->permissions->pluck('name');

        foreach ($lecture as $nom) {
            $this->assertTrue(
                str_ends_with($nom, '.index') || $nom === 'catalogue.api.view',
                "Le rôle de consultation ne devrait pas porter « {$nom} »."
            );
        }
    }
}
