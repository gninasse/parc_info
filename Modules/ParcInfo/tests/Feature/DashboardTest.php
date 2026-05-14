<?php

namespace Modules\ParcInfo\Tests\Feature;

use Tests\TestCase;
use Modules\Core\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup permissions
        Permission::findOrCreate('dashboard.view');
        Permission::findOrCreate('parcinfo.dashboard.view');
        
        $this->adminRole = Role::findOrCreate('Admin');
        $this->adminRole->givePermissionTo(Permission::all());
        
        $this->userRole = Role::findOrCreate('User');
    }

    public function test_unauthenticated_user_cannot_access_dashboard()
    {
        $response = $this->get(route('parc-info.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_dashboard()
    {
        $user = User::create([
            'name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->assignRole($this->userRole);

        $response = $this->actingAs($user)->get(route('parc-info.dashboard'));

        $response->assertStatus(403);
    }

    public function test_user_with_permission_can_access_dashboard()
    {
        $user = User::create([
            'name' => 'Permitted',
            'last_name' => 'User',
            'user_name' => 'permitteduser',
            'email' => 'permitted@example.com',
            'password' => bcrypt('password'),
        ]);
        $user->givePermissionTo('parcinfo.dashboard.view');

        $response = $this->actingAs($user)->get(route('parc-info.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('parcinfo::dashboard.index');
    }
}
