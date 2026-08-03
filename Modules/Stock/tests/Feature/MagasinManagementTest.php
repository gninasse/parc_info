<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Organisation\Models\Site;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Modules\Core\Models\User;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

class MagasinManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
    }

    private function makeUser(array $permissions = [], string $email = 'test@example.com'): User
    {
        $user = User::create([
            'name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /** Matrice : chaque route magasins exige SA permission (toggle et cascades inclus). */
    public static function routesProtegees(): array
    {
        return [
            'index' => ['GET', 'stock.magasins.index', [], 'stock.magasins.index'],
            'data' => ['GET', 'stock.magasins.data', [], 'stock.magasins.index'],
            'show' => ['GET', 'stock.magasins.show', ['id' => true], 'stock.magasins.index'],
            'equipements-data' => ['GET', 'stock.magasins.equipements-data', ['id' => true], 'stock.magasins.index'],
            'mouvements-data' => ['GET', 'stock.magasins.mouvements-data', ['id' => true], 'stock.magasins.index'],
            'sites-disponibles' => ['GET', 'stock.magasins.sites-disponibles', [], 'stock.magasins.store'],
            'locaux (cascade)' => ['GET', 'stock.magasins.locaux', ['site_id' => 1], 'stock.magasins.store'],
            'responsables (cascade)' => ['GET', 'stock.magasins.responsables', [], 'stock.magasins.store'],
            'store' => ['POST', 'stock.magasins.store', [], 'stock.magasins.store'],
            'update' => ['PUT', 'stock.magasins.update', ['id' => true], 'stock.magasins.update'],
            'destroy' => ['DELETE', 'stock.magasins.destroy', ['id' => true], 'stock.magasins.destroy'],
            'toggle-status' => ['PATCH', 'stock.magasins.toggle-status', ['id' => true], 'stock.magasins.toggle-status'],
        ];
    }

    /** @dataProvider routesProtegees */
    public function test_matrice_403_sans_permission_et_redirection_invite(string $methode, string $route, array $params, string $permission): void
    {
        $magasin = Magasin::factory()->create();
        $params = array_map(fn ($valeur) => $valeur === true ? $magasin->id : $valeur, $params);
        $url = route($route, $params);

        // Invité → login
        $this->json = false;
        $this->call($methode, $url)->assertRedirect(route('login'));

        // Sans permission → 403, jamais 200 ni 500
        $sans = $this->makeUser([], 'sans-'.md5($route.$methode).'@example.com');
        $this->actingAs($sans)->json($methode, $url)->assertStatus(403);

        // Avec la permission → jamais 403 ni 500 (2xx ou 422 de validation)
        $avec = $this->makeUser([$permission], 'avec-'.md5($route.$methode).'@example.com');
        $statut = $this->actingAs($avec)->json($methode, $url)->getStatusCode();
        $this->assertContains($statut, [200, 201, 302, 422], "Route {$route} : statut {$statut} inattendu avec permission.");
    }

    public function test_store_genere_le_code_mag_site(): void
    {
        $site = Site::query()->create(['code' => 'SITE-NEUF', 'libelle' => 'Site neuf']);
        $user = $this->makeUser(['stock.magasins.store']);

        $this->actingAs($user)
            ->postJson(route('stock.magasins.store'), ['libelle' => 'Magasin neuf', 'site_id' => $site->id])
            ->assertOk()
            ->assertJsonPath('data.code', 'MAG-SITE-NEUF');

        $this->assertDatabaseHas('stock_magasins', ['code' => 'MAG-SITE-NEUF', 'site_id' => $site->id]);
    }

    public function test_unicite_un_magasin_par_site(): void
    {
        $magasin = Magasin::factory()->create();
        $user = $this->makeUser(['stock.magasins.store']);

        $this->actingAs($user)
            ->postJson(route('stock.magasins.store'), ['libelle' => 'Doublon', 'site_id' => $magasin->site_id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['site_id']);
    }

    public function test_le_site_est_verrouille_en_edition(): void
    {
        $magasin = Magasin::factory()->create();
        $autreSite = Site::query()->create(['code' => 'SITE-AUTRE', 'libelle' => 'Autre site']);
        $user = $this->makeUser(['stock.magasins.update']);

        $this->actingAs($user)
            ->putJson(route('stock.magasins.update', $magasin->id), [
                'libelle' => 'Libellé modifié',
                'site_id' => $autreSite->id, // forgé : doit être ignoré
            ])
            ->assertOk();

        $magasin->refresh();
        $this->assertSame('Libellé modifié', $magasin->libelle);
        $this->assertNotSame($autreSite->id, $magasin->site_id);
    }

    public function test_sites_disponibles_exclut_les_sites_avec_magasin(): void
    {
        $magasin = Magasin::factory()->create();
        $siteLibre = Site::query()->create(['code' => 'SITE-LIBRE', 'libelle' => 'Site libre']);
        $user = $this->makeUser(['stock.magasins.store']);

        $ids = collect($this->actingAs($user)
            ->getJson(route('stock.magasins.sites-disponibles'))
            ->assertOk()
            ->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($siteLibre->id));
        $this->assertFalse($ids->contains($magasin->site_id));

        // En édition : le site du magasin courant reste listé (verrouillé côté UI)
        $idsAvecInclure = collect($this->actingAs($user)
            ->getJson(route('stock.magasins.sites-disponibles', ['inclure' => $magasin->id]))
            ->json('data'))->pluck('id');

        $this->assertTrue($idsAvecInclure->contains($magasin->site_id));
    }

    public function test_garde_suppression_magasin_garni(): void
    {
        $magasin = Magasin::factory()->create();
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 25]);
        EquipementMagasin::factory()->create(['magasin_id' => $magasin->id]);
        $user = $this->makeUser(['stock.magasins.destroy']);

        $reponse = $this->actingAs($user)
            ->deleteJson(route('stock.magasins.destroy', $magasin->id))
            ->assertStatus(422);

        // Message SW-422 énumérant références + équipements, action contextuelle
        $this->assertStringContainsString('référence(s)', $reponse->json('message'));
        $this->assertStringContainsString('équipement(s)', $reponse->json('message'));
        $this->assertStringContainsString('FCFA', $reponse->json('message'));
        $this->assertSame('Voir l\'état des stocks', $reponse->json('action.label'));
        $this->assertDatabaseHas('stock_magasins', ['id' => $magasin->id]);
    }

    public function test_garde_suppression_magasin_avec_journal(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $entree = Entree::factory()->validee()->create(['magasin_id' => $magasin->id]);

        app(MouvementService::class)->entree([
            'entree_id' => $entree->id,
            'magasin_id' => $magasin->id,
            'article_id' => $article->id,
            'quantite' => 5,
        ]);

        // vider le niveau pour isoler la garde « journal »
        app(MouvementService::class)->sortie([
            'sortie_id' => \Modules\Stock\Models\Sortie::factory()->validee()->create(['magasin_id' => $magasin->id])->id,
            'magasin_id' => $magasin->id,
            'article_id' => $article->id,
            'quantite' => 5,
        ]);

        $user = $this->makeUser(['stock.magasins.destroy']);

        $this->actingAs($user)
            ->deleteJson(route('stock.magasins.destroy', $magasin->id))
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('stock_magasins', ['id' => $magasin->id]);
    }

    public function test_garde_desactivation_magasin_garni_et_reactivation_libre(): void
    {
        $magasin = Magasin::factory()->create();
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 3]);
        $user = $this->makeUser(['stock.magasins.toggle-status']);

        $this->actingAs($user)
            ->patchJson(route('stock.magasins.toggle-status', $magasin->id))
            ->assertStatus(422);

        $this->assertTrue($magasin->fresh()->est_actif);

        // Un magasin inactif se réactive toujours, garni ou non
        $magasin->update(['est_actif' => false]);
        $this->actingAs($user)
            ->patchJson(route('stock.magasins.toggle-status', $magasin->id))
            ->assertOk();

        $this->assertTrue($magasin->fresh()->est_actif);
    }

    public function test_suppression_et_desactivation_nominales_magasin_vide(): void
    {
        $user = $this->makeUser(['stock.magasins.destroy', 'stock.magasins.toggle-status']);

        $aDesactiver = Magasin::factory()->create();
        $this->actingAs($user)
            ->patchJson(route('stock.magasins.toggle-status', $aDesactiver->id))
            ->assertOk();
        $this->assertFalse($aDesactiver->fresh()->est_actif);

        $aSupprimer = Magasin::factory()->create();
        $this->actingAs($user)
            ->deleteJson(route('stock.magasins.destroy', $aSupprimer->id))
            ->assertOk();
        $this->assertDatabaseMissing('stock_magasins', ['id' => $aSupprimer->id]);
    }

    public function test_la_fiche_html_affiche_kpis_et_onglets(): void
    {
        $magasin = Magasin::factory()->create();
        $user = $this->makeUser(['stock.magasins.index']);

        $this->actingAs($user)
            ->get(route('stock.magasins.show', $magasin->id))
            ->assertOk()
            ->assertSee($magasin->libelle)
            ->assertSee('Références en stock')
            ->assertSee('Équipements présents')
            ->assertSee('Derniers mouvements');
    }

    public function test_data_renvoie_total_rows_et_nb_references(): void
    {
        $magasin = Magasin::factory()->create();
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 4]);
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 0]); // à 0 : pas une référence
        $user = $this->makeUser(['stock.magasins.index']);

        $reponse = $this->actingAs($user)
            ->getJson(route('stock.magasins.data'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows']);

        $ligne = collect($reponse->json('rows'))->firstWhere('id', $magasin->id);
        $this->assertSame(1, $ligne['nb_references']);
    }
}
