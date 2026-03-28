<?php

namespace Tests\Feature\Modules\Grh\tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;
use Tests\TestCase;

class EmployeTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $direction;

    protected $service;

    protected $unite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $site = Site::create([
            'code' => 'SITE1',
            'libelle' => 'Site Principal',
            'actif' => true,
        ]);

        $this->direction = Direction::create([
            'site_id' => $site->id,
            'code' => 'DIR1',
            'libelle' => 'Direction 1',
            'actif' => true,
        ]);

        $this->service = Service::create([
            'direction_id' => $this->direction->id,
            'site_id' => $site->id,
            'code' => 'SRV1',
            'libelle' => 'Service 1',
            'type_service' => 'administratif',
            'actif' => true,
        ]);

        $this->unite = Unite::create([
            'service_id' => $this->service->id,
            'site_id' => $site->id,
            'code' => 'UNT1',
            'libelle' => 'Unité 1',
            'actif' => true,
        ]);
    }

    public function test_can_create_employe_with_contacts()
    {
        $data = [
            'matricule' => 'EMP001',
            'nom' => 'Kaboré',
            'prenom' => 'Jean',
            'genre' => 'M',
            'niveau_rattachement' => 'service',
            'direction_id' => $this->direction->id,
            'service_id' => $this->service->id,
            'contacts' => [
                ['type_contact' => 'telephone', 'valeur' => '12345678'],
                ['type_contact' => 'email', 'valeur' => 'jean@example.com'],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->post(route('grh.employes.store'), $data);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('grh_dossiers_employes', [
            'matricule' => 'EMP001',
            'service_id' => $this->service->id,
        ]);

        $this->assertDatabaseCount('grh_contacts_employes', 2);
    }

    public function test_can_update_employe_and_sync_contacts()
    {
        $employe = Employe::create([
            'matricule' => 'EMP002',
            'nom' => 'Test',
            'prenom' => 'Update',
            'niveau_rattachement' => 'direction',
            'direction_id' => $this->direction->id,
        ]);

        $employe->contacts()->create(['type_contact' => 'telephone', 'valeur' => 'old_val']);

        $data = [
            'matricule' => 'EMP002',
            'nom' => 'Test Updated',
            'prenom' => 'Update',
            'niveau_rattachement' => 'direction',
            'direction_id' => $this->direction->id,
            'contacts' => [
                ['type_contact' => 'telephone', 'valeur' => 'new_val'],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->put(route('grh.employes.update', $employe->id), $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('grh_dossiers_employes', ['nom' => 'Test Updated']);
        $this->assertDatabaseHas('grh_contacts_employes', ['valeur' => 'new_val']);
        $this->assertDatabaseMissing('grh_contacts_employes', ['valeur' => 'old_val']);
    }

    public function test_get_services_by_direction()
    {
        $response = $this->actingAs($this->user)
            ->get(route('grh.employes.services-by-direction', $this->direction->id));

        $response->assertStatus(200)
            ->assertJsonFragment(['libelle' => 'Service 1']);
    }
}
