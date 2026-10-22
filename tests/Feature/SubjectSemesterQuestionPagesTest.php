<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Role;
use App\Models\User;
use App\Models\Subject;
use App\Models\Department;
use App\Models\Faculty;

class SubjectSemesterQuestionPagesTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        $role = Role::where('name', 'admin')->firstOrCreate([
            'name' => 'admin',
            'display_name' => 'Администратор',
            'level' => 90,
            'is_system' => true,
        ]);
        $user = User::factory()->create(['status' => 'active']);
        $user->roles()->attach($role->id);
        return $user;
    }

    private function makeSubjects(): array
    {
        $faculty = Faculty::create(['name' => 'F', 'code' => 'F1', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'name' => 'D', 'code' => 'D1', 'is_active' => true]);
        $s1 = Subject::create(['department_id' => $dept->id, 'name' => 'Sub1', 'code' => 'C1', 'semester_number' => 1, 'is_active' => true]);
        $s2 = Subject::create(['department_id' => $dept->id, 'name' => 'Sub2', 'code' => 'C2', 'semester_number' => 2, 'is_active' => true]);
        $s3 = Subject::create(['department_id' => $dept->id, 'name' => 'Sub3', 'code' => 'C3', 'semester_number' => null, 'is_active' => true]);
        return [$s1, $s2, $s3];
    }

    public function test_rating_questions_filter(): void
    {
        $this->createAdmin();
        [$s1, $s2, $s3] = $this->makeSubjects();
        $admin = User::first();
        $this->actingAs($admin);

        $res = $this->get('/admin/rating-questions?semester_number=1');
        $res->assertStatus(200);
        $res->assertSee('Sub1');
        $res->assertDontSee('Sub2');
        $res->assertDontSee('Sub3');

        $res2 = $this->get('/admin/rating-questions');
        $res2->assertSee('Sub1');
        $res2->assertSee('Sub2');
        $res2->assertSee('Sub3');
    }

    public function test_exams_questions_filter(): void
    {
        $this->createAdmin();
        [$s1, $s2, $s3] = $this->makeSubjects();
        $admin = User::first();
        $this->actingAs($admin);

        $res = $this->get('/admin/exams/questions?semester_number=1');
        $res->assertStatus(200);
        $res->assertSee('Sub1');
        $res->assertDontSee('Sub2');
        $res->assertDontSee('Sub3');

        $res2 = $this->get('/admin/exams/questions');
        $res2->assertSee('Sub1');
        $res2->assertSee('Sub2');
        $res2->assertSee('Sub3');
    }
}
