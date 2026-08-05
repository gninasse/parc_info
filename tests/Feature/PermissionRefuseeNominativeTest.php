<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * 403 nominatives (SPEC_UX §0.5 · DESIGN.md) — exigence TRANSVERSE.
 *
 * Un refus muet (« Forbidden ») laisse l'utilisateur sans recours et
 * l'administrateur sans diagnostic. Toute 403 issue d'un contrôle de
 * permission doit nommer ce qui manque, sous ses deux formes : le libellé
 * métier et le nom technique à accorder dans la matrice des rôles.
 */
class PermissionRefuseeNominativeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    private function utilisateurSansDroit(string $email = 'sans.droit@example.com'): User
    {
        return User::create([
            'name' => 'Sans', 'last_name' => 'Droit', 'user_name' => 'user_'.md5($email),
            'email' => $email, 'password' => bcrypt('password'),
        ]);
    }

    public function test_la_page_403_nomme_la_permission_manquante(): void
    {
        $reponse = $this->actingAs($this->utilisateurSansDroit())
            ->get(route('achat.dashboard'))
            ->assertStatus(403);

        // Nom technique : ce que l'administrateur doit accorder
        $reponse->assertSee('achat.dashboard.view');
        // Libellé métier : ce que l'utilisateur voulait faire
        $reponse->assertSee('Accéder au module et au tableau de bord');
    }

    public function test_la_page_403_reste_lisible_et_offre_une_sortie(): void
    {
        $reponse = $this->actingAs($this->utilisateurSansDroit())
            ->get(route('achat.dashboard'))
            ->assertStatus(403);

        $reponse->assertSee("Vous n'avez pas l'autorisation d'accéder à cette page.", false);
        $reponse->assertSee('Accueil général');
        $reponse->assertDontSee('Forbidden');
    }

    /** Les appels AJAX reçoivent le même diagnostic, en JSON exploitable. */
    public function test_une_403_json_porte_la_permission_manquante(): void
    {
        $this->actingAs($this->utilisateurSansDroit())
            ->getJson(route('achat.dashboard'))
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
                'permissions_requises' => ['achat.dashboard.view'],
            ])
            ->assertJsonPath('message', 'Autorisation requise : Accéder au module et au tableau de bord.');
    }

    /** L'exigence est transverse : elle vaut pour tous les modules. */
    public function test_le_diagnostic_vaut_aussi_pour_les_autres_modules(): void
    {
        $this->actingAs($this->utilisateurSansDroit('autre@example.com'))
            ->get(route('stock.dashboard'))
            ->assertStatus(403)
            ->assertSee('stock.dashboard.view');
    }

    /** Un utilisateur habilité n'est évidemment pas gêné. */
    public function test_l_utilisateur_habilite_accede_normalement(): void
    {
        $user = $this->utilisateurSansDroit('habilite@example.com');
        $user->givePermissionTo('achat.dashboard.view');

        $this->actingAs($user)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertDontSee('Accès refusé');
    }
}
