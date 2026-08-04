<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Services\MagasinContexteService;
use Tests\TestCase;

/**
 * Diligence 3 — magasin par défaut : préférence explicite, résolution en
 * cascade et pré-sélection dans les trois documents.
 */
class MagasinParDefautTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Magasinier');
        $this->user->givePermissionTo('stock.dashboard.view');
    }

    public function test_enregistrement_et_retrait_de_la_preference(): void
    {
        $magasin = Magasin::factory()->create();

        $this->actingAs($this->user)
            ->patchJson(route('stock.preferences.magasin-defaut'), ['magasin_id' => $magasin->id])
            ->assertOk();

        $this->assertDatabaseHas('stock_preferences', [
            'user_id' => $this->user->id,
            'magasin_defaut_id' => $magasin->id,
        ]);
        $this->assertSame($magasin->id, app(MagasinContexteService::class)->magasinParDefautId($this->user->id));

        // Retrait : la préférence existe toujours mais sans magasin
        $this->actingAs($this->user)
            ->patchJson(route('stock.preferences.magasin-defaut'), ['magasin_id' => null])
            ->assertOk();

        $this->assertDatabaseHas('stock_preferences', [
            'user_id' => $this->user->id,
            'magasin_defaut_id' => null,
        ]);
    }

    public function test_resolution_en_cascade(): void
    {
        $service = app(MagasinContexteService::class);

        // 3. Magasin unique → retenu d'office
        $unique = Magasin::factory()->create();
        $this->assertSame($unique->id, $service->magasinParDefautId($this->user->id));

        // Plusieurs magasins sans préférence ni responsabilité → aucun
        $autre = Magasin::factory()->create();
        $this->assertNull($service->magasinParDefautId($this->user->id));

        // 2. Magasin dont l'utilisateur est responsable
        $employe = Employe::factory()->create();
        $this->user->update(['dossier_employe_id' => $employe->id]);
        $autre->update(['responsable_id' => $employe->id]);
        $this->assertSame($autre->id, $service->magasinParDefautId($this->user->id));

        // 1. Préférence explicite : prioritaire sur la responsabilité
        $service->definirMagasinParDefaut($this->user->id, $unique->id);
        $this->assertSame($unique->id, $service->magasinParDefautId($this->user->id));

        // Un magasin désactivé ne peut pas être le magasin de travail
        $unique->update(['est_actif' => false]);
        $this->assertSame($autre->id, $service->magasinParDefautId($this->user->id));
    }

    public function test_preselection_dans_les_trois_formulaires(): void
    {
        $prefere = Magasin::factory()->create(['libelle' => 'Magasin Préféré']);
        Magasin::factory()->create(['libelle' => 'Autre magasin']);

        app(MagasinContexteService::class)->definirMagasinParDefaut($this->user->id, $prefere->id);

        $this->user->givePermissionTo(['stock.entrees.store', 'stock.sorties.store', 'stock.transferts.store']);

        // Entrée
        $this->actingAs($this->user)
            ->get(route('stock.entrees.create'))
            ->assertOk()
            ->assertSee("<option value=\"{$prefere->id}\" selected>Magasin Préféré</option>", false);

        // Sortie
        $this->actingAs($this->user)
            ->get(route('stock.sorties.create'))
            ->assertOk()
            ->assertSee("<option value=\"{$prefere->id}\" selected>Magasin Préféré</option>", false);

        // Transfert : le magasin de travail est la SOURCE
        $reponse = $this->actingAs($this->user)
            ->get(route('stock.transferts.create'))
            ->assertOk();
        $this->assertSame($prefere->id, $reponse->viewData('sourcePreremplie'));
    }

    public function test_le_parametre_d_url_prime_sur_la_preference(): void
    {
        $prefere = Magasin::factory()->create();
        $autre = Magasin::factory()->create();
        app(MagasinContexteService::class)->definirMagasinParDefaut($this->user->id, $prefere->id);
        $this->user->givePermissionTo('stock.entrees.store');

        // « ➜ Réceptionner » depuis une alerte d'un AUTRE magasin
        $reponse = $this->actingAs($this->user)
            ->get(route('stock.entrees.create', ['magasin_id' => $autre->id]))
            ->assertOk();

        $this->assertSame($autre->id, $reponse->viewData('magasinPrerempli'));
    }

    public function test_le_selecteur_est_present_sur_le_tableau_de_bord(): void
    {
        $magasin = Magasin::factory()->create(['libelle' => 'Magasin du tableau']);
        app(MagasinContexteService::class)->definirMagasinParDefaut($this->user->id, $magasin->id);

        $this->actingAs($this->user)
            ->get(route('stock.dashboard'))
            ->assertOk()
            ->assertSee('Mon magasin par défaut')
            ->assertSee("<option value=\"{$magasin->id}\" selected>Magasin du tableau</option>", false);
    }

    public function test_preference_refusee_sans_permission_du_module(): void
    {
        $etranger = User::create([
            'name' => 'Hors', 'last_name' => 'Module', 'user_name' => 'hors',
            'email' => 'hors@example.com', 'password' => bcrypt('password'),
        ]);

        $this->actingAs($etranger)
            ->patchJson(route('stock.preferences.magasin-defaut'), ['magasin_id' => Magasin::factory()->create()->id])
            ->assertStatus(403);
    }
}
