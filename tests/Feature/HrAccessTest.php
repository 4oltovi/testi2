<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Role;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тестҳои нақши «Кадр».
 *
 * Саволи асосии ин тест: Кадр метавонад кормандро идора кунад, донишҷӯро
 * танҳо барои тасдиқи шахсияти бинад, ва ҳеҷ чӣзи дигареро наметавонад.
 */
class HrAccessTest extends TestCase
{
    use RefreshDatabase;

    // ==================== ЁРГОҲ ====================

    private function role(string $name, string $display, int $level): Role
    {
        return Role::firstOrCreate(
            ['name' => $name],
            ['display_name' => $display, 'level' => $level, 'is_system' => true]
        );
    }

    /** Ҳамаи нақшҳоро як бор месозад ва бармегардонад. */
    private function seedRoles(): array
    {
        return [
            'super_admin' => $this->role('super_admin', 'Суперадмин', 100),
            'admin'       => $this->role('admin', 'Администратор', 90),
            'dean'        => $this->role('dean', 'Декан', 80),
            'teacher'     => $this->role('teacher', 'Омӯзгор', 50),
            'accountant'  => $this->role('accountant', 'Муҳосиб', 40),
            'operator'    => $this->role('operator', 'Оператор', 30),
            'hr'          => $this->role('hr', 'Кадр', 60),
            'student'     => $this->role('student', 'Донишҷӯ', 10),
        ];
    }

    private function userWithRole(Role $role, array $attrs = []): User
    {
        $user = User::factory()->create($attrs + [
            'status'    => 'active',
            'first_name' => 'Тест',
            'last_name'  => 'Тестов',
        ]);

        $user->roles()->attach($role->id);

        return $user->fresh();
    }

    private function makeGroup(int $index = 1): Group
    {
        $year = AcademicYear::create([
            'name'       => '2026-2027',
            'start_year' => 2026,
            'end_year'   => 2027,
            'start_date' => '2026-09-01',
            'end_date'   => '2027-08-31',
            'is_active'  => true,
        ]);

        $faculty = Faculty::create([
            'name' => 'Факултеи Тест', 'code' => 'FAC-HRT', 'is_active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-HRT', 'is_active' => true,
        ]);

        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-HRT',
            'total_credits' => 120, 'is_active' => true,
        ]);

        $course = Course::create(['number' => $index, 'name' => 'Курси '.$index]);

        return Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $year->id, 'name' => 'Гурӯҳи Тест'.$index,
            'code' => (string) $index, 'is_active' => true,
        ]);
    }

    private function makeStudent(array $studentAttrs = []): Student
    {
        $roles = $this->seedRoles();
        $group = $this->makeGroup();

        $user = User::factory()->create([
            'first_name' => 'Али',
            'last_name'  => 'Каримов',
            'status'     => 'active',
        ]);
        $user->roles()->attach($roles['student']->id);

        return $user->student()->create(array_merge([
            'group_id'           => $group->id,
            'specialty_id'       => $group->specialty_id,
            'course_id'          => $group->course_id,
            'student_id_number'  => 'S'.random_int(10000, 99999),
            'enrollment_date'    => now()->subYear()->toDateString(),
            'status'             => 'active',
        ], $studentAttrs));
    }

    // =================================================================
    // 1. Кадр метавонад кормандро дидан, сохтан ва таҳрир кардан
    // =================================================================

    public function test_hr_can_open_own_dashboard(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $this->actingAs($hr)->get(route('hr.dashboard'))->assertOk();
    }

    public function test_hr_can_list_employees_including_teacher_and_other_staff(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $teacher = $this->userWithRole($roles['teacher'], ['first_name' => 'Омӯз']);
        $accountant = $this->userWithRole($roles['accountant'], ['first_name' => 'Мухос']);

        $this->actingAs($hr)->get(route('hr.employees.index'))
            ->assertOk()
            ->assertSee($teacher->full_name)
            ->assertSee($accountant->full_name);
    }

    public function test_hr_employee_list_excludes_admin_superadmin_and_students(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $admin = $this->userWithRole($roles['admin'], ['first_name' => 'Админ']);
        $super = $this->userWithRole($roles['super_admin'], ['first_name' => 'Супер']);

        // Донишҷӯ ҳам корманд нест
        $this->makeStudent();

        $response = $this->actingAs($hr)->get(route('hr.employees.index'))->assertOk();

        $response->assertDontSee($admin->full_name)
            ->assertDontSee($super->full_name)
            ->assertDontSee('Али');
    }

    public function test_hr_can_search_and_filter_employees(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher'], ['first_name' => 'Чаронам']);

        $this->actingAs($hr)
            ->get(route('hr.employees.index', ['search' => 'Чаронам']))
            ->assertOk()
            ->assertSee($teacher->full_name);

        $this->actingAs($hr)
            ->get(route('hr.employees.index', ['role' => 'teacher']))
            ->assertOk()
            ->assertSee($teacher->full_name);
    }

    public function test_hr_can_view_employee_profile(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($hr)->get(route('hr.employees.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->login);
    }

    public function test_hr_can_create_employee(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $this->actingAs($hr)
            ->post(route('hr.employees.store'), [
                'first_name'  => 'Муҳаммад',
                'last_name'   => 'Аҳмадов',
                'middle_name' => 'Раҳимович',
                'login'       => 'ahmadov',
                'email'       => 'ahmadov@example.com',
                'phone'       => '+992900000001',
                'status'      => 'active',
                'password'    => 'secret123',
                'password_confirmation' => 'secret123',
                'roles'       => [$roles['teacher']->id],
            ])
            ->assertRedirect();

        $created = User::where('login', 'ahmadov')->first();

        $this->assertNotNull($created);
        $this->assertTrue($created->isHr() === false);
        $this->assertTrue($created->hasRole('teacher'));
    }

    public function test_hr_can_edit_employee(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($hr)
            ->put(route('hr.employees.update', $teacher), [
                'first_name'  => 'Таҳриршуда',
                'last_name'   => 'Ном',
                'login'       => $teacher->login,
                'status'      => 'active',
            ])
            ->assertRedirect();

        $this->assertSame('Таҳриршуда', $teacher->fresh()->first_name);
    }

    public function test_hr_can_deactivate_and_reactivate_employee(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($hr)
            ->patch(route('hr.employees.status', $teacher), ['activate' => 0])
            ->assertRedirect();

        $this->assertSame('inactive', $teacher->fresh()->status);

        $this->actingAs($hr)
            ->patch(route('hr.employees.status', $teacher), ['activate' => 1])
            ->assertRedirect();

        $this->assertSame('active', $teacher->fresh()->status);
    }

    public function test_hr_operations_are_written_to_audit_log(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher']);

        $this->actingAs($hr)
            ->post(route('hr.employees.store'), [
                'first_name' => 'Аудит', 'last_name' => 'Тест',
                'login' => 'audit-test', 'status' => 'active',
                'password' => 'secret123', 'password_confirmation' => 'secret123',
                'roles' => [$roles['teacher']->id],
            ])->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'action'    => 'create',
            'model_id'  => User::where('login', 'audit-test')->value('id'),
        ]);
    }

    // =================================================================
    // 2. Кадр админ/суперадминро намебинад ва нақши маҳдуд намедиҳад
    // =================================================================

    public function test_hr_cannot_view_admin_or_superadmin_profile(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $admin = $this->userWithRole($roles['admin']);
        $super = $this->userWithRole($roles['super_admin']);

        $this->actingAs($hr)->get(route('hr.employees.show', $admin))->assertForbidden();
        $this->actingAs($hr)->get(route('hr.employees.show', $super))->assertForbidden();
    }

    public function test_hr_cannot_edit_admin_or_superadmin(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $admin = $this->userWithRole($roles['admin']);

        $this->actingAs($hr)->get(route('hr.employees.edit', $admin))->assertForbidden();

        $this->actingAs($hr)->put(route('hr.employees.update', $admin), [
            'first_name' => 'Хакл', 'last_name' => 'Шуд',
            'login' => $admin->login, 'status' => 'active',
        ])->assertForbidden();

        $this->assertNotSame('Хакл', $admin->fresh()->first_name);
    }

    public function test_hr_cannot_deactivate_admin(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $admin = $this->userWithRole($roles['admin']);

        $this->actingAs($hr)
            ->patch(route('hr.employees.status', $admin), ['activate' => 0])
            ->assertForbidden();

        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_hr_cannot_assign_admin_superadmin_or_student_role(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        foreach (['admin', 'super_admin', 'student'] as $forbidden) {
            $this->actingAs($hr)
                ->post(route('hr.employees.store'), [
                    'first_name' => 'Тест', 'last_name' => 'Манъ',
                    'login' => 'try-'.$forbidden, 'status' => 'active',
                    'password' => 'secret123', 'password_confirmation' => 'secret123',
                    'roles' => [$roles[$forbidden]->id],
                ])
                ->assertSessionHasErrors('roles.0');

            $user = User::where('login', 'try-'.$forbidden)->first();

            $this->assertNull($user, "Корбари «{$forbidden}» сохта шуда бояд, агар кадр ин нақшро додан мешуд.");
        }
    }

    public function test_hr_cannot_remove_forbidden_role_from_employee(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $teacher = $this->userWithRole($roles['teacher']);

        // Кӯтоҳ кардани нақш ба ду нақши маҳдуд аз рӯи валидация истод мешавад
        $this->actingAs($hr)->put(route('hr.employees.update', $teacher), [
            'first_name' => 'Тест', 'last_name' => 'Манъ',
            'login' => $teacher->login, 'status' => 'active',
            'roles' => [$roles['admin']->id],
        ])->assertSessionHasErrors('roles.0');

        $this->assertTrue($teacher->fresh()->hasRole('teacher'));
        $this->assertFalse($teacher->fresh()->hasRole('admin'));
    }

    public function test_hr_cannot_change_own_roles(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $this->actingAs($hr)->put(route('hr.employees.update', $hr), [
            'first_name' => 'Тест', 'last_name' => 'Худ',
            'login' => $hr->login, 'status' => 'active',
            'roles' => [$roles['teacher']->id],
        ])->assertForbidden();

        $this->assertTrue($hr->fresh()->isHr());
        $this->assertFalse($hr->fresh()->hasRole('teacher'));
    }

    public function test_hr_cannot_deactivate_self(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $this->actingAs($hr)
            ->patch(route('hr.employees.status', $hr), ['activate' => 0])
            ->assertForbidden();

        $this->assertSame('active', $hr->fresh()->status);
    }

    public function test_there_is_no_delete_route_for_employees(): void
    {
        $routes = collect(app('router')->getRoutes())->map(fn ($r) => $r->methods());

        $hasDelete = collect(app('router')->getRoutes())
            ->filter(fn ($r) => in_array('DELETE', $r->methods(), true))
            ->contains(fn ($r) => str_starts_with($r->uri(), 'hr/employees'));

        $this->assertFalse($hasDelete, 'Нест кардани корманд бояд бо роҳи DELETE мавҷуд нашавад.');
    }

    // =================================================================
    // 3. Донишҷӯён — танҳо хондан
    // =================================================================

    public function test_hr_can_open_student_list(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $student = $this->makeStudent();

        $this->actingAs($hr)->get(route('hr.students.index'))
            ->assertOk()
            ->assertSee($student->full_name);
    }

    public function test_hr_can_open_student_show_page(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $student = $this->makeStudent();

        $this->actingAs($hr)->get(route('hr.students.show', $student))
            ->assertOk()
            ->assertSee($student->full_name)
            ->assertSee($student->student_id_number);
    }

    public function test_student_show_page_contains_identity_but_no_grades_gpa_or_debts(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $student = $this->makeStudent([
            'cumulative_gpa'       => 4.75,
            'total_credits_earned' => 199,
            'has_debts'            => true,
        ]);

        $response = $this->actingAs($hr)->get(route('hr.students.show', $student));

        $response->assertOk();

        // Ҳуқуқи шахсӣ ҳаст
        $response->assertSee($student->full_name)
            ->assertSee($student->student_id_number);

        // Аммо баҳо, кредит ва қарзорӣ НЕ ҳастанд
        $response->assertDontSee('4.75')
            ->assertDontSee('199')
            ->assertDontSee('/admin/debts')
            ->assertDontSee('/admin/reports')
            ->assertDontSee(route('admin.students.edit', $student));
    }

    public function test_hr_cannot_create_edit_or_change_status_of_student(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $student = $this->makeStudent();

        // Сабти нав — 403
        $this->actingAs($hr)
            ->post(route('admin.students.store'), [])
            ->assertForbidden();

        // Таҳрир — 403
        $this->actingAs($hr)
            ->put(route('admin.students.update', $student), [])
            ->assertForbidden();

        // Тағйири ҳолат — 403
        $this->actingAs($hr)
            ->post(route('admin.students.change-status', $student), ['status' => 'expelled'])
            ->assertForbidden();

        // Гузаронидан — 403
        $this->actingAs($hr)
            ->post(route('admin.students.promote', $student), [])
            ->assertForbidden();

        // Ҳеҷ як тағйире дар база (status — enum, пас ->value)
        $this->assertSame('active', $student->fresh()->status->value);
    }

    public function test_hr_cannot_import_or_edit_student_via_import_routes(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        $this->actingAs($hr)
            ->post(route('admin.students.import'), [])
            ->assertForbidden();
    }

    public function test_hr_cannot_remove_student_avatar(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);
        $student = $this->makeStudent();

        $this->actingAs($hr)
            ->delete(route('admin.students.avatar.destroy', $student))
            ->assertForbidden();
    }

    public function test_hr_gets_403_on_other_admin_sections(): void
    {
        $roles = $this->seedRoles();
        $hr = $this->userWithRole($roles['hr']);

        foreach ([
            route('admin.reports.index'),
            route('admin.settings.index'),
            route('admin.journal.index'),
            route('admin.audit.index'),
            route('admin.students.index'),
        ] as $url) {
            $this->actingAs($hr)->get($url)->assertForbidden();
        }
    }

    public function test_non_hr_roles_cannot_open_hr_pages(): void
    {
        $roles = $this->seedRoles();

        foreach (['admin', 'super_admin', 'teacher', 'dean', 'student'] as $roleName) {
            $user = $this->userWithRole($roles[$roleName]);

            $this->actingAs($user)
                ->get(route('hr.employees.index'))
                ->assertForbidden();
        }
    }

    // =================================================================
    // 4. Меҳмони (guest)
    // =================================================================

    public function test_guest_is_redirected_from_all_hr_routes(): void
    {
        $student = null;
        $roles = $this->seedRoles();
        $employee = $this->userWithRole($roles['teacher']);
        $student = $this->makeStudent();

        $urls = [
            route('hr.dashboard'),
            route('hr.employees.index'),
            route('hr.employees.create'),
            route('hr.employees.show', $employee),
            route('hr.employees.edit', $employee),
            route('hr.students.index'),
            route('hr.students.show', $student),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }

        // Навиштаиҳо ҳам ҳифз мешаванд
        $this->post(route('hr.employees.store'), [])->assertRedirect(route('login'));
    }

    // =================================================================
    // 5. Миграция: Бақайдгир -> Кадр
    // =================================================================

    public function test_migration_moves_registrar_users_to_hr_and_leaves_no_user_roleless(): void
    {
        // Ҳолати қӯшӣ: нақши `registrar` мавҷуд аст ва корбар онро дорад
        $registrar = Role::create([
            'name' => 'registrar', 'display_name' => 'Бақайдгир',
            'level' => 60, 'is_system' => true,
        ]);

        $oldUser = User::factory()->create();
        $oldUser->roles()->attach($registrar->id);

        // Миграцияро дастӣ мегузардем, чун дар RefreshDatabase он аллакай
        // иҷро шуда ва база тоза аст
        $migration = require database_path(
            'migrations/2026_10_01_000001_replace_registrar_role_with_hr.php'
        );
        $migration->up();

        // Нақши `hr` ҳаст
        $this->assertNotNull(Role::where('name', 'hr')->first());
        $this->assertSame('Кадр', Role::where('name', 'hr')->value('display_name'));

        // Нақши `registrar` нест шудааст
        $this->assertNull(Role::where('name', 'registrar')->first());

        // Корбар нақши Кадрро гирифтааст
        $this->assertTrue($oldUser->fresh()->isHr());
        $this->assertFalse($oldUser->fresh()->hasRole('registrar'));

        // Ҳеҷ корбаре бе нақш намондааст
        $this->assertSame(0, User::doesntHave('roles')->count());
    }

    public function test_hr_user_keeps_single_hr_role_after_migration(): void
    {
        // Агар корбар аллакай `hr` дошт, ду бор нашавад
        $hrRole = $this->role('hr', 'Кадр', 60);
        $user = User::factory()->create();
        $user->roles()->attach($hrRole->id);

        $registrar = Role::create([
            'name' => 'registrar', 'display_name' => 'Бақайдгир',
            'level' => 60, 'is_system' => true,
        ]);
        $user->roles()->attach($registrar->id);

        $migration = require database_path(
            'migrations/2026_10_01_000001_replace_registrar_role_with_hr.php'
        );
        $migration->up();

        $this->assertSame(1, $user->fresh()->roles()->count());
        $this->assertTrue($user->fresh()->isHr());
    }

    public function test_user_role_enum_uses_hr_and_not_registrar(): void
    {
        $values = array_map(
            fn ($case) => $case->value,
            \App\Enums\UserRole::cases()
        );

        $this->assertContains('hr', $values);
        $this->assertNotContains('registrar', $values);
    }

    public function test_employee_roles_exclude_admin_superadmin_and_student(): void
    {
        $employeeRoles = \App\Enums\UserRole::employeeRoles();

        $this->assertNotContains('admin', $employeeRoles);
        $this->assertNotContains('super_admin', $employeeRoles);
        $this->assertNotContains('student', $employeeRoles);
        $this->assertContains('teacher', $employeeRoles);
        $this->assertContains('hr', $employeeRoles);
    }
}