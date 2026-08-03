<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\TamponEquipement;
use Tests\TestCase;

/**
 * Sorties — matrice de permissions, pointage I17 et scan express (D14/D17).
 */
class SortiePointageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Magasin $magasin;

    private Article $modele;

    private CategorieEquipement $categorie;

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

        $this->magasin = Magasin::factory()->create();
        $this->modele = Article::factory()->equipement()->create(['modele' => 'ProDesk 400']);
        $this->categorie = CategorieEquipement::query()->find($this->modele->categorie_equipement_id);
    }

    /** Unité « en stock » rattachée au magasin, du bon modèle. */
    private function uniteEnStock(?Magasin $magasin = null, array $attributs = []): \Modules\ParcInfo\Models\Equipement
    {
        $unite = ParcInfoDeTest::equipement(array_merge([
            'categorie_id' => $this->categorie->id,
            'modele' => 'ProDesk 400',
        ], $attributs));

        EquipementMagasin::create([
            'equipement_id' => $unite->id,
            'magasin_id' => ($magasin ?? $this->magasin)->id,
            'date_rattachement' => now(),
        ]);

        return $unite;
    }

    private function sortieEnPointage(int $quantite = 2): Sortie
    {
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneSortie::factory()->create([
            'sortie_id' => $sortie->id,
            'article_id' => $this->modele->id,
            'quantite' => $quantite,
        ]);
        $sortie->passerEnPointage();

        return $sortie->refresh();
    }

    public static function routesProtegees(): array
    {
        return [
            'index' => ['GET', 'stock.sorties.index', [], 'stock.sorties.index'],
            'data' => ['GET', 'stock.sorties.data', [], 'stock.sorties.index'],
            'show' => ['GET', 'stock.sorties.show', ['id' => true], 'stock.sorties.index'],
            'create' => ['GET', 'stock.sorties.create', [], 'stock.sorties.store'],
            'store' => ['POST', 'stock.sorties.store', [], 'stock.sorties.store'],
            'edit' => ['GET', 'stock.sorties.edit', ['id' => true], 'stock.sorties.update'],
            'update' => ['PUT', 'stock.sorties.update', ['id' => true], 'stock.sorties.update'],
            'destroy' => ['DELETE', 'stock.sorties.destroy', ['id' => true], 'stock.sorties.destroy'],
            'pointage POST' => ['POST', 'stock.sorties.pointage', ['id' => true], 'stock.sorties.store'],
            'pointage GET' => ['GET', 'stock.sorties.pointage.show', ['id' => true], 'stock.sorties.store'],
            'pointage PUT' => ['PUT', 'stock.sorties.pointage.update', ['id' => true], 'stock.sorties.store'],
            'scan express' => ['POST', 'stock.sorties.scan-express', ['id' => true], 'stock.sorties.store'],
            'retour brouillon' => ['POST', 'stock.sorties.retour-brouillon', ['id' => true], 'stock.sorties.store'],
            'valider' => ['POST', 'stock.sorties.valider', ['id' => true], 'stock.sorties.store'],
            'pdf' => ['GET', 'stock.sorties.pdf', ['id' => true], 'stock.sorties.store'],
            'disponibilite' => ['GET', 'stock.sorties.disponibilite', [], 'stock.sorties.store'],
            'du-magasin' => ['GET', 'stock.equipements.du-magasin', [], 'stock.sorties.store'],
            'beneficiaires' => ['GET', 'stock.beneficiaires.data', [], 'stock.sorties.store'],
        ];
    }

    /** @dataProvider routesProtegees */
    public function test_matrice_403_sans_permission_et_redirection_invite(string $methode, string $route, array $params, string $permission): void
    {
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        $params = array_map(fn ($valeur) => $valeur === true ? $sortie->id : $valeur, $params);
        $url = route($route, $params);

        $this->app['auth']->forgetGuards();
        $this->call($methode, $url)->assertRedirect(route('login'));

        $sans = User::create([
            'name' => 'S', 'last_name' => 'P', 'user_name' => 'sans_'.md5($route.$methode),
            'email' => 'sans-'.md5($route.$methode).'@example.com', 'password' => bcrypt('x'),
        ]);
        $this->actingAs($sans)->json($methode, $url)->assertStatus(403);

        $avec = User::create([
            'name' => 'A', 'last_name' => 'P', 'user_name' => 'avec_'.md5($route.$methode),
            'email' => 'avec-'.md5($route.$methode).'@example.com', 'password' => bcrypt('x'),
        ]);
        $avec->givePermissionTo($permission);
        $statut = $this->actingAs($avec)->json($methode, $url)->getStatusCode();
        $this->assertContains($statut, [200, 201, 302, 409, 422], "Route {$route} : statut {$statut} inattendu avec permission.");
    }

    public function test_invariant_I17_pointage_nominal(): void
    {
        $sortie = $this->sortieEnPointage(2);
        $unite = $this->uniteEnStock();
        $ligne = $sortie->lignes()->first();

        $this->actingAs($this->user)
            ->putJson(route('stock.sorties.pointage.update', $sortie->id), [
                'action' => 'pointer',
                'ligne_id' => $ligne->id,
                'equipement_id' => $unite->id,
            ])
            ->assertOk()
            ->assertJsonPath('statut_pointage.pointees', 1)
            ->assertJsonPath('statut_pointage.attendues', 2);

        $this->assertDatabaseHas('stock_tampon_equipements', [
            'ligne_sortie_id' => $ligne->id,
            'equipement_id' => $unite->id,
        ]);
    }

    public function test_invariant_I17_unite_d_un_autre_magasin_refusee(): void
    {
        $sortie = $this->sortieEnPointage();
        $uniteAilleurs = $this->uniteEnStock(Magasin::factory()->create());

        $this->actingAs($this->user)
            ->putJson(route('stock.sorties.pointage.update', $sortie->id), [
                'action' => 'pointer',
                'ligne_id' => $sortie->lignes()->first()->id,
                'equipement_id' => $uniteAilleurs->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Introuvable dans ce magasin');
    }

    public function test_invariant_I17_unite_non_en_stock_refusee(): void
    {
        $sortie = $this->sortieEnPointage();
        $unite = $this->uniteEnStock(attributs: ['statut' => 'en_reparation']);

        $this->actingAs($this->user)
            ->putJson(route('stock.sorties.pointage.update', $sortie->id), [
                'action' => 'pointer',
                'ligne_id' => $sortie->lignes()->first()->id,
                'equipement_id' => $unite->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Unité non « en stock »');
    }

    public function test_invariant_I17_unite_deja_pointee_dans_un_autre_bon_refusee(): void
    {
        $unite = $this->uniteEnStock();

        // Pointée dans un premier bon non validé
        $premiere = $this->sortieEnPointage();
        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $premiere->id), [
            'action' => 'pointer', 'ligne_id' => $premiere->lignes()->first()->id, 'equipement_id' => $unite->id,
        ])->assertOk();

        // Refusée dans un second
        $seconde = $this->sortieEnPointage();
        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $seconde->id), [
            'action' => 'pointer', 'ligne_id' => $seconde->lignes()->first()->id, 'equipement_id' => $unite->id,
        ])->assertStatus(422)->assertJsonPath('message', 'Déjà pointé');
    }

    public function test_ligne_complete_refuse_une_unite_de_plus(): void
    {
        $sortie = $this->sortieEnPointage(1);
        $ligne = $sortie->lignes()->first();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $ligne->id, 'equipement_id' => $this->uniteEnStock()->id,
        ])->assertOk();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $ligne->id, 'equipement_id' => $this->uniteEnStock()->id,
        ])->assertStatus(422);
    }

    public function test_depointer_libere_l_unite(): void
    {
        $sortie = $this->sortieEnPointage();
        $unite = $this->uniteEnStock();
        $ligne = $sortie->lignes()->first();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $ligne->id, 'equipement_id' => $unite->id,
        ])->assertOk();

        $tampon = TamponEquipement::query()->where('equipement_id', $unite->id)->first();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'depointer', 'tampon_id' => $tampon->id,
        ])->assertOk()->assertJsonPath('statut_pointage.pointees', 0);

        $this->assertDatabaseMissing('stock_tampon_equipements', ['id' => $tampon->id]);
    }

    public function test_scan_express_cree_une_ligne_modele_x1_pre_pointee(): void
    {
        // Brouillon (D17) : un scan = ligne modèle × 1 créée et pré-pointée
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        $unite = $this->uniteEnStock(attributs: ['numero_serie' => 'SN-EXPRESS-1']);

        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.sorties.scan-express', $sortie->id), ['numero_serie' => 'SN-EXPRESS-1'])
            ->assertOk();

        $this->assertStringContainsString('SN-EXPRESS-1 ajouté', $reponse->json('message'));

        $ligne = $sortie->lignes()->first();
        $this->assertSame($this->modele->id, $ligne->article_id);
        $this->assertSame(1.0, (float) $ligne->quantite);
        $this->assertDatabaseHas('stock_tampon_equipements', [
            'ligne_sortie_id' => $ligne->id,
            'equipement_id' => $unite->id,
        ]);
    }

    public function test_scan_express_messages_d_erreur_exacts(): void
    {
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);

        // Introuvable dans ce magasin (numéro inconnu)
        $this->actingAs($this->user)
            ->postJson(route('stock.sorties.scan-express', $sortie->id), ['numero_serie' => 'SN-INCONNU'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Introuvable dans ce magasin');

        // Déjà pointé
        $unite = $this->uniteEnStock(attributs: ['numero_serie' => 'SN-DEJA']);
        $this->actingAs($this->user)->postJson(route('stock.sorties.scan-express', $sortie->id), ['numero_serie' => 'SN-DEJA'])->assertOk();
        $this->actingAs($this->user)
            ->postJson(route('stock.sorties.scan-express', $sortie->id), ['numero_serie' => 'SN-DEJA'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Déjà pointé');
    }

    public function test_retour_brouillon_depointe_et_conserve_les_lignes(): void
    {
        $sortie = $this->sortieEnPointage(2);
        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $sortie->lignes()->first()->id, 'equipement_id' => $this->uniteEnStock()->id,
        ])->assertOk();

        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.sorties.retour-brouillon', $sortie->id))
            ->assertOk();

        $this->assertStringContainsString('1 unité(s) dépointée(s)', $reponse->json('message'));
        $this->assertStringContainsString('Les articles et quantités du bon sont conservés', $reponse->json('message'));
        $this->assertSame(Sortie::STATUT_BROUILLON, $sortie->fresh()->statut);
        $this->assertSame(0, TamponEquipement::query()->count());
        $this->assertSame(1, $sortie->lignes()->count());
    }

    public function test_du_magasin_filtre_par_magasin_et_modele(): void
    {
        $dedans = $this->uniteEnStock();
        $this->uniteEnStock(Magasin::factory()->create()); // autre magasin

        $rows = collect($this->actingAs($this->user)
            ->getJson(route('stock.equipements.du-magasin', ['magasin_id' => $this->magasin->id, 'modele_id' => $this->modele->id]))
            ->assertOk()
            ->json('rows'));

        $this->assertTrue($rows->pluck('id')->contains($dedans->id));
        $this->assertCount(1, $rows);
    }
}
