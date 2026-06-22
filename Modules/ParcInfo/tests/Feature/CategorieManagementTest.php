<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\ChampConfig;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CategorieManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create standard roles & permissions
        $this->adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

        $permissions = [
            'parc-info.referentiels.categories.index',
            'parc-info.referentiels.categories.store',
            'parc-info.referentiels.categories.update',
            'parc-info.referentiels.categories.destroy',
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
    public function test_can_access_categories_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.categories.index'));
        $response->assertStatus(200)
            ->assertViewIs('parcinfo::referentiels.categories.index');
    }

    /**
     * Test get data.
     */
    public function test_can_get_categories_data(): void
    {
        CategorieEquipement::query()->delete();

        CategorieEquipement::create([
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
            'icone' => 'bi-projector',
        ]);

        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.categories.data'));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 1)
            ->assertJsonPath('rows.0.code', 'projecteur');
    }

    /**
     * Test store category.
     */
    public function test_can_store_category(): void
    {
        $data = [
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
            'icone' => 'bi-projector',
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.referentiels.categories.store'), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_categories_equipements', [
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
        ]);

        // Assert permissions were dynamically created
        $this->assertDatabaseHas('permissions', [
            'name' => 'parcinfo.projecteurs.index',
        ]);
        $this->assertDatabaseHas('permissions', [
            'name' => 'parcinfo.projecteurs.store',
        ]);
    }

    /**
     * Test update category.
     */
    public function test_can_update_category(): void
    {
        $category = CategorieEquipement::create([
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
            'icone' => 'bi-projector',
        ]);

        $data = [
            'libelle' => 'Vidéoprojecteurs UHD',
            'icone' => 'bi-projector-fill',
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.referentiels.categories.update', $category->id), $data);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_categories_equipements', [
            'id' => $category->id,
            'libelle' => 'Vidéoprojecteurs UHD',
            'icone' => 'bi-projector-fill',
        ]);
    }

    /**
     * Test destroy category.
     */
    public function test_can_destroy_category(): void
    {
        $category = CategorieEquipement::create([
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
            'icone' => 'bi-projector',
        ]);

        // Add dynamic permission to test deletion
        Permission::firstOrCreate(['name' => 'parcinfo.projecteurs.index', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->delete(route('parc-info.referentiels.categories.destroy', $category->id));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parc_info_categories_equipements', [
            'id' => $category->id,
        ]);

        // Dynamic permissions should be deleted
        $this->assertDatabaseMissing('permissions', [
            'name' => 'parcinfo.projecteurs.index',
        ]);
    }

    /**
     * Test dynamic fields CRUD.
     */
    public function test_can_manage_dynamic_fields(): void
    {
        $category = CategorieEquipement::create([
            'code' => 'projecteur',
            'libelle' => 'Vidéoprojecteurs',
            'icone' => 'bi-projector',
        ]);

        // 1. Create field
        $fieldData = [
            'code' => 'resolution',
            'libelle' => 'Résolution',
            'type_champ' => 'text',
            'nom_panel' => 'Spécifications',
            'ordre_affichage' => 10,
            'afficher_dans_modal' => 1,
            'afficher_dans_show' => 1,
            'afficher_dans_liste' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('parc-info.referentiels.categories.fields.store', $category->id), $fieldData);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_champs_config', [
            'categorie_id' => $category->id,
            'code' => 'resolution',
            'libelle' => 'Résolution',
        ]);

        $field = ChampConfig::where('code', 'resolution')->first();

        // 2. Fetch data list
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.categories.fields.data', $category->id));
        $response->assertStatus(200)
            ->assertJsonStructure(['total', 'rows'])
            ->assertJsonPath('total', 1);

        // 3. Show field
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.categories.fields.show', [$category->id, $field->id]));
        $response->assertStatus(200)
            ->assertJsonPath('data.code', 'resolution');

        // 4. Update field
        $updateData = [
            'libelle' => 'Résolution native',
            'type_champ' => 'select',
            'source_options' => '["1080p", "4K"]',
            'nom_panel' => 'Spécifications',
            'ordre_affichage' => 5,
            'afficher_dans_modal' => 1,
            'afficher_dans_show' => 1,
            'afficher_dans_liste' => 0,
        ];

        $response = $this->actingAs($this->user)->put(route('parc-info.referentiels.categories.fields.update', [$category->id, $field->id]), $updateData);
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('parc_info_champs_config', [
            'id' => $field->id,
            'libelle' => 'Résolution native',
            'source_options' => '["1080p", "4K"]',
            'afficher_dans_liste' => false,
        ]);

        // 5. Delete field
        $response = $this->actingAs($this->user)->delete(route('parc-info.referentiels.categories.fields.destroy', [$category->id, $field->id]));
        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('parc_info_champs_config', [
            'id' => $field->id,
        ]);
    }
}
