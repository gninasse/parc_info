<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Tests\TestCase;

class FournisseurManagementTest extends TestCase
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

    private function fournisseur(array $attributes = []): Fournisseur
    {
        return Fournisseur::factory()->create($attributes);
    }

    public function test_matrice_de_permissions_chaque_action_est_protegee(): void
    {
        $fournisseur = $this->fournisseur();
        $sansDroit = $this->userWith();

        $matrice = [
            ['get', route('catalogue.fournisseurs.index')],
            ['get', route('catalogue.fournisseurs.data')],
            ['get', route('catalogue.fournisseurs.show', $fournisseur->id)],
            ['post', route('catalogue.fournisseurs.store')],
            ['put', route('catalogue.fournisseurs.update', $fournisseur->id)],
            ['delete', route('catalogue.fournisseurs.destroy', $fournisseur->id)],
            ['patch', route('catalogue.fournisseurs.toggle-status', $fournisseur->id)],
        ];

        foreach ($matrice as [$methode, $url]) {
            $this->actingAs($sansDroit)->json($methode, $url)
                ->assertStatus(403);
        }
    }

    public function test_index_accessible_avec_la_permission_et_affiche_les_kpi(): void
    {
        $this->fournisseur();

        $this->actingAs($this->userWith('catalogue.fournisseurs.index'))
            ->get(route('catalogue.fournisseurs.index'))
            ->assertStatus(200)
            ->assertSee('Fournisseurs actifs')
            ->assertSee('Ajoutés cette année')
            // Groupe CATALOGUE de la sidebar Core, visible avec la permission
            ->assertSee('CATALOGUE')
            ->assertSee(route('catalogue.fournisseurs.index'));
    }

    public function test_get_data_retourne_total_rows_et_le_compteur_d_articles(): void
    {
        $avecArticles = $this->fournisseur(['raison_sociale' => 'SITB Burkina']);
        $sansArticle = $this->fournisseur(['raison_sociale' => 'Techno Sahel']);

        $categorie = Categorie::create(['libelle' => 'Générale']);
        Article::factory()->count(2)->create([
            'categorie_id' => $categorie->id,
            'fournisseur_principal_id' => $avecArticles->id,
        ]);

        $reponse = $this->actingAs($this->userWith('catalogue.fournisseurs.index'))
            ->getJson(route('catalogue.fournisseurs.data'))
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 2);

        $rows = collect($reponse->json('rows'))->keyBy('raison_sociale');
        $this->assertSame(2, $rows['SITB Burkina']['nb_articles']);
        $this->assertSame(0, $rows['Techno Sahel']['nb_articles']);
    }

    public function test_get_data_recherche_et_filtre_statut(): void
    {
        $this->fournisseur(['raison_sociale' => 'SoftSell Ouaga']);
        $this->fournisseur(['raison_sociale' => 'Autre', 'est_actif' => false]);

        $utilisateur = $this->userWith('catalogue.fournisseurs.index');

        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.fournisseurs.data', ['search' => 'SOFTSELL']))
            ->assertJsonPath('total', 1);

        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.fournisseurs.data', ['statut' => 'inactif']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.raison_sociale', 'Autre');
    }

    public function test_show_renvoie_le_json_de_preremplissage_quand_la_requete_attend_du_json(): void
    {
        $fournisseur = $this->fournisseur(['raison_sociale' => 'SITB Burkina']);

        $this->actingAs($this->userWith('catalogue.fournisseurs.index'))
            ->getJson(route('catalogue.fournisseurs.show', $fournisseur->id))
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.raison_sociale', 'SITB Burkina')
            ->assertJsonPath('data.code', $fournisseur->code);
    }

    public function test_show_renvoie_la_fiche_html_sinon(): void
    {
        $fournisseur = $this->fournisseur(['raison_sociale' => 'SITB Burkina']);

        $this->actingAs($this->userWith('catalogue.fournisseurs.index'))
            ->get(route('catalogue.fournisseurs.show', $fournisseur->id))
            ->assertStatus(200)
            ->assertSee('SITB Burkina')
            ->assertSee('01 — Coordonnées')
            ->assertSee('02 — Articles au catalogue')
            ->assertSee("03 — Journal d'activité", false);
    }

    public function test_store_cree_avec_un_code_genere_automatiquement(): void
    {
        $this->actingAs($this->userWith('catalogue.fournisseurs.store'))
            ->postJson(route('catalogue.fournisseurs.store'), [
                'raison_sociale' => 'SoftSell Burkina',
                'email' => 'contact@softsell.bf',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'FOUR-SO001');

        $this->assertDatabaseHas('catalogue_fournisseurs', ['raison_sociale' => 'SoftSell Burkina', 'code' => 'FOUR-SO001']);
    }

    public function test_store_valide_raison_sociale_email_et_code_unique(): void
    {
        $this->fournisseur(['code' => 'FOUR-XX001']);
        $utilisateur = $this->userWith('catalogue.fournisseurs.store');

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.fournisseurs.store'), ['email' => 'pas-un-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['raison_sociale', 'email']);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.fournisseurs.store'), [
                'raison_sociale' => 'Doublon',
                'code' => 'FOUR-XX001',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_update_modifie_le_fournisseur(): void
    {
        $fournisseur = $this->fournisseur(['raison_sociale' => 'Ancien nom']);

        $this->actingAs($this->userWith('catalogue.fournisseurs.update'))
            ->putJson(route('catalogue.fournisseurs.update', $fournisseur->id), [
                'raison_sociale' => 'Nouveau nom',
                'code' => $fournisseur->code,
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSame('Nouveau nom', $fournisseur->fresh()->raison_sociale);
    }

    public function test_destroy_bloque_en_422_quand_des_articles_le_referencent(): void
    {
        $fournisseur = $this->fournisseur();
        $categorie = Categorie::create(['libelle' => 'Générale']);
        Article::factory()->create([
            'categorie_id' => $categorie->id,
            'fournisseur_principal_id' => $fournisseur->id,
        ]);

        $this->actingAs($this->userWith('catalogue.fournisseurs.destroy'))
            ->deleteJson(route('catalogue.fournisseurs.destroy', $fournisseur->id))
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonFragment(['message' => 'Suppression impossible : 1 article(s) du catalogue le référencent comme fournisseur principal.']);

        $this->assertDatabaseHas('catalogue_fournisseurs', ['id' => $fournisseur->id]);
    }

    public function test_destroy_supprime_sans_donnees_liees_meme_sans_les_modules_stock_et_achat(): void
    {
        $fournisseur = $this->fournisseur();

        // Les tables stock_receptions / achat_commandes n'existent pas dans
        // cet environnement : la garde doit rester fonctionnelle (DoD).
        $this->actingAs($this->userWith('catalogue.fournisseurs.destroy'))
            ->deleteJson(route('catalogue.fournisseurs.destroy', $fournisseur->id))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('catalogue_fournisseurs', ['id' => $fournisseur->id]);
    }

    public function test_toggle_status_inverse_le_statut(): void
    {
        $fournisseur = $this->fournisseur(['est_actif' => true]);

        $this->actingAs($this->userWith('catalogue.fournisseurs.toggle-status'))
            ->patchJson(route('catalogue.fournisseurs.toggle-status', $fournisseur->id))
            ->assertStatus(200)
            ->assertJsonPath('data.est_actif', false);

        $this->assertFalse($fournisseur->fresh()->est_actif);
    }

    public function test_articles_data_filtre_par_fournisseur_pour_la_fiche(): void
    {
        $fournisseur = $this->fournisseur();
        $autre = $this->fournisseur();
        $categorie = Categorie::create(['libelle' => 'Générale']);

        Article::factory()->create(['categorie_id' => $categorie->id, 'fournisseur_principal_id' => $fournisseur->id, 'nom' => 'Article du fournisseur']);
        Article::factory()->create(['categorie_id' => $categorie->id, 'fournisseur_principal_id' => $autre->id]);
        Article::factory()->create(['categorie_id' => $categorie->id]);

        $this->actingAs($this->userWith('catalogue.articles.index'))
            ->getJson(route('catalogue.articles.data', ['fournisseur_id' => $fournisseur->id]))
            ->assertStatus(200)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.nom', 'Article du fournisseur');
    }
}
