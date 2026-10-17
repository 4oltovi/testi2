<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;

class AdminRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): array
    {
        return [
            'super_admin' => Role::firstOrCreate(
                ['name' => 'super_admin'],
                ['display_name' => 'Суперадмин', 'level' => 100, 'is_system' => true]
            ),
            'admin' => Role::firstOrCreate(
                ['name' => 'admin'],
                ['display_name' => 'Администратор', 'level' => 90, 'is_system' => true]
            ),
            'teacher' => Role::firstOrCreate(
                ['name' => 'teacher'],
                ['display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true]
            ),
            'student' => Role::firstOrCreate(
                ['name' => 'student'],
                ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
            ),
        ];
    }

    private function userWithRole(Role $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs + [
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);
        return $user->fresh();
    }

    // ==================== ГУЗВОН (guest) — рӯяхати GET ====================

    public function test_guest_get_admin_routes_redirect_to_login(): void
    {
        $this->seedRoles();

        $urls = [
            '/admin/dashboard',
            '/admin/students',
            '/admin/debts',
            '/admin/users',
            '/admin/journal/category-scores/1',
            '/admin/settings',
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    // ==================== ГУЗВОН (guest) — рӯяхати POST ====================

    public function test_guest_post_admin_routes_redirect_to_login(): void
    {
        $this->seedRoles();

        $urls = [
            '/admin/students',
            '/admin/users',
            '/admin/journal/category-scores/1',
        ];

        foreach ($urls as $url) {
            $this->post($url, [])->assertRedirect('/login');
        }
    }

    // ==================== ДОНИШҶӮ (student) — 403 на ротҳои админ ====================

    public function test_student_gets_403_on_admin_routes(): void
    {
        $roles = $this->seedRoles();
        $student = $this->userWithRole($roles['student']);

        $urls = [
            '/admin/dashboard',
            '/admin/students',
            '/admin/debts',
            '/admin/users',
            '/admin/settings',
        ];

        foreach ($urls as $url) {
            $this->actingAs($student)->get($url)->assertForbidden();
        }
    }

    // ==================== АДМИН — достӣро инҳо медӯрад ====================

    public function test_admin_can_reach_admin_pages(): void
    {
        $roles = $this->seedRoles();
        $admin = $this->userWithRole($roles['admin']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/admin/students')->assertOk();
        $this->actingAs($admin)->get('/admin/debts')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
    }

    // ==================== СУПЕРАДМИН — ҳам достӣро инҳо медӯрад ====================

    public function test_super_admin_can_reach_admin_pages(): void
    {
        $roles = $this->seedRoles();
        $super = $this->userWithRole($roles['super_admin']);

        $this->actingAs($super)->get('/admin/dashboard')->assertOk();
        $this->actingAs($super)->get('/admin/users')->assertOk();
    }

    // ==================== ОМӮЗГОР — достӣро инҳо медӯрад (саъфиҳотҳои оман) ====================

    public function test_teacher_can_reach_teacher_pages(): void
    {
        $roles = $this->seedRoles();
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($teacher)->get('/teacher/dashboard')->assertOk();
        $this->actingAs($teacher)->get('/teacher/journal')->assertOk();
    }

    // ==================== ОМӮЗГОР — 403 на ротҳои админ ====================

    public function test_teacher_gets_403_on_admin_routes(): void
    {
        $roles = $this->seedRoles();
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($teacher)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/students')->assertForbidden();
        $this->actingAs($teacher)->get('/admin/users')->assertForbidden();
    }

    // ==================== АВАТАР DELETE (state-changing) — гузвон манъ аст ====================

    public function test_guest_cannot_delete_student_avatar(): void
    {
        $this->seedRoles();
        $this->delete('/admin/students/1/avatar')->assertRedirect('/login');
    }
}
