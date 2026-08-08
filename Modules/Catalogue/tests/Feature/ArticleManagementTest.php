<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Database\Factories\ArticleFactory;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Marque;
use Tests\TestCase;

class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Categorie $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->categorie = Categorie::create(['libelle' => 'Générale']);
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

    private function categorieEquipement(): CategorieEquipement
    {
        return CategorieEquipement::firstOrCreate(['code' => 'ORDI'], ['libelle' => 'Ordinateurs']);
    }

    private function payloadConsommable(array $surcharge = []): array
    {
        return array_merge([
            'nom' => 'Toner test '.uniqid(),
            'nature' => Article::NATURE_CONSOMMABLE,
            'categorie_id' => $this->categorie->id,
            'unite_stock' => 'cartouche',
        ], $surcharge);
    }

    public function test_matrice_de_permissions_toutes_les_routes_sont_protegees(): void
    {
        $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);
        $sansDroit = $this->userWith();

        $matrice = [
            ['get', route('catalogue.articles.index')],
            ['get', route('catalogue.articles.data')],
            ['get', route('catalogue.articles.categories-cascade')],
            ['get', route('catalogue.articles.marques')],
            ['get', route('catalogue.articles.categories-equipements')],
            ['get', route('catalogue.articles.logiciels')],
            ['get', route('catalogue.articles.show', $article->id)],
            ['post', route('catalogue.articles.store')],
            ['put', route('catalogue.articles.update', $article->id)],
            ['delete', route('catalogue.articles.destroy', $article->id)],
            ['patch', route('catalogue.articles.toggle-status', $article->id)],
        ];

        foreach ($matrice as [$methode, $url]) {
            $this->actingAs($sansDroit)->json($methode, $url)->assertStatus(403);
        }
    }

    public function test_creation_nominale_des_quatre_natures(): void
    {
        $utilisateur = $this->userWith('catalogue.articles.store');

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), $this->payloadConsommable([
                'seuil_defaut' => 5,
                'compatibilites' => [$this->categorieEquipement()->code],
            ]))
            ->assertStatus(200)->assertJsonPath('success', true);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), $this->payloadConsommable([
                'nature' => Article::NATURE_PIECE,
                'unite_stock' => 'unité',
            ]))
            ->assertStatus(200);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), [
                'nom' => 'Portable test',
                'nature' => Article::NATURE_EQUIPEMENT,
                'categorie_id' => $this->categorie->id,
                'categorie_equipement_id' => $this->categorieEquipement()->id,
            ])
            ->assertStatus(200);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), [
                'nom' => 'Licence test',
                'nature' => Article::NATURE_LICENCE,
                'categorie_id' => $this->categorie->id,
                'logiciel_id' => ArticleFactory::logicielDeReference()->id,
            ])
            ->assertStatus(200);

        $codes = Article::pluck('code');
        $this->assertSame(4, $codes->count());
        foreach (['CONS-', 'PIE-', 'EQP-', 'LIC-'] as $prefixe) {
            $this->assertTrue($codes->contains(fn ($c) => str_starts_with($c, $prefixe)), "Préfixe {$prefixe} absent");
        }
    }

    public function test_le_modele_est_enregistre_modifiable_et_restitue(): void
    {
        $utilisateur = $this->userWith(
            'catalogue.articles.store',
            'catalogue.articles.update',
            'catalogue.articles.index',
        );

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), [
                'nom' => 'Ordinateur portable Dell',
                'nature' => Article::NATURE_EQUIPEMENT,
                'categorie_id' => $this->categorie->id,
                'categorie_equipement_id' => $this->categorieEquipement()->id,
                'modele' => 'Latitude 3540',
            ])
            ->assertStatus(200)->assertJsonPath('success', true);

        $article = Article::where('nom', 'Ordinateur portable Dell')->firstOrFail();
        $this->assertSame('Latitude 3540', $article->modele);

        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.articles.show', $article->id))
            ->assertJsonPath('data.modele', 'Latitude 3540');

        $this->actingAs($utilisateur)
            ->putJson(route('catalogue.articles.update', $article->id), [
                'nom' => $article->nom,
                'categorie_id' => $this->categorie->id,
                'categorie_equipement_id' => $this->categorieEquipement()->id,
                'modele' => 'Latitude 5550',
            ])
            ->assertStatus(200);

        $this->assertSame('Latitude 5550', $article->fresh()->modele);
    }

    public function test_regles_conditionnelles_un_refus_par_regle(): void
    {
        $utilisateur = $this->userWith('catalogue.articles.store');
        $refus = [
            // equipement sans catégorie d'équipements
            [['nom' => 'X', 'nature' => 'equipement', 'categorie_id' => $this->categorie->id], 'categorie_equipement_id'],
            // equipement avec seuil (interdit)
            [['nom' => 'X', 'nature' => 'equipement', 'categorie_id' => $this->categorie->id, 'categorie_equipement_id' => $this->categorieEquipement()->id, 'seuil_defaut' => 3], 'seuil_defaut'],
            // equipement avec compatibilités (interdites)
            [['nom' => 'X', 'nature' => 'equipement', 'categorie_id' => $this->categorie->id, 'categorie_equipement_id' => $this->categorieEquipement()->id, 'compatibilites' => ['ORDI']], 'compatibilites'],
            // licence sans logiciel
            [['nom' => 'X', 'nature' => 'licence', 'categorie_id' => $this->categorie->id], 'logiciel_id'],
            // consommable sans unité
            [$this->payloadConsommable(['unite_stock' => null]), 'unite_stock'],
            // consommable avec catégorie d'équipements (interdite)
            [$this->payloadConsommable(['categorie_equipement_id' => $this->categorieEquipement()->id]), 'categorie_equipement_id'],
            // pièce avec logiciel (interdit)
            [$this->payloadConsommable(['nature' => 'piece', 'logiciel_id' => ArticleFactory::logicielDeReference()->id]), 'logiciel_id'],
            // compatibilité inconnue
            [$this->payloadConsommable(['compatibilites' => ['CODE-INEXISTANT']]), 'compatibilites.0'],
            // TVA hors bornes et prix négatif
            [$this->payloadConsommable(['taux_tva' => 150]), 'taux_tva'],
            [$this->payloadConsommable(['prix_indicatif' => -5]), 'prix_indicatif'],
        ];

        foreach ($refus as [$payload, $champ]) {
            $this->actingAs($utilisateur)
                ->postJson(route('catalogue.articles.store'), $payload)
                ->assertStatus(422)
                ->assertJsonValidationErrors([$champ]);
        }

        $this->assertSame(0, Article::count());
    }

    public function test_licence_avec_logiciel_inactif_refusee(): void
    {
        $logiciel = ArticleFactory::logicielDeReference();
        $logiciel->update(['est_actif' => false]);

        $this->actingAs($this->userWith('catalogue.articles.store'))
            ->postJson(route('catalogue.articles.store'), [
                'nom' => 'Licence morte',
                'nature' => 'licence',
                'categorie_id' => $this->categorie->id,
                'logiciel_id' => $logiciel->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['logiciel_id']);
    }

    public function test_unicite_code_global_et_reference_par_marque(): void
    {
        $utilisateur = $this->userWith('catalogue.articles.store');
        $marque = Marque::create(['libelle' => 'HP']);
        Article::factory()->create(['categorie_id' => $this->categorie->id, 'code' => 'CONS-99999']);
        Article::factory()->avecMarque()->create(['categorie_id' => $this->categorie->id, 'marque_id' => $marque->id, 'reference_constructeur' => 'CE285A']);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), $this->payloadConsommable(['code' => 'CONS-99999']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), $this->payloadConsommable([
                'marque_id' => $marque->id,
                'reference_constructeur' => 'CE285A',
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['reference_constructeur']);

        // La même référence sur une AUTRE marque est permise
        $autre = Marque::create(['libelle' => 'Canon']);
        $this->actingAs($utilisateur)
            ->postJson(route('catalogue.articles.store'), $this->payloadConsommable([
                'marque_id' => $autre->id,
                'reference_constructeur' => 'CE285A',
            ]))
            ->assertStatus(200);
    }

    public function test_update_ignore_nature_et_code_c6(): void
    {
        $article = Article::factory()->create(['categorie_id' => $this->categorie->id]);
        $codeInitial = $article->code;

        $this->actingAs($this->userWith('catalogue.articles.update'))
            ->putJson(route('catalogue.articles.update', $article->id), [
                'nom' => 'Nom modifié',
                'categorie_id' => $this->categorie->id,
                'unite_stock' => 'boîte',
                'nature' => Article::NATURE_PIECE,
                'code' => 'HACK-00001',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        $article->refresh();
        $this->assertSame('Nom modifié', $article->nom);
        $this->assertSame(Article::NATURE_CONSOMMABLE, $article->nature);
        $this->assertSame($codeInitial, $article->code);
    }

    public function test_update_reference_par_marque_ignore_sa_propre_ligne(): void
    {
        $marque = Marque::create(['libelle' => 'HP']);
        $article = Article::factory()->create([
            'categorie_id' => $this->categorie->id,
            'marque_id' => $marque->id,
            'reference_constructeur' => 'CE285A',
        ]);

        $this->actingAs($this->userWith('catalogue.articles.update'))
            ->putJson(route('catalogue.articles.update', $article->id), [
                'nom' => $article->nom,
                'categorie_id' => $this->categorie->id,
                'unite_stock' => 'cartouche',
                'marque_id' => $marque->id,
                'reference_constructeur' => 'CE285A',
            ])
            ->assertStatus(200);
    }

    public function test_destroy_sans_reference_puis_garde_multi_modules(): void
    {
        $utilisateur = $this->userWith('catalogue.articles.destroy');

        // Sans référence aval (tables Stock/Achat absentes) : suppression OK
        $libre = Article::factory()->create(['categorie_id' => $this->categorie->id]);
        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.articles.destroy', $libre->id))
            ->assertStatus(200)
            ->assertJsonPath('success', true);

        // Avec affectations ParcInfo + une table Stock créée à la volée
        $article = Article::factory()->create(['categorie_id' => $this->categorie->id, 'nom' => 'Toner HP 85A']);

        $equipementId = DB::table('parc_info_equipements')->insertGetId([
            'code_inventaire' => 'INV-1', 'numero_serie' => 'SN-1', 'modele' => 'X',
            'statut' => 'en_service', 'etat' => 'bon', 'created_at' => now(), 'updated_at' => now(),
        ]);
        for ($i = 0; $i < 2; $i++) {
            DB::table('parc_info_affectations_consommables')->insert([
                'consommable_id' => null, 'article_id' => $article->id, 'equipement_id' => $equipementId,
                'quantite_fournie' => 1, 'date_affectation' => now()->toDateString(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Le module Stock est installé : un vrai mouvement référence l'article.
        // (Avant son installation, ce test créait une table factice à la volée.)
        \Modules\Stock\Models\Mouvement::factory()->create(['article_id' => $article->id]);

        $this->actingAs($utilisateur)
            ->deleteJson(route('catalogue.articles.destroy', $article->id))
            ->assertStatus(422)
            ->assertJsonFragment([
                'message' => "L'article {$article->code} — Toner HP 85A est référencé par : 1 mouvement(s) de stock, 2 affectation(s). Vous pouvez le désactiver.",
            ]);

        $this->assertDatabaseHas('catalogue_articles', ['id' => $article->id]);
    }

    public function test_filtre_categorie_de_niveau_1_inclut_ses_enfants(): void
    {
        $enfant = Categorie::create(['libelle' => 'Toners', 'parent_id' => $this->categorie->id]);
        $autre = Categorie::create(['libelle' => 'Réseau']);

        Article::factory()->create(['categorie_id' => $this->categorie->id, 'nom' => 'Direct']);
        Article::factory()->create(['categorie_id' => $enfant->id, 'nom' => 'Dans enfant']);
        Article::factory()->create(['categorie_id' => $autre->id, 'nom' => 'Ailleurs']);

        $utilisateur = $this->userWith('catalogue.articles.index');

        // Niveau 1 → inclut la sous-catégorie
        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.articles.data', ['categorie_id' => $this->categorie->id]))
            ->assertJsonPath('total', 2);

        // Feuille → elle seule
        $this->actingAs($utilisateur)
            ->getJson(route('catalogue.articles.data', ['categorie_id' => $enfant->id]))
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.nom', 'Dans enfant');
    }

    public function test_get_data_champs_filtres_et_kpis(): void
    {
        $marque = Marque::create(['libelle' => 'HP']);
        Article::factory()->create([
            'categorie_id' => $this->categorie->id,
            'nom' => 'Toner 85A',
            'marque_id' => $marque->id,
            'reference_constructeur' => 'CE285A',
            'prix_indicatif' => 45000,
        ]);
        Article::factory()->equipement()->create(['categorie_id' => $this->categorie->id]);

        $reponse = $this->actingAs($this->userWith('catalogue.articles.index'))
            ->getJson(route('catalogue.articles.data', ['search' => 'ce285a']))
            ->assertStatus(200)
            ->assertJsonStructure(['total', 'rows', 'kpis'])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('kpis.consommable', 1)
            ->assertJsonPath('kpis.equipement', 1);

        $ligne = $reponse->json('rows.0');
        $this->assertSame('Toner 85A', $ligne['nom']);
        $this->assertSame('Consommable', $ligne['nature_label']);
        $this->assertSame('HP', $ligne['marque_libelle']);
        $this->assertSame('Générale', $ligne['categorie_chemin']);
        $this->assertSame('45000.00', $ligne['prix_indicatif']);

        $this->actingAs($this->userWith('catalogue.articles.index'))
            ->getJson(route('catalogue.articles.data', ['nature' => 'equipement']))
            ->assertJsonPath('total', 1);
    }
}
