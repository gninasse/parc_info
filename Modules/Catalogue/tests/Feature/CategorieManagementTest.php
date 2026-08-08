<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Database\Seeders\CatalogueReferenceSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Core\Models\User;
use Tests\TestCase;

class CategorieManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::create([
            'name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user_'.uniqid(),
            'email' => uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_matrice_de_permissions_toutes_les_routes_sont_protegees(): void
    {
        $categorie = Categorie::create(['libelle' => 'Impression']);
        $sansDroit = $this->userWith();

        $matrice = [
            ['get', route('catalogue.categories.index')],
            ['get', route('catalogue.categories.data')],
            ['get', route('catalogue.categories.parents')],
            ['get', route('catalogue.categories.show', $categorie->id)],
            ['post', route('catalogue.categories.store')],
            ['put', route('catalogue.categories.update', $categorie->id)],
            ['delete', route('catalogue.categories.destroy', $categorie->id)],
            ['patch', route('catalogue.categories.toggle-status', $categorie->id)],
        ];

        foreach ($matrice as [$methode, $url]) {
            $this->actingAs($sansDroit)->json($methode, $url)->assertStatus(403);
        }
    }

    public function test_get_data_format_total_rows_et_tri_arborescent(): void
    {
        $impression = Categorie::create(['libelle' => 'Impression']);
        $reseau = Categorie::create(['libelle' => 'Réseau']);
        $toners = Categorie::create(['libelle' => 'Toners', 'parent_id' => $impression->id]);
        Article::factory()->count(2)->create(['categorie_id' => $toners->id]);

        $reponse = $this->actingAs($this->userWith('catalogue.categories.index'))
            ->getJson(route('catalogue.categories.data'))
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 3);

        $rows = collect($reponse->json('rows'));

        // Arborescence : « Toners » (niveau 2) suit immédiatement son parent
        $this->assertSame(
            ['Impression', 'Toners', 'Réseau'],
            $rows->pluck('libelle')->all()
        );
        $ligneToners = $rows->firstWhere('libelle', 'Toners');
        $this->assertSame(2, $ligneToners['niveau']);
        $this->assertSame('Impression', $ligneToners['parent_libelle']);
        $this->assertSame(2, $ligneToners['nb_articles']);
        $this->assertSame(1, $rows->firstWhere('libelle', 'Impression')['niveau']);
    }

    public function test_get_data_recherche_et_filtre_statut(): void
    {
        Categorie::create(['libelle' => 'Impression']);
        Categorie::create(['libelle' => 'Réseau', 'est_actif' => false]);

        $utilisateur = $this->userWith('catalogue.categories.index');

        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.categories.data', ['search' => 'IMPRESS']))
            ->assertJsonPath('total', 1);

        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.categories.data', ['est_actif' => '0']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.libelle', 'Réseau');
    }

    public function test_parents_ne_liste_que_les_niveaux_1_actifs(): void
    {
        $racine = Categorie::create(['libelle' => 'Impression']);
        Categorie::create(['libelle' => 'Toners', 'parent_id' => $racine->id]);
        Categorie::create(['libelle' => 'Inactive', 'est_actif' => false]);

        $this->actingAs($this->userWith('catalogue.categories.index'))
            ->getJson(route('catalogue.categories.parents'))
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.libelle', 'Impression');
    }

    public function test_store_impose_le_libelle_et_la_profondeur_2(): void
    {
        $racine = Categorie::create(['libelle' => 'Impression']);
        $enfant = Categorie::create(['libelle' => 'Toners', 'parent_id' => $racine->id]);
        $utilisateur = $this->userWith('catalogue.categories.store');

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.categories.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['libelle']);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.categories.store'), ['libelle' => 'Trop profond', 'parent_id' => $enfant->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.categories.store'), ['libelle' => 'Cartouches', 'parent_id' => $racine->id])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('catalogue_categories', ['libelle' => 'Cartouches', 'parent_id' => $racine->id]);
    }

    public function test_update_refuse_qu_une_categorie_avec_enfants_devienne_enfant(): void
    {
        $racine = Categorie::create(['libelle' => 'Impression']);
        Categorie::create(['libelle' => 'Toners', 'parent_id' => $racine->id]);
        $autre = Categorie::create(['libelle' => 'Réseau']);

        $this->actingAs($this->userWith('catalogue.categories.update'))
            ->putJson(route('catalogue.categories.update', $racine->id), [
                'libelle' => 'Impression',
                'parent_id' => $autre->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['parent_id']);

        $this->assertNull($racine->fresh()->parent_id);
    }

    public function test_destroy_gardes_enfants_articles_et_systeme(): void
    {
        $this->seed(CatalogueReferenceSeeder::class);

        $racine = Categorie::create(['libelle' => 'Impression']);
        Categorie::create(['libelle' => 'Toners', 'parent_id' => $racine->id]);
        $avecArticles = Categorie::create(['libelle' => 'Réseau']);
        Article::factory()->count(3)->create(['categorie_id' => $avecArticles->id]);
        $systeme = Categorie::where('code', 'CAT-NC-CONS')->firstOrFail();
        $libre = Categorie::create(['libelle' => 'Libre']);

        $utilisateur = $this->userWith('catalogue.categories.destroy');

        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.categories.destroy', $racine->id))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Suppression impossible : 1 sous-catégorie(s) existe(nt).']);

        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.categories.destroy', $avecArticles->id))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Suppression impossible : 3 article(s) rattaché(s).']);

        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.categories.destroy', $systeme->id))
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Suppression impossible : catégorie système « Non classé », insupprimable.']);

        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.categories.destroy', $libre->id))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('catalogue_categories', ['id' => $libre->id]);
    }

    public function test_toggle_parent_demande_confirmation_puis_cascade(): void
    {
        $racine = Categorie::create(['libelle' => 'Impression']);
        $enfant1 = Categorie::create(['libelle' => 'Toners', 'parent_id' => $racine->id]);
        $enfant2 = Categorie::create(['libelle' => 'Cartouches', 'parent_id' => $racine->id]);

        $utilisateur = $this->userWith('catalogue.categories.toggle-status');

        // 1er appel : confirmation demandée, rien ne change
        $this->actingAs($utilisateur)
            ->patchJson(route('catalogue.categories.toggle-status', $racine->id))
            ->assertStatus(200)
            ->assertJsonPath('requires_confirmation', true)
            ->assertJsonPath('data.enfants_actifs', 2);

        $this->assertTrue($racine->fresh()->est_actif);
        $this->assertTrue($enfant1->fresh()->est_actif);

        // 2e appel avec cascade : tout est désactivé
        $this->actingAs($utilisateur)
            ->patchJson(route('catalogue.categories.toggle-status', $racine->id), ['cascade' => true])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertFalse($racine->fresh()->est_actif);
        $this->assertFalse($enfant1->fresh()->est_actif);
        $this->assertFalse($enfant2->fresh()->est_actif);

        // Réactivation simple, sans cascade demandée
        $this->actingAs($utilisateur)
            ->patchJson(route('catalogue.categories.toggle-status', $racine->id))
            ->assertStatus(200)
            ->assertJsonPath('data.est_actif', true);

        $this->assertFalse($enfant1->fresh()->est_actif);
    }

    public function test_seeder_c7_cree_les_quatre_non_classe_idempotent(): void
    {
        $this->seed(CatalogueReferenceSeeder::class);
        $this->seed(CatalogueReferenceSeeder::class);

        $this->assertSame(4, Categorie::where('est_systeme', true)->count());
        $this->assertDatabaseHas('catalogue_categories', ['code' => 'CAT-NC-LIC', 'est_systeme' => true]);
    }
}
