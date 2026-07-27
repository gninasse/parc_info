<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Dictionnaire;
use Modules\ParcInfo\Models\DictionnaireValeur;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DictionnaireDynamiqueTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Les habilitations sont couvertes par HabilitationsTest.
        \Illuminate\Support\Facades\Gate::before(fn () => true);

        // Create standard roles & permissions
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $permissions = [
            'parc-info.referentiels.dictionnaires.index',
            'parc-info.referentiels.dictionnaires.store',
            'parc-info.referentiels.dictionnaires.update',
            'parc-info.referentiels.dictionnaires.destroy',
            'parc-info.referentiels.dictionnaires.manage',
        ];

        foreach ($permissions as $perm) {
            $permission = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
            $this->adminRole->givePermissionTo($permission);
        }

        $this->user = User::factory()->create();
        $this->user->assignRole($this->adminRole);
    }

    /**
     * Test index access.
     */
    public function test_can_access_dictionnaires_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.dictionnaires.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::referentiels.dictionnaires.index');
    }

    /**
     * Test get data.
     */
    public function test_can_get_dictionnaires_data(): void
    {
        Dictionnaire::query()->delete();

        Dictionnaire::create([
            'code' => 'type_cable',
            'libelle' => 'Types de Câbles',
            'description' => 'Différents types de câbles informatiques',
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.dictionnaires.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.code', 'type_cable');
    }

    /**
     * Test store dictionary.
     */
    public function test_can_store_dictionnaire(): void
    {
        $data = [
            'code' => 'type_cable',
            'libelle' => 'Types de Câbles',
            'description' => 'Différents types de câbles informatiques',
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.referentiels.dictionnaires.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_dictionnaires', [
            'code' => 'type_cable',
            'libelle' => 'Types de Câbles',
        ]);
    }

    /**
     * Test update dictionary.
     */
    public function test_can_update_dictionnaire(): void
    {
        $dict = Dictionnaire::create([
            'code' => 'type_cable',
            'libelle' => 'Types de Câbles',
        ]);

        $data = [
            'libelle' => 'Types de Câbles Réseau',
            'description' => 'Nouvelle description',
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.referentiels.dictionnaires.update', $dict->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_dictionnaires', [
            'id' => $dict->id,
            'libelle' => 'Types de Câbles Réseau',
            'description' => 'Nouvelle description',
        ]);
    }

    /**
     * Test system dictionary cannot be deleted.
     */
    public function test_cannot_delete_system_dictionnaire(): void
    {
        $dict = Dictionnaire::create([
            'code' => 'type_cpu',
            'libelle' => 'Types CPU',
        ]);

        $response = $this->actingAs($this->user)->delete(route('parc-info.referentiels.dictionnaires.destroy', $dict->id));
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test dictionary values CRUD.
     */
    public function test_can_manage_dictionnaire_valeurs(): void
    {
        $dict = Dictionnaire::create([
            'code' => 'type_cable',
            'libelle' => 'Types de Câbles',
        ]);

        // 1. View Values Page
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.dictionnaires.valeurs.index', $dict->code));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::referentiels.dictionnaires.values');

        // 2. Store Value
        $data = [
            'valeur' => 'RJ45 Cat6',
            'description' => 'Câble ethernet standard',
        ];
        $response = $this->actingAs($this->user)->post(route('parc-info.referentiels.dictionnaires.valeurs.store', $dict->code), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_dictionnaire_valeurs', [
            'dictionnaire_id' => $dict->id,
            'valeur' => 'RJ45 Cat6',
        ]);

        $valeur = DictionnaireValeur::where('dictionnaire_id', $dict->id)->first();

        // 3. Show Value
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.dictionnaires.valeurs.show', ['code' => $dict->code, 'id' => $valeur->id]));
        $response->assertStatus(200)
            ->assertJsonPath('data.valeur', 'RJ45 Cat6');

        // 4. Update Value
        $dataUpdate = [
            'valeur' => 'RJ45 Cat6a',
            'description' => 'Câble ethernet amélioré',
        ];
        $response = $this->actingAs($this->user)->put(route('parc-info.referentiels.dictionnaires.valeurs.update', ['code' => $dict->code, 'id' => $valeur->id]), $dataUpdate);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_dictionnaire_valeurs', [
            'id' => $valeur->id,
            'valeur' => 'RJ45 Cat6a',
        ]);

        // 5. Delete Value
        $response = $this->actingAs($this->user)->delete(route('parc-info.referentiels.dictionnaires.valeurs.destroy', ['code' => $dict->code, 'id' => $valeur->id]));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parc_info_dictionnaire_valeurs', [
            'id' => $valeur->id,
        ]);
    }
}
