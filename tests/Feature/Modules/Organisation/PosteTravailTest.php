<?php

namespace Tests\Feature\Modules\Organisation;

use Modules\Core\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\PosteTravail;
use Tests\TestCase;

class PosteTravailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin',
            'last_name' => 'User',
            'user_name' => 'admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        $this->actingAs($this->user);

        $this->site = Site::create(['code' => 'SITE', 'libelle' => 'Site Test']);
    }

    public function test_can_list_postes_travail()
    {
        $response = $this->get(route('organisation.postes.index'));
        $response->assertStatus(200);
    }

    public function test_can_get_postes_data()
    {
        $direction = Direction::create(['code' => 'DIR', 'libelle' => 'Direction Test', 'site_id' => $this->site->id]);
        PosteTravail::create([
            'libelle' => 'Poste Test',
            'code' => 'P-001',
            'niveau_rattachement' => 'direction',
            'direction_id' => $direction->id,
            'statut' => 'actif'
        ]);

        $response = $this->get(route('organisation.postes.data'));
        $response->assertStatus(200);
        $response->assertJsonStructure(['total', 'rows']);
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_can_store_poste_travail()
    {
        $direction = Direction::create(['code' => 'DIR', 'libelle' => 'Direction Test', 'site_id' => $this->site->id]);
        $service = Service::create([
            'code' => 'SRV',
            'libelle' => 'Service Test',
            'direction_id' => $direction->id,
            'type_service' => 'clinique'
        ]);

        $data = [
            'libelle' => 'Nouveau Poste',
            'niveau_rattachement' => 'service',
            'direction_id' => $direction->id,
            'service_id' => $service->id,
            'statut' => 'actif'
        ];

        $response = $this->post(route('organisation.postes.store'), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organisation_postes_travail', [
            'libelle' => 'Nouveau Poste',
            'service_id' => $service->id
        ]);
    }

    public function test_can_update_poste_travail()
    {
        $direction = Direction::create(['code' => 'DIR', 'libelle' => 'Direction Test', 'site_id' => $this->site->id]);
        $poste = PosteTravail::create([
            'libelle' => 'Vieux Libelle',
            'code' => 'P-001',
            'niveau_rattachement' => 'direction',
            'direction_id' => $direction->id,
            'statut' => 'actif'
        ]);

        $data = [
            'libelle' => 'Nouveau Libelle',
            'niveau_rattachement' => 'direction',
            'direction_id' => $direction->id,
            'statut' => 'inactif'
        ];

        $response = $this->put(route('organisation.postes.update', $poste->id), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organisation_postes_travail', [
            'id' => $poste->id,
            'libelle' => 'Nouveau Libelle',
            'statut' => 'inactif'
        ]);
    }

    public function test_can_search_employes()
    {
        Employe::create([
            'nom' => 'DOE',
            'prenom' => 'John',
            'matricule' => 'M001',
            'niveau_rattachement' => 'direction'
        ]);

        $response = $this->get(route('organisation.postes.search-employes', ['q' => 'John']));
        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('DOE John (M001)', $response->json()[0]['text']);
    }
}
