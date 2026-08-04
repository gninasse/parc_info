<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Batiment;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Etage;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;
use Tests\TestCase;

/**
 * Diligence 6 — les filtres des modales de sélection filtrent réellement,
 * et les selects se remplissent en cascade.
 */
class BeneficiairesFiltresTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Direction $directionA;

    private Direction $directionB;

    private Service $serviceA1;

    private Service $serviceB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->givePermissionTo(['stock.sorties.store']);

        $site = Site::query()->first();

        $this->directionA = Direction::create(['code' => 'DIR-A', 'libelle' => 'Direction Alpha', 'site_id' => $site->id]);
        $this->directionB = Direction::create(['code' => 'DIR-B', 'libelle' => 'Direction Beta', 'site_id' => $site->id]);

        $this->serviceA1 = Service::create([
            'code' => 'SRV-A1', 'libelle' => 'Informatique', 'direction_id' => $this->directionA->id,
            'site_id' => $site->id, 'type_service' => 'administratif',
        ]);
        $this->serviceB1 = Service::create([
            'code' => 'SRV-B1', 'libelle' => 'Pharmacie', 'direction_id' => $this->directionB->id,
            'site_id' => $site->id, 'type_service' => 'medico-technique',
        ]);
    }

    private function liste(array $params): array
    {
        return $this->actingAs($this->user)
            ->getJson(route('stock.beneficiaires.data', $params))
            ->assertOk()
            ->json('data');
    }

    public function test_filtre_des_services_par_direction(): void
    {
        $tous = $this->liste(['type' => 'service']);
        $this->assertCount(2, $tous);

        $filtres = $this->liste(['type' => 'service', 'direction_id' => $this->directionA->id]);
        $this->assertCount(1, $filtres);
        $this->assertSame('Informatique', $filtres[0]['libelle']);
        $this->assertSame('Direction Alpha', $filtres[0]['contexte']);
    }

    public function test_filtre_des_unites_par_service_et_par_direction(): void
    {
        $site = Site::query()->first();
        Unite::create(['code' => 'UNT-1', 'libelle' => 'Réseau', 'service_id' => $this->serviceA1->id, 'site_id' => $site->id]);
        Unite::create(['code' => 'UNT-2', 'libelle' => 'Officine', 'service_id' => $this->serviceB1->id, 'site_id' => $site->id]);

        $parService = $this->liste(['type' => 'unite', 'service_id' => $this->serviceA1->id]);
        $this->assertCount(1, $parService);
        $this->assertSame('Réseau', $parService[0]['libelle']);

        // Le filtre direction remonte la chaîne service → direction
        $parDirection = $this->liste(['type' => 'unite', 'direction_id' => $this->directionB->id]);
        $this->assertCount(1, $parDirection);
        $this->assertSame('Officine', $parDirection[0]['libelle']);
    }

    public function test_filtre_des_employes_par_direction_service_et_recherche(): void
    {
        Employe::factory()->create([
            'matricule' => 'MAT-001', 'nom' => 'Kabore', 'prenom' => 'Awa',
            'direction_id' => $this->directionA->id, 'service_id' => $this->serviceA1->id,
        ]);
        Employe::factory()->create([
            'matricule' => 'MAT-002', 'nom' => 'Traore', 'prenom' => 'Issa',
            'direction_id' => $this->directionB->id, 'service_id' => $this->serviceB1->id,
        ]);

        $this->assertCount(2, $this->liste(['type' => 'employe']));

        $parService = $this->liste(['type' => 'employe', 'service_id' => $this->serviceA1->id]);
        $this->assertCount(1, $parService);
        $this->assertSame('Awa Kabore', $parService[0]['libelle']);
        $this->assertSame('Informatique', $parService[0]['contexte']);

        // Recherche texte, combinable avec les filtres
        $this->assertCount(1, $this->liste(['type' => 'employe', 'q' => 'traore']));
        $this->assertCount(0, $this->liste(['type' => 'employe', 'q' => 'traore', 'direction_id' => $this->directionA->id]));
    }

    public function test_filtre_des_locaux_par_site_batiment_et_etage(): void
    {
        $site = Site::query()->first();
        $batiment = Batiment::create(['code' => 'BAT-1', 'libelle' => 'Bâtiment A', 'site_id' => $site->id]);
        $etage1 = Etage::create(['code' => 'ET-1', 'libelle' => 'RDC', 'batiment_id' => $batiment->id, 'numero' => 0]);
        $etage2 = Etage::create(['code' => 'ET-2', 'libelle' => '1er', 'batiment_id' => $batiment->id, 'numero' => 1]);

        Local::create(['code' => 'LOC-1', 'libelle' => 'Magasin central', 'etage_id' => $etage1->id, 'type_local' => 'magasin']);
        Local::create(['code' => 'LOC-2', 'libelle' => 'Bureau 12', 'etage_id' => $etage2->id, 'type_local' => 'bureau']);

        $this->assertCount(2, $this->liste(['type' => 'local', 'site_id' => $site->id]));
        $this->assertCount(2, $this->liste(['type' => 'local', 'batiment_id' => $batiment->id]));

        $parEtage = $this->liste(['type' => 'local', 'etage_id' => $etage1->id]);
        $this->assertCount(1, $parEtage);
        $this->assertSame('Magasin central', $parEtage[0]['libelle']);
        $this->assertSame('Bâtiment A', $parEtage[0]['contexte']);
    }

    public function test_filtre_par_statut(): void
    {
        $this->directionB->update(['actif' => false]);

        $this->assertCount(1, $this->liste(['type' => 'direction', 'statut' => 'actif']));
        $this->assertCount(1, $this->liste(['type' => 'direction', 'statut' => 'inactif']));
        $this->assertCount(2, $this->liste(['type' => 'direction']));
    }

    public function test_cascade_des_selects(): void
    {
        $site = Site::query()->first();
        $batiment = Batiment::create(['code' => 'BAT-C', 'libelle' => 'Bâtiment C', 'site_id' => $site->id]);
        Etage::create(['code' => 'ET-C1', 'libelle' => 'Niveau 1', 'batiment_id' => $batiment->id, 'numero' => 1]);

        $cascade = fn (array $params) => $this->actingAs($this->user)
            ->getJson(route('stock.beneficiaires.cascade', $params))
            ->assertOk()
            ->json('data');

        // Racines
        $this->assertGreaterThanOrEqual(2, count($cascade(['niveau' => 'directions'])));
        $this->assertGreaterThanOrEqual(1, count($cascade(['niveau' => 'sites'])));

        // Enfants bornés par leur parent
        $services = $cascade(['niveau' => 'services', 'parent_id' => $this->directionA->id]);
        $this->assertCount(1, $services);
        $this->assertSame('Informatique', $services[0]['libelle']);

        $batiments = $cascade(['niveau' => 'batiments', 'parent_id' => $site->id]);
        $this->assertTrue(collect($batiments)->pluck('libelle')->contains('Bâtiment C'));

        $etages = $cascade(['niveau' => 'etages', 'parent_id' => $batiment->id]);
        $this->assertCount(1, $etages);
        $this->assertSame('Niveau 1', $etages[0]['libelle']);
    }

    public function test_parametres_invalides_et_permissions(): void
    {
        // Type inconnu → 422
        $this->actingAs($this->user)
            ->getJson(route('stock.beneficiaires.data', ['type' => 'fournisseur']))
            ->assertStatus(422);

        $this->actingAs($this->user)
            ->getJson(route('stock.beneficiaires.cascade', ['niveau' => 'planetes']))
            ->assertStatus(422);

        // Sans permission d'un document qui utilise les sélecteurs → 403
        $etranger = User::create([
            'name' => 'E', 'last_name' => 'T', 'user_name' => 'etranger',
            'email' => 'etranger@example.com', 'password' => bcrypt('x'),
        ]);

        $this->actingAs($etranger)
            ->getJson(route('stock.beneficiaires.data', ['type' => 'service']))
            ->assertStatus(403);
    }
}
