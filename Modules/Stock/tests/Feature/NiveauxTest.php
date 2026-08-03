<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Models\Inventaire;
use Modules\Stock\Models\LigneInventaire;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Tests\TestCase;

class NiveauxTest extends TestCase
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

    public static function routesProtegees(): array
    {
        return [
            'index' => ['GET', 'stock.niveaux.index', [], 'stock.niveaux.index'],
            'data' => ['GET', 'stock.niveaux.data', [], 'stock.niveaux.index'],
            'export' => ['GET', 'stock.niveaux.export', [], 'stock.niveaux.index'],
            'seuil' => ['PATCH', 'stock.niveaux.seuil', ['id' => true], 'stock.niveaux.seuil'],
            'seuil-article' => ['POST', 'stock.niveaux.seuil-article', [], 'stock.niveaux.seuil'],
        ];
    }

    /** @dataProvider routesProtegees */
    public function test_matrice_403_sans_permission_et_redirection_invite(string $methode, string $route, array $params, string $permission): void
    {
        $niveau = Niveau::factory()->create();
        $params = array_map(fn ($valeur) => $valeur === true ? $niveau->id : $valeur, $params);
        $url = route($route, $params);

        $this->call($methode, $url)->assertRedirect(route('login'));

        $sans = $this->makeUser([], 'sans-'.md5($route.$methode).'@example.com');
        $this->actingAs($sans)->json($methode, $url)->assertStatus(403);

        $avec = $this->makeUser([$permission], 'avec-'.md5($route.$methode).'@example.com');
        $statut = $this->actingAs($avec)->json($methode, $url)->getStatusCode();
        $this->assertContains($statut, [200, 201, 302, 422], "Route {$route} : statut {$statut} inattendu avec permission.");
    }

    public function test_data_calcule_le_seuil_effectif_dans_les_trois_cas(): void
    {
        $magasin = Magasin::factory()->create();

        $articleLocal = Article::factory()->consommable()->create(['seuil_defaut' => 3, 'nom' => 'Cas seuil local']);
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'article_id' => $articleLocal->id, 'quantite' => 10, 'seuil' => 7]);

        $articleHerite = Article::factory()->consommable()->create(['seuil_defaut' => 4, 'nom' => 'Cas seuil article']);
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'article_id' => $articleHerite->id, 'quantite' => 10, 'seuil' => null]);

        $articleSans = Article::factory()->consommable()->create(['seuil_defaut' => null, 'nom' => 'Cas sans seuil']);
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'article_id' => $articleSans->id, 'quantite' => 10, 'seuil' => null]);

        $user = $this->makeUser(['stock.niveaux.index']);
        $rows = collect($this->actingAs($user)
            ->getJson(route('stock.niveaux.data', ['magasin_id' => $magasin->id, 'limit' => 50]))
            ->assertOk()
            ->json('rows'));

        $local = $rows->firstWhere('article_nom', 'Cas seuil local');
        $this->assertSame(7.0, (float) $local['seuil_effectif']);
        $this->assertSame('local', $local['seuil_origine']);

        $herite = $rows->firstWhere('article_nom', 'Cas seuil article');
        $this->assertSame(4.0, (float) $herite['seuil_effectif']);
        $this->assertSame('article', $herite['seuil_origine']);

        $sans = $rows->firstWhere('article_nom', 'Cas sans seuil');
        $this->assertNull($sans['seuil_effectif']);
        $this->assertNull($sans['seuil_origine']);
        $this->assertSame('OK', $sans['statut']);
    }

    public function test_le_filtre_statut_d_alerte_suit_la_cascade_de_seuil(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create(['seuil_defaut' => 5]);

        Niveau::factory()->create(['magasin_id' => $magasin->id, 'article_id' => $article->id, 'quantite' => 4]); // SOUS_SEUIL par héritage
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 0]); // RUPTURE

        $user = $this->makeUser(['stock.niveaux.index']);

        $sousSeuiL = $this->actingAs($user)
            ->getJson(route('stock.niveaux.data', ['magasin_id' => $magasin->id, 'statut' => 'SOUS_SEUIL']))
            ->assertOk();
        $this->assertSame(1, $sousSeuiL->json('total'));

        $rupture = $this->actingAs($user)
            ->getJson(route('stock.niveaux.data', ['magasin_id' => $magasin->id, 'statut' => 'RUPTURE']))
            ->assertOk();
        $this->assertSame(1, $rupture->json('total'));
    }

    public function test_patch_seuil_enregistre_et_retire_le_seuil_local(): void
    {
        $niveau = Niveau::factory()->create(['seuil' => null]);
        $niveau->article->update(['seuil_defaut' => 9]);
        $user = $this->makeUser(['stock.niveaux.seuil']);

        $this->actingAs($user)
            ->patchJson(route('stock.niveaux.seuil', $niveau->id), ['seuil' => 12])
            ->assertOk()
            ->assertJsonPath('data.seuil_origine', 'local');

        $this->assertSame(12.0, (float) $niveau->fresh()->seuil);

        // Vider le champ = retour à l'héritage du seuil article
        $this->actingAs($user)
            ->patchJson(route('stock.niveaux.seuil', $niveau->id), ['seuil' => null])
            ->assertOk()
            ->assertJsonPath('data.seuil_origine', 'article');

        $this->assertNull($niveau->fresh()->seuil);
    }

    public function test_seuil_negatif_refuse(): void
    {
        $niveau = Niveau::factory()->create();
        $user = $this->makeUser(['stock.niveaux.seuil']);

        $this->actingAs($user)
            ->patchJson(route('stock.niveaux.seuil', $niveau->id), ['seuil' => -2])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['seuil']);
    }

    public function test_seuil_article_jamais_recu_cree_la_ligne_a_zero(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $user = $this->makeUser(['stock.niveaux.seuil']);

        $this->actingAs($user)
            ->postJson(route('stock.niveaux.seuil-article'), [
                'magasin_id' => $magasin->id,
                'article_id' => $article->id,
                'seuil' => 5,
            ])
            ->assertOk();

        $this->assertDatabaseHas('stock_niveaux', [
            'magasin_id' => $magasin->id,
            'article_id' => $article->id,
            'quantite' => 0,
            'seuil' => 5,
        ]);

        // Idempotent sur ligne existante : le seuil se met à jour sans doublon
        $this->actingAs($user)
            ->postJson(route('stock.niveaux.seuil-article'), [
                'magasin_id' => $magasin->id,
                'article_id' => $article->id,
                'seuil' => 8,
            ])
            ->assertOk();

        $this->assertSame(1, Niveau::query()->where('magasin_id', $magasin->id)->where('article_id', $article->id)->count());
    }

    public function test_seuil_article_non_stockable_refuse(): void
    {
        $magasin = Magasin::factory()->create();
        $licence = Article::factory()->licence()->create();
        $user = $this->makeUser(['stock.niveaux.seuil']);

        $this->actingAs($user)
            ->postJson(route('stock.niveaux.seuil-article'), [
                'magasin_id' => $magasin->id,
                'article_id' => $licence->id,
                'seuil' => 5,
            ])
            ->assertStatus(422);

        $this->assertSame(0, Niveau::query()->where('article_id', $licence->id)->count());
    }

    public function test_flag_sous_inventaire_perimetre_magasin_et_selection(): void
    {
        $magasin = Magasin::factory()->create();
        $niveau = Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 5]);

        $autreMagasin = Magasin::factory()->create();
        $niveauSelectionne = Niveau::factory()->create(['magasin_id' => $autreMagasin->id, 'quantite' => 5]);
        $niveauLibre = Niveau::factory()->create(['magasin_id' => $autreMagasin->id, 'quantite' => 5]);

        // Inventaire « tout le magasin » sur le premier magasin
        Inventaire::factory()->create(['magasin_id' => $magasin->id, 'perimetre' => Inventaire::PERIMETRE_MAGASIN]);

        // Inventaire « sélection » couvrant un seul article de l'autre magasin
        $selection = Inventaire::factory()->create(['magasin_id' => $autreMagasin->id, 'perimetre' => Inventaire::PERIMETRE_SELECTION]);
        LigneInventaire::factory()->create(['inventaire_id' => $selection->id, 'article_id' => $niveauSelectionne->article_id]);

        $user = $this->makeUser(['stock.niveaux.index']);
        $rows = collect($this->actingAs($user)
            ->getJson(route('stock.niveaux.data', ['limit' => 50]))
            ->json('rows'))->keyBy('id');

        $this->assertTrue($rows[$niveau->id]['sous_inventaire']);
        $this->assertTrue($rows[$niveauSelectionne->id]['sous_inventaire']);
        $this->assertFalse($rows[$niveauLibre->id]['sous_inventaire']);
        $this->assertNotNull($rows[$niveau->id]['inventaire_reference']);
    }

    public function test_export_csv_imprime_les_filtres_en_entete(): void
    {
        $magasin = Magasin::factory()->create(['libelle' => 'Magasin Export Test']);
        Niveau::factory()->create(['magasin_id' => $magasin->id, 'quantite' => 5]);
        $user = $this->makeUser(['stock.niveaux.index']);

        $reponse = $this->actingAs($user)
            ->get(route('stock.niveaux.export', ['format' => 'csv', 'magasin_id' => $magasin->id, 'statut' => 'OK']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $contenu = $reponse->streamedContent();

        // Amendement UX n°18 : les filtres actifs figurent en en-tête
        $this->assertStringContainsString('Magasin : Magasin Export Test', $contenu);
        $this->assertStringContainsString('Statut d\'alerte : ✓ OK', $contenu);
        $this->assertStringContainsString('Seuil effectif', $contenu);
    }

    public function test_export_pdf_repond(): void
    {
        Niveau::factory()->create(['quantite' => 5]);
        $user = $this->makeUser(['stock.niveaux.index']);

        $this->actingAs($user)
            ->get(route('stock.niveaux.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
