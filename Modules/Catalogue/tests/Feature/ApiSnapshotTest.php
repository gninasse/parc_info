<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Factories\ArticleFactory;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Tests\TestCase;

/**
 * SNAPSHOTS CONTRACTUELS de l'API inter-modules (SFD §2.5).
 *
 * Ces tests figent le format EXACT des réponses consommées par les autres
 * modules (Stock, Achat, ParcInfo). Toute modification qui les casse est une
 * rupture de contrat : elle doit être délibérée, versionnée et coordonnée
 * avec les modules consommateurs.
 */
class ApiSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $utilisateur;

    private Categorie $impression;

    private Categorie $toners;

    private Fournisseur $sitb;

    private Article $toner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);

        $this->utilisateur = User::create([
            'name' => 'Api',
            'last_name' => 'User',
            'user_name' => 'api_user',
            'email' => 'api@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->utilisateur->givePermissionTo('catalogue.api.view');

        $this->impression = Categorie::create(['code' => 'CAT-001', 'libelle' => 'Impression']);
        $this->toners = Categorie::create(['code' => 'CAT-002', 'libelle' => 'Toners', 'parent_id' => $this->impression->id]);
        Categorie::create(['code' => 'CAT-003', 'libelle' => 'Catégorie inactive', 'est_actif' => false]);

        $this->sitb = Fournisseur::create([
            'code' => 'FOUR-SI001',
            'raison_sociale' => 'SITB Burkina',
            'telephone' => '+226 70 12 34 56',
            'email' => 'contact@sitb.bf',
        ]);
        Fournisseur::create(['code' => 'FOUR-XX001', 'raison_sociale' => 'Fournisseur inactif', 'est_actif' => false]);

        $this->toner = Article::create([
            'code' => 'CONS-00001',
            'nom' => 'Toner HP 85A noir',
            'nature' => Article::NATURE_CONSOMMABLE,
            'categorie_id' => $this->toners->id,
            'unite_stock' => 'cartouche',
            'prix_indicatif' => 45000,
            'seuil_defaut' => 5,
            'fournisseur_principal_id' => $this->sitb->id,
        ]);
        Article::create([
            'code' => 'LIC-00001',
            'nom' => 'Licence Windows 11 Pro',
            'nature' => Article::NATURE_LICENCE,
            'categorie_id' => $this->impression->id,
            'logiciel_id' => ArticleFactory::logicielDeReference()->id,
        ]);
        Article::create([
            'code' => 'PIE-00001',
            'nom' => 'Article inactif',
            'nature' => Article::NATURE_PIECE,
            'categorie_id' => $this->impression->id,
            'est_actif' => false,
        ]);
    }

    public function test_snapshot_articles(): void
    {
        $logicielId = ArticleFactory::logicielDeReference()->id;

        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles'))
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $this->toner->id,
                        'code' => 'CONS-00001',
                        'nom' => 'Toner HP 85A noir',
                        'nature' => 'consommable',
                        'unite_stock' => 'cartouche',
                        'seuil_defaut' => '5.00',
                        'prix_indicatif' => '45000.00',
                        'taux_tva' => '18.00',
                        'categorie' => 'Impression > Toners',
                        'fournisseur_principal_id' => $this->sitb->id,
                        'categorie_equipement_id' => null,
                        'logiciel_id' => null,
                    ],
                    [
                        'id' => Article::where('code', 'LIC-00001')->value('id'),
                        'code' => 'LIC-00001',
                        'nom' => 'Licence Windows 11 Pro',
                        'nature' => 'licence',
                        'unite_stock' => 'unité',
                        'seuil_defaut' => null,
                        'prix_indicatif' => null,
                        'taux_tva' => '18.00',
                        'categorie' => 'Impression',
                        'fournisseur_principal_id' => null,
                        'categorie_equipement_id' => null,
                        'logiciel_id' => $logicielId,
                    ],
                ],
                'total' => 2,
            ]);
    }

    public function test_articles_filtres_q_nature_est_actif_et_limite_bornee(): void
    {
        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles', ['q' => 'TONER']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.code', 'CONS-00001');

        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles', ['nature' => 'licence']))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.nature', 'licence');

        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles', ['est_actif' => 0]))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.code', 'PIE-00001');

        // limit borné à 200 : la requête n'échoue pas et reste bornée
        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles', ['limit' => 9999]))
            ->assertStatus(200)
            ->assertJsonPath('total', 2);
    }

    public function test_snapshot_article_fiche_compacte_et_404_propre(): void
    {
        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles.show', $this->toner->id))
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    'id' => $this->toner->id,
                    'code' => 'CONS-00001',
                    'nom' => 'Toner HP 85A noir',
                    'nature' => 'consommable',
                    'unite_stock' => 'cartouche',
                    'seuil_defaut' => '5.00',
                    'prix_indicatif' => '45000.00',
                    'taux_tva' => '18.00',
                    'categorie' => 'Impression > Toners',
                    'fournisseur_principal_id' => $this->sitb->id,
                    'categorie_equipement_id' => null,
                    'logiciel_id' => null,
                ],
            ]);

        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.articles.show', 999999))
            ->assertStatus(404)
            ->assertExactJson(['message' => 'Article introuvable.']);
    }

    public function test_snapshot_categories_arbre_2_niveaux_actives_seulement(): void
    {
        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.categories'))
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $this->impression->id,
                        'code' => 'CAT-001',
                        'libelle' => 'Impression',
                        'enfants' => [
                            [
                                'id' => $this->toners->id,
                                'code' => 'CAT-002',
                                'libelle' => 'Toners',
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_snapshot_fournisseurs_actifs_par_defaut(): void
    {
        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.fournisseurs'))
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    [
                        'id' => $this->sitb->id,
                        'code' => 'FOUR-SI001',
                        'raison_sociale' => 'SITB Burkina',
                        'telephone' => '+226 70 12 34 56',
                        'email' => 'contact@sitb.bf',
                    ],
                ],
                'total' => 1,
            ]);

        $this->actingAs($this->utilisateur)
            ->getJson(route('catalogue.api.fournisseurs', ['q' => 'introuvable']))
            ->assertExactJson(['data' => [], 'total' => 0]);
    }

    public function test_api_refusee_sans_la_permission_catalogue_api_view(): void
    {
        $sansDroit = User::create([
            'name' => 'Sans',
            'last_name' => 'Droit',
            'user_name' => 'sans_droit',
            'email' => 'sans@example.com',
            'password' => bcrypt('password'),
        ]);

        foreach (['catalogue.api.articles', 'catalogue.api.categories', 'catalogue.api.fournisseurs'] as $route) {
            $this->actingAs($sansDroit)->getJson(route($route))->assertStatus(403);
        }

        $this->actingAs($sansDroit)
            ->getJson(route('catalogue.api.articles.show', $this->toner->id))
            ->assertStatus(403);
    }
}
