<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Core\Models\User;
use Tests\TestCase;

/**
 * P0-A — la nature « prestation » (PRQ-02, C9/C10).
 *
 * Le critère du recueil : un article prestation se crée, se recherche, se
 * commande (fixture pour Achat) et ne peut JAMAIS toucher Stock.
 */
class ArticlePrestationTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->categorie = Categorie::create(['libelle' => 'Services']);
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

    private function payloadPrestation(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Maintenance annuelle onduleurs',
            'nature' => 'prestation',
            'categorie_id' => $this->categorie->id,
            'prix_indicatif' => 1500000,
            'taux_tva' => 18,
        ], $surcharge);
    }

    // ═══ Création, code, immatérialité ══════════════════════════════════════

    public function test_une_prestation_se_cree_avec_un_code_pre(): void
    {
        $this->actingAs($this->userWith('catalogue.articles.store'))
            ->postJson(route('catalogue.articles.store'), $this->payloadPrestation())
            ->assertOk();

        $article = Article::query()->where('nature', 'prestation')->firstOrFail();

        $this->assertMatchesRegularExpression('/^PRE-\d{5}$/', $article->code);
        $this->assertFalse($article->est_stockable, 'Une prestation est immatérielle : jamais stockable.');
        $this->assertSame('unité', $article->unite_stock);
    }

    public function test_les_champs_de_stock_sont_interdits_sur_une_prestation(): void
    {
        $this->actingAs($this->userWith('catalogue.articles.store'))
            ->postJson(route('catalogue.articles.store'), $this->payloadPrestation([
                'seuil_defaut' => 5,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['seuil_defaut']);
    }

    public function test_logiciel_et_categorie_equipement_sont_interdits(): void
    {
        $this->actingAs($this->userWith('catalogue.articles.store'))
            ->postJson(route('catalogue.articles.store'), $this->payloadPrestation([
                'logiciel_id' => 1,
                'categorie_equipement_id' => 1,
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['logiciel_id', 'categorie_equipement_id']);
    }

    /** La garde applicative double le CHECK : écrire un seuil lève l'exception. */
    public function test_le_modele_refuse_un_seuil_sur_une_prestation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/réservé aux natures stockables/');

        Article::create([
            'nom' => 'Formation bureautique',
            'nature' => 'prestation',
            'categorie_id' => $this->categorie->id,
            'seuil_defaut' => 3,
        ]);
    }

    // ═══ Recherche et API (contrat §2.1) ════════════════════════════════════

    public function test_l_api_expose_et_filtre_la_nature_prestation(): void
    {
        Article::create($this->payloadPrestation());
        Article::create([
            'nom' => 'Toner noir',
            'nature' => 'consommable',
            'categorie_id' => $this->categorie->id,
            'unite_stock' => 'unité',
        ]);

        $lecteur = $this->userWith('catalogue.api.view');

        $donnees = $this->actingAs($lecteur)
            ->getJson(route('catalogue.api.articles', ['nature' => 'prestation']))
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $donnees);
        $this->assertSame('prestation', $donnees[0]['nature']);
        $this->assertStringStartsWith('PRE-', $donnees[0]['code']);
    }

    /** Fixture pour Achat : une prestation se met sur un bon de commande. */
    public function test_une_prestation_se_commande_dans_achat(): void
    {
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatParametresSeeder::class);

        $prestation = Article::create($this->payloadPrestation());
        $bon = \Modules\Achat\Models\BonCommande::factory()->create();

        app(\Modules\Achat\Services\LignesBonCommandeService::class)->synchroniser($bon, [
            ['article_id' => $prestation->id, 'quantite' => 1, 'prix_unitaire_ht' => 1500000],
        ]);

        $ligne = $bon->lignes()->firstOrFail();
        $this->assertSame('prestation', $ligne->nature);
        $this->assertTrue($ligne->estPrestation());
    }

    // ═══ La frontière : une prestation ne touche JAMAIS Stock ═══════════════

    public function test_stock_refuse_une_prestation_a_l_entree(): void
    {
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $magasinier = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'mag_'.uniqid(),
            'email' => uniqid().'@example.com', 'password' => bcrypt('password'),
        ]);
        $magasinier->assignRole('Magasinier');

        $prestation = Article::create($this->payloadPrestation());
        $magasin = \Modules\Stock\Models\Magasin::factory()->create();

        $reponse = $this->actingAs($magasinier)
            ->postJson(route('stock.entrees.store'), [
                'magasin_id' => $magasin->id,
                'date_document' => now()->format('Y-m-d'),
                'nature' => 'livraison',
                'lignes' => [
                    ['article_id' => $prestation->id, 'quantite' => 1, 'cout_unitaire' => 1500000],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lignes.0.article_id']);

        $this->assertStringContainsString(
            'n\'est pas stockable',
            $reponse->json('errors')['lignes.0.article_id'][0]
        );
    }
}
