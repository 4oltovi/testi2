<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Enums\UserRole;

class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_requires_authentication(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_admin_reports_index_loads(): void
    {
        $role = \App\Models\Role::where('name', 'admin')->firstOrCreate([
            'name' => 'admin',
            'display_name' => 'Администратор',
            'level' => 90,
            'is_system' => true,
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get('/admin/reports');

        $response->assertStatus(200);
        $response->assertSee('Ҳисоботҳо');
    }

    public function test_export_route_works_for_authenticated_user(): void
    {
        $role = \App\Models\Role::where('name', 'admin')->firstOrCreate([
            'name' => 'admin',
            'display_name' => 'Администратор',
            'level' => 90,
            'is_system' => true,
        ]);

        $user = User::factory()->create([
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get('/admin/reports/export/students');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
