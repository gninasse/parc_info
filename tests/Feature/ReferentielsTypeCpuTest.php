<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\TypeCpu;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ReferentielsTypeCpuTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a testing user manually
        $this->user = User::factory()->create();
    }

    /**
     * Test index page access and permissions.
     */
    public function test_cannot_access_index_without_auth(): void
    {
        $response = $this->get(route('parc-info.referentiels.types-cpus.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cannot_access_index_without_permission(): void
    {
        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.types-cpus.index'));
        $response->assertStatus(403);
    }

    public function test_can_access_index_with_permission(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.index', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.index');

        $response = $this->actingAs($this->user)->get(route('parc-info.referentiels.types-cpus.index'));
        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::referentiels.types-cpus.index');
    }

    /**
     * Test getData endpoint.
     */
    public function test_can_get_data_via_ajax(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.index', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.index');

        TypeCpu::create(['libelle' => 'Intel Core Test CPU']);

        $response = $this->actingAs($this->user)->getJson(route('parc-info.referentiels.types-cpus.data', [
            'search' => 'Intel Core Test',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'rows' => [
                '*' => ['id', 'libelle', 'created_at', 'updated_at'],
            ],
        ]);

        $this->assertGreaterThanOrEqual(1, $response->json('total'));
    }

    /**
     * Test store CPU.
     */
    public function test_can_store_cpu_with_permission(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.store', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.store');

        $response = $this->actingAs($this->user)->postJson(route('parc-info.referentiels.types-cpus.store'), [
            'libelle' => 'AMD Ryzen Test CPU',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Type de CPU créé avec succès',
        ]);

        $this->assertDatabaseHas('parc_info_types_cpus', [
            'libelle' => 'AMD Ryzen Test CPU',
        ]);
    }

    /**
     * Test store validation.
     */
    public function test_store_validation_fails_on_duplicate(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.store', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.store');

        TypeCpu::create(['libelle' => 'AMD Ryzen Duplicate']);

        $response = $this->actingAs($this->user)->postJson(route('parc-info.referentiels.types-cpus.store'), [
            'libelle' => 'AMD Ryzen Duplicate',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['libelle']);
    }

    /**
     * Test show CPU.
     */
    public function test_can_show_cpu(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.index', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.index');

        $cpu = TypeCpu::create(['libelle' => 'Intel Show Test']);

        $response = $this->actingAs($this->user)->getJson(route('parc-info.referentiels.types-cpus.show', $cpu->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'id' => $cpu->id,
                'libelle' => 'Intel Show Test',
            ],
        ]);
    }

    /**
     * Test update CPU.
     */
    public function test_can_update_cpu(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.update', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.update');

        $cpu = TypeCpu::create(['libelle' => 'Intel Old Name']);

        $response = $this->actingAs($this->user)->putJson(route('parc-info.referentiels.types-cpus.update', $cpu->id), [
            'libelle' => 'Intel New Name',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Type de CPU modifié avec succès',
        ]);

        $this->assertDatabaseHas('parc_info_types_cpus', [
            'id' => $cpu->id,
            'libelle' => 'Intel New Name',
        ]);
    }

    /**
     * Test delete CPU.
     */
    public function test_can_delete_cpu(): void
    {
        Permission::findOrCreate('parc-info.referentiels.types-cpus.destroy', 'web');
        $this->user->givePermissionTo('parc-info.referentiels.types-cpus.destroy');

        $cpu = TypeCpu::create(['libelle' => 'Intel Delete Test']);

        $response = $this->actingAs($this->user)->deleteJson(route('parc-info.referentiels.types-cpus.destroy', $cpu->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Type de CPU supprimé avec succès',
        ]);

        $this->assertDatabaseMissing('parc_info_types_cpus', [
            'id' => $cpu->id,
        ]);
    }
}
