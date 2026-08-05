<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Nwidart\Modules\Facades\Module;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Installation et intégration du module (jalon « le module existe, s'installe,
 * s'affiche »).
 *
 * Vérifie ce qu'un installateur constate réellement : le module est déclaré
 * avec ses dépendances, la commande de synchronisation fonctionne et reste
 * idempotente, le chrome (sidebar, topbar, fil d'Ariane) est conforme à
 * SPEC_UX §0.1, et l'accès est bien gardé côté serveur.
 */
class InstallationModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    private function utilisateur(array $permissions = [], string $email = 'install@example.com'): User
    {
        $user = User::create([
            'name' => 'Test', 'last_name' => 'Install', 'user_name' => 'user_'.md5($email),
            'email' => $email, 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    // ── Déclaration du module ──────────────────────────────────────────────

    public function test_le_module_est_declare_et_actif(): void
    {
        $module = Module::find('achat');

        $this->assertNotNull($module, 'Le module Achat est introuvable.');
        $this->assertTrue($module->isEnabled(), 'Le module Achat doit être activé.');
    }

    /** Les dépendances du SFD §1.1 conditionnent l'ordre d'installation. */
    public function test_les_dependances_du_sfd_sont_declarees(): void
    {
        $manifeste = json_decode(
            file_get_contents(module_path('Achat', 'module.json')),
            true
        );

        $attendues = ['Core', 'Catalogue', 'Stock', 'ParcInfo', 'Organisation'];

        sort($attendues);
        $declarees = $manifeste['requires'];
        sort($declarees);

        $this->assertSame($attendues, $declarees);
        $this->assertSame('achat', $manifeste['alias']);
    }

    public function test_les_routes_sont_prefixees_et_nommees(): void
    {
        $route = Route::getRoutes()->getByName('achat.dashboard');

        $this->assertNotNull($route, 'La route achat.dashboard est absente.');
        $this->assertSame('achat', $route->uri());
    }

    // ── Synchronisation des permissions ────────────────────────────────────

    public function test_la_commande_de_synchronisation_fonctionne(): void
    {
        $this->assertSame(0, Artisan::call('cores:sync-permissions achat'));

        $this->assertSame(
            20,
            Permission::where('module', 'achat')->count(),
            'La commande doit poser les 20 permissions du SFD §5.'
        );
    }

    /** Rejouée, la commande ne duplique rien : l'installation est reprenable. */
    public function test_la_synchronisation_est_idempotente(): void
    {
        Artisan::call('cores:sync-permissions achat');
        Artisan::call('cores:sync-permissions achat');

        $this->assertSame(20, Permission::where('module', 'achat')->count());
        $this->assertSame(
            20,
            Permission::where('name', 'LIKE', 'achat.%')->distinct()->count('name')
        );
    }

    /** Le seeder complet (permissions + rôles + paramètres) rejoué deux fois. */
    public function test_le_seeder_complet_est_idempotent(): void
    {
        $this->seed(\Modules\Achat\Database\Seeders\AchatDatabaseSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatDatabaseSeeder::class);

        $this->assertSame(20, Permission::where('module', 'achat')->count());
        $this->assertSame(
            3,
            \Modules\Core\Models\Role::whereIn('name', ['Acheteur', 'Validateur Achat', 'Consultation Achat'])->count()
        );

        foreach (array_keys(config('achat.parametres_defaut')) as $cle) {
            $this->assertSame(1, \Modules\Achat\Models\Parametre::where('cle', $cle)->count());
        }
    }

    // ── Chrome (SPEC_UX §0.1) ──────────────────────────────────────────────

    public function test_la_page_affiche_la_marque_et_le_fil_d_ariane(): void
    {
        $reponse = $this->actingAs($this->utilisateur(['achat.dashboard.view']))
            ->get(route('achat.dashboard'))
            ->assertOk();

        // Marque de la sidebar
        $reponse->assertSee('CHU-YO | ACHAT');
        // Fil d'Ariane « Accueil / Achat / Tableau de bord »
        $reponse->assertSee('Accueil');
        $reponse->assertSee('Tableau de bord');
        // Pied de page du module
        $reponse->assertSee('CHU-YO | Module Achat');
    }

    /** Les accès rapides de la topbar (SPEC_UX §0.1). */
    public function test_la_topbar_porte_l_acces_rapide_au_tableau_de_bord(): void
    {
        $this->actingAs($this->utilisateur(['achat.dashboard.view']))
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertSee('TABLEAU DE BORD');
    }

    /**
     * Doctrine §0.3 : une entrée dont la page n'existe pas encore reste
     * masquée — jamais de lien mort dans la navigation.
     */
    public function test_les_entrees_de_sidebar_non_developpees_sont_masquees(): void
    {
        $reponse = $this->actingAs($this->utilisateur([
            'achat.dashboard.view',
            'achat.bons_commande.index',
            'achat.reliquats.index',
            'achat.rapports.view',
        ]))->get(route('achat.dashboard'))->assertOk();

        foreach (['achat.bons-commande.index', 'achat.reliquats.index', 'achat.rapports.index'] as $route) {
            if (! Route::has($route)) {
                $reponse->assertDontSee(url('/achat/'.explode('.', $route)[1]));
            }
        }

        // Le tableau de bord, lui, est bien présent
        $reponse->assertSee(route('achat.dashboard'), false);
    }

    /** EV-01 — un module vide enseigne le circuit au lieu d'aligner des zéros. */
    public function test_le_module_vide_affiche_l_etat_pedagogique(): void
    {
        $this->actingAs($this->utilisateur(['achat.dashboard.view']))
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertSee('Aucun bon de commande pour l\'instant.', false)
            ->assertSee('Catalogue → bon de commande → visa → réception au magasin → parc', false);
    }

    // ── Garde d'accès ──────────────────────────────────────────────────────

    public function test_l_acces_est_garde_cote_serveur(): void
    {
        // Visiteur
        $this->get(route('achat.dashboard'))->assertRedirect(route('login'));

        // Authentifié sans rôle Achat : 403 nominative
        $this->actingAs($this->utilisateur([], 'sans@example.com'))
            ->get(route('achat.dashboard'))
            ->assertStatus(403)
            ->assertSee('achat.dashboard.view');
    }

    /** Chacun des 3 rôles seedés ouvre le tableau de bord. */
    public function test_les_trois_roles_seedes_accedent_au_tableau_de_bord(): void
    {
        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $index => $role) {
            $user = $this->utilisateur([], "role{$index}@example.com");
            $user->assignRole($role);

            $this->actingAs($user)
                ->get(route('achat.dashboard'))
                ->assertOk();
        }
    }
}
