<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\Equipement;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CentreImpressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Les habilitations sont couvertes par HabilitationsTest.
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Seed permissions required for testing
        Permission::findOrCreate('parcinfo.dashboard.view');

        $this->adminRole = Role::findOrCreate('Admin');
        $this->adminRole->givePermissionTo(Permission::all());

        $this->user = User::create([
            'name' => 'Admin',
            'last_name' => 'User',
            'user_name' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->user->assignRole($this->adminRole);
    }

    public function test_centre_impression_requires_authentication()
    {
        $response = $this->get(route('parc-info.equipements.centre-impression'));
        $response->assertRedirect(route('login'));
    }

    public function test_authorised_user_can_access_centre_impression()
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.equipements.centre-impression'));

        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::informatique.equipements.centre_impression');
        $response->assertViewHasAll(['categories', 'sites', 'directions']);
    }

    public function test_centre_impression_data_endpoint_returns_json()
    {
        $category = CategorieEquipement::create([
            'code' => 'ordinateur',
            'libelle' => 'Ordinateurs',
            'icone' => 'bi-pc-display',
        ]);

        Equipement::create([
            'categorie_id' => $category->id,
            'code_inventaire' => 'INV-2026-0001',
            'numero_serie' => 'SN12345678',
            'modele' => 'Latitude 5420',
            'statut' => 'en_service',
            'etat' => 'bon',
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.equipements.etiquettes-data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'rows' => [
                '*' => [
                    'id',
                    'code_inventaire',
                    'categorie_libelle',
                    'marque_modele',
                    'numero_serie',
                    'statut',
                    'statut_label',
                    'affectation',
                    'etat',
                ],
            ],
        ]);
    }

    public function test_imprimer_selection_requires_ids()
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.equipements.imprimer-etiquettes'));

        $response->assertStatus(400);
    }

    public function test_imprimer_selection_renders_view_with_equipements()
    {
        $category = CategorieEquipement::create([
            'code' => 'ordinateur',
            'libelle' => 'Ordinateurs',
            'icone' => 'bi-pc-display',
        ]);

        $equipement = Equipement::create([
            'categorie_id' => $category->id,
            'code_inventaire' => 'INV-2026-0001',
            'numero_serie' => 'SN12345678',
            'modele' => 'Latitude 5420',
            'statut' => 'en_service',
            'etat' => 'bon',
        ]);

        $response = $this->actingAs($this->user)->get(
            route('parc-info.equipements.imprimer-etiquettes', ['ids' => $equipement->id])
        );

        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::informatique.equipements.etiquettes_multiples');
        $response->assertViewHas('equipements');
    }
}
