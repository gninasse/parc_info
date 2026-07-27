<?php

namespace Modules\ParcInfo\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AnalyseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected $normalUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Ce test vérifie lui-même les habilitations (403/200) : pas de Gate::before ici.
        // Setup permissions
        Permission::findOrCreate('parcinfo.analyse.view');

        $adminRole = Role::findOrCreate('Admin');
        $adminRole->givePermissionTo(Permission::all());

        $this->adminUser = User::create([
            'name' => 'Admin',
            'last_name' => 'User',
            'user_name' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->adminUser->assignRole($adminRole);

        $this->normalUser = User::create([
            'name' => 'Normal',
            'last_name' => 'User',
            'user_name' => 'normaluser',
            'email' => 'normal@example.com',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_unauthorized_user_cannot_access_reports()
    {
        $response = $this->actingAs($this->normalUser)->get(route('parc-info.analyse.etats.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_access_reports_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('parc-info.analyse.etats.index'));
        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::analyse.etats.index');
    }

    public function test_authorized_user_can_get_reports_data()
    {
        $response = $this->actingAs($this->adminUser)->get(route('parc-info.analyse.etats.data', [
            'report_type' => 'global_park',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'total',
            'rows',
            'columns',
            'title',
        ]);
    }

    public function test_authorized_user_can_export_reports_csv()
    {
        $response = $this->actingAs($this->adminUser)->get(route('parc-info.analyse.etats.export', [
            'report_type' => 'global_park',
            'format' => 'csv',
        ]));

        $response->assertStatus(200);
        $response->assertHeaderContains('content-disposition', 'attachment;');
        $response->assertHeaderContains('content-disposition', '.csv');
    }

    public function test_unauthorized_user_cannot_access_statistics()
    {
        $response = $this->actingAs($this->normalUser)->get(route('parc-info.analyse.statistiques.index'));
        $response->assertStatus(403);
    }

    public function test_authorized_user_can_access_statistics_page()
    {
        $response = $this->actingAs($this->adminUser)->get(route('parc-info.analyse.statistiques.index'));
        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::analyse.statistiques.index');
    }

    public function test_authorized_user_can_get_statistics_data()
    {
        $response = $this->actingAs($this->adminUser)->get(route('parc-info.analyse.statistiques.data'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'summary' => [
                'total_equipements',
                'total_val_purchase',
                'total_residual_value',
                'vetuste_rate',
                'availability_rate',
                'avg_equip_per_employee',
                'estimated_budget',
            ],
            'types_stats',
            'status_stats',
            'state_stats',
        ]);
    }
}
