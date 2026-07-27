<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Modules\Core\Services\PermissionService;
use Modules\ParcInfo\Database\Seeders\ParcInfoConfigSeeder;
use Tests\TestCase;

/**
 * Vérifie que les contrôles d'habilitation (middleware permission / trait
 * AuthorizesDynamicCategory) sont effectifs sur les routes du module ParcInfo.
 */
class HabilitationsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Déclare les permissions du module depuis config/permissions.php.
        app(PermissionService::class)->syncModulePermissions('parcinfo');

        $this->user = User::factory()->create();
    }

    public function test_consommables_index_refuse_sans_permission(): void
    {
        $this->actingAs($this->user)
            ->get(route('parc-info.consommables.index'))
            ->assertStatus(403);
    }

    public function test_consommables_index_accessible_avec_permission(): void
    {
        $this->user->givePermissionTo('parcinfo.consommables.index');

        $this->actingAs($this->user)
            ->get(route('parc-info.consommables.index'))
            ->assertStatus(200);
    }

    public function test_licences_index_refuse_sans_permission(): void
    {
        $this->actingAs($this->user)
            ->get(route('parc-info.licences.index'))
            ->assertStatus(403);
    }

    public function test_licences_index_accessible_avec_permission(): void
    {
        $this->user->givePermissionTo('parcinfo.licences.index');

        $this->actingAs($this->user)
            ->get(route('parc-info.licences.index'))
            ->assertStatus(200);
    }

    public function test_ordinateurs_index_refuse_sans_permission(): void
    {
        $this->seed(ParcInfoConfigSeeder::class);

        $this->actingAs($this->user)
            ->get(route('parc-info.ordinateurs.index'))
            ->assertStatus(403);
    }

    public function test_ordinateurs_index_accessible_avec_permission(): void
    {
        $this->seed(ParcInfoConfigSeeder::class);

        $this->user->givePermissionTo('parcinfo.ordinateurs.index');

        $this->actingAs($this->user)
            ->get(route('parc-info.ordinateurs.index'))
            ->assertStatus(200);
    }

    public function test_fournisseurs_store_refuse_sans_permission(): void
    {
        $this->actingAs($this->user)
            ->post(route('parc-info.fournisseurs.store'), [
                'nom' => 'Fournisseur Test',
            ])
            ->assertStatus(403);
    }
}
