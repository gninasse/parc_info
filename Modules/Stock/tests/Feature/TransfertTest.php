<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneTransfert;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Transfert;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

/**
 * Transferts (D17) : I4 (paire atomique), source ≠ cible, unités bornées à
 * la source, filtre ?cible=mon-magasin, matrice de permissions, DoD mixte.
 */
class TransfertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Magasin $source;

    private Magasin $cible;

    private Article $article;

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

        $this->source = Magasin::factory()->create();
        $this->cible = Magasin::factory()->create();
        $this->article = Article::factory()->consommable()->create(['seuil_defaut' => null]);
    }

    private function approvisionnerSource(float $quantite): void
    {
        app(MouvementService::class)->entree([
            'entree_id' => Entree::factory()->validee()->create(['magasin_id' => $this->source->id])->id,
            'magasin_id' => $this->source->id,
            'article_id' => $this->article->id,
            'quantite' => $quantite,
        ]);
    }

    private function niveau(Magasin $magasin): float
    {
        return (float) (Niveau::query()
            ->where('magasin_id', $magasin->id)
            ->where('article_id', $this->article->id)
            ->value('quantite') ?? 0);
    }

    private function valider(Transfert $transfert, string $jeton = 'jeton-trf')
    {
        return $this->actingAs($this->user)
            ->postJson(route('stock.transferts.valider', $transfert->id), ['jeton' => $jeton]);
    }

    public static function routesProtegees(): array
    {
        return [
            'index' => ['GET', 'stock.transferts.index', [], 'stock.transferts.index'],
            'data' => ['GET', 'stock.transferts.data', [], 'stock.transferts.index'],
            'show' => ['GET', 'stock.transferts.show', ['id' => true], 'stock.transferts.index'],
            'create' => ['GET', 'stock.transferts.create', [], 'stock.transferts.store'],
            'store' => ['POST', 'stock.transferts.store', [], 'stock.transferts.store'],
            'edit' => ['GET', 'stock.transferts.edit', ['id' => true], 'stock.transferts.update'],
            'update' => ['PUT', 'stock.transferts.update', ['id' => true], 'stock.transferts.update'],
            'destroy' => ['DELETE', 'stock.transferts.destroy', ['id' => true], 'stock.transferts.destroy'],
            'pointage POST' => ['POST', 'stock.transferts.pointage', ['id' => true], 'stock.transferts.store'],
            'pointage GET' => ['GET', 'stock.transferts.pointage.show', ['id' => true], 'stock.transferts.store'],
            'pointage PUT' => ['PUT', 'stock.transferts.pointage.update', ['id' => true], 'stock.transferts.store'],
            'scan express' => ['POST', 'stock.transferts.scan-express', ['id' => true], 'stock.transferts.store'],
            'retour brouillon' => ['POST', 'stock.transferts.retour-brouillon', ['id' => true], 'stock.transferts.store'],
            'valider' => ['POST', 'stock.transferts.valider', ['id' => true], 'stock.transferts.store'],
            'pdf' => ['GET', 'stock.transferts.pdf', ['id' => true], 'stock.transferts.store'],
        ];
    }

    /** @dataProvider routesProtegees */
    public function test_matrice_403_sans_permission_et_redirection_invite(string $methode, string $route, array $params, string $permission): void
    {
        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);
        $params = array_map(fn ($valeur) => $valeur === true ? $transfert->id : $valeur, $params);
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

    public function test_source_egale_cible_422(): void
    {
        $this->actingAs($this->user)
            ->postJson(route('stock.transferts.store'), [
                'magasin_source_id' => $this->source->id,
                'magasin_cible_id' => $this->source->id,
                'date_document' => now()->format('Y-m-d'),
                'lignes' => [],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['magasin_cible_id']);
    }

    /**
     * DoD — transfert mixte (quantitatif + unité scannée) : les DEUX magasins
     * bougent dans la même transaction, l'unité change de rattachement.
     */
    public function test_transfert_mixte_fait_bouger_les_deux_magasins(): void
    {
        $this->approvisionnerSource(10);

        $modele = Article::factory()->equipement()->create(['modele' => 'Switch 24p']);
        $categorie = CategorieEquipement::query()->find($modele->categorie_equipement_id);
        $unite = ParcInfoDeTest::equipement([
            'categorie_id' => $categorie->id, 'modele' => 'Switch 24p', 'numero_serie' => 'SN-TRF-1',
        ]);
        EquipementMagasin::create(['equipement_id' => $unite->id, 'magasin_id' => $this->source->id, 'date_rattachement' => now()]);

        // Brouillon : 1 ligne quantitative + 1 unité par scan express
        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
            'transporte_par_nom' => 'C. Transporteur',
        ]);
        LigneTransfert::factory()->create(['transfert_id' => $transfert->id, 'article_id' => $this->article->id, 'quantite' => 4]);

        $this->actingAs($this->user)
            ->postJson(route('stock.transferts.scan-express', $transfert->id), ['numero_serie' => 'SN-TRF-1'])
            ->assertOk();

        $this->valider($transfert)->assertOk();

        $transfert->refresh();
        $this->assertSame(Transfert::STATUT_VALIDE, $transfert->statut);
        $this->assertStringStartsWith('TRF-'.now()->year.'-', $transfert->numero);

        // Les DEUX magasins ont bougé (paire atomique)
        $this->assertSame(6.0, $this->niveau($this->source));
        $this->assertSame(4.0, $this->niveau($this->cible));

        // Paire TRANSFERT_SORTIE/TRANSFERT_ENTREE liée au même document, pour l'article ET l'unité
        $this->assertSame(2, Mouvement::query()->where('transfert_id', $transfert->id)->where('type', Mouvement::TYPE_TRANSFERT_SORTIE)->count());
        $this->assertSame(2, Mouvement::query()->where('transfert_id', $transfert->id)->where('type', Mouvement::TYPE_TRANSFERT_ENTREE)->count());

        // L'unité est rattachée à la CIBLE, toujours « en stock »
        $rattachement = EquipementMagasin::query()->where('equipement_id', $unite->id)->first();
        $this->assertSame($this->cible->id, (int) $rattachement->magasin_id);
        $this->assertSame('en_stock', $unite->fresh()->statut);

        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    /**
     * I4 — échec forcé de l'entrée cible (magasin cible désactivé entre le
     * brouillon et la validation) → rollback COMPLET : ni la sortie source,
     * ni le document.
     */
    public function test_invariant_I4_echec_de_l_entree_cible_annule_la_sortie_source(): void
    {
        $this->approvisionnerSource(10);

        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);
        LigneTransfert::factory()->create(['transfert_id' => $transfert->id, 'article_id' => $this->article->id, 'quantite' => 4]);

        // La garde « magasin actif » du MouvementService frappera le côté
        // ENTREE de la paire, APRÈS que le côté SORTIE a décrémenté la source
        $this->cible->update(['est_actif' => false]);

        $this->valider($transfert)->assertStatus(422);

        // Rollback complet : source intacte, cible vierge, aucun mouvement, statut inchangé
        $this->assertSame(10.0, $this->niveau($this->source));
        $this->assertSame(0.0, $this->niveau($this->cible));
        $this->assertSame(0, Mouvement::query()->where('transfert_id', $transfert->id)->count());
        $this->assertSame(Transfert::STATUT_BROUILLON, $transfert->fresh()->statut);
        $this->assertNull($transfert->fresh()->numero);
    }

    public function test_disponible_source_insuffisant_422_ligne_par_ligne(): void
    {
        $this->approvisionnerSource(3);

        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);
        LigneTransfert::factory()->create(['transfert_id' => $transfert->id, 'article_id' => $this->article->id, 'quantite' => 5]);

        $reponse = $this->valider($transfert)->assertStatus(422);
        $this->assertStringContainsString('ligne 1', $reponse->json('message'));
        $this->assertSame(3.0, $this->niveau($this->source));
    }

    public function test_scan_express_borne_aux_unites_de_la_source(): void
    {
        $modele = Article::factory()->equipement()->create(['modele' => 'Routeur X']);
        $categorie = CategorieEquipement::query()->find($modele->categorie_equipement_id);

        // Unité rattachée à la CIBLE (pas à la source) → « Introuvable dans ce magasin »
        $uniteAilleurs = ParcInfoDeTest::equipement([
            'categorie_id' => $categorie->id, 'modele' => 'Routeur X', 'numero_serie' => 'SN-AILLEURS',
        ]);
        EquipementMagasin::create(['equipement_id' => $uniteAilleurs->id, 'magasin_id' => $this->cible->id, 'date_rattachement' => now()]);

        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('stock.transferts.scan-express', $transfert->id), ['numero_serie' => 'SN-AILLEURS'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Introuvable dans ce magasin');
    }

    public function test_filtre_cible_mon_magasin(): void
    {
        // L'utilisateur est responsable du magasin cible (users.dossier_employe_id)
        $employe = Employe::factory()->create();
        $this->user->update(['dossier_employe_id' => $employe->id]);
        $this->cible->update(['responsable_id' => $employe->id]);

        $versMonMagasin = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);
        Transfert::factory()->create([
            'magasin_source_id' => $this->cible->id,
            'magasin_cible_id' => $this->source->id,
        ]);

        $rows = collect($this->actingAs($this->user)
            ->getJson(route('stock.transferts.data', ['cible' => 'mon-magasin']))
            ->assertOk()
            ->json('rows'));

        $this->assertCount(1, $rows);
        $this->assertSame($versMonMagasin->id, $rows->first()['id']);

        // Le toggle est proposé sur la page (l'utilisateur est responsable)
        $this->actingAs($this->user)
            ->get(route('stock.transferts.index'))
            ->assertOk()
            ->assertSee('Vers mon magasin');
    }

    public function test_invariant_I9_idempotence(): void
    {
        $this->approvisionnerSource(10);
        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
        ]);
        LigneTransfert::factory()->create(['transfert_id' => $transfert->id, 'article_id' => $this->article->id, 'quantite' => 2]);

        $premier = $this->valider($transfert, 'jeton-t')->assertOk();
        $second = $this->valider($transfert, 'jeton-t')->assertOk();

        $this->assertSame($premier->json('recap.numero'), $second->json('recap.numero'));
        $this->assertSame(8.0, $this->niveau($this->source));
        $this->assertSame(2.0, $this->niveau($this->cible));
        $this->assertSame(2, Mouvement::query()->where('transfert_id', $transfert->id)->count());
    }

    public function test_le_gabarit_pdf_porte_la_double_signature(): void
    {
        $this->approvisionnerSource(5);
        $transfert = Transfert::factory()->create([
            'magasin_source_id' => $this->source->id,
            'magasin_cible_id' => $this->cible->id,
            'transporte_par_nom' => 'C. Transporteur',
        ]);
        LigneTransfert::factory()->create(['transfert_id' => $transfert->id, 'article_id' => $this->article->id, 'quantite' => 2]);
        $this->valider($transfert)->assertOk();

        $html = view('stock::pdf.transfert', [
            'transfert' => $transfert->fresh(['magasinSource', 'magasinCible', 'lignes.article', 'transporteParEmploye', 'createur', 'valideur']),
            'unites' => collect(),
        ])->render();

        $this->assertStringContainsString('Départ — magasinier source / transporteur', $html);
        $this->assertStringContainsString('Arrivée — magasinier cible', $html);
        $this->assertStringContainsString('C. Transporteur', $html);
        $this->assertStringContainsString($transfert->fresh()->numero, $html);
        $this->assertStringContainsString('Document non modifiable — corrections par contre-mouvement', $html);

        $this->actingAs($this->user)
            ->get(route('stock.transferts.pdf', $transfert->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }
}
