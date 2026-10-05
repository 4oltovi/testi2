<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Role;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Specialty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Тестҳои бонусҳои панели донишҷӯ (/student/dashboard), аз ҷумла
 * `Student::earnedCredits()` ва бонусҳи «Кредитҳо».
 */
class StudentDashboardCardsTest extends TestCase
{
    use RefreshDatabase;

    private array $org = [];

    private function makeStudent(?int $specialtyTotalCredits = 120): User
    {
        if ($this->org === []) {
            $year = AcademicYear::create([
                'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
                'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
            ]);
            $faculty = Faculty::create(['name' => 'Ф', 'code' => 'F1', 'is_active' => true]);
            $department = Department::create([
                'faculty_id' => $faculty->id, 'name' => 'К', 'code' => 'D1', 'is_active' => true,
            ]);
            $specialty = Specialty::create([
                'department_id' => $department->id, 'faculty_id' => $faculty->id,
                'name' => 'Ихтисос', 'code' => 'S1',
                'total_credits' => $specialtyTotalCredits, 'is_active' => true,
            ]);
            $course = Course::create(['number' => 1, 'name' => 'Курси 1']);
            $semester = Semester::create([
                'academic_year_id' => $year->id, 'number' => 1, 'name' => 'Семестри 1',
                'start_date' => '2026-09-01', 'end_date' => '2027-01-15', 'is_current' => true,
            ]);
            $group = Group::create([
                'specialty_id' => $specialty->id, 'course_id' => $course->id,
                'academic_year_id' => $year->id, 'name' => 'Г1', 'code' => 'g1', 'is_active' => true,
            ]);

            $teacherRole = Role::firstOrCreate([
                'name' => 'teacher', 'display_name' => 'Омӯзгор', 'level' => 30, 'is_system' => true,
            ]);
            $teacherUser = User::factory()->create(['status' => 'active']);
            $teacherUser->roles()->attach($teacherRole->id);
            $teacher = $teacherUser->teacher()->create([
                'department_id' => $department->id,
                'employee_id' => 'E1', 'employee_number' => 'T1',
                'hire_date' => now()->toDateString(),
                'position' => 'teacher', 'status' => 'active',
            ]);

            $role = Role::firstOrCreate([
                'name' => 'student', 'display_name' => 'Донишҷӯ',
                'level' => 10, 'is_system' => true,
            ]);
            $user = User::factory()->create(['status' => 'active']);
            $user->roles()->attach($role->id);
            $student = $user->student()->create([
                'group_id' => $group->id, 'specialty_id' => $specialty->id, 'course_id' => $course->id,
                'student_id_number' => 'S2000', 'enrollment_date' => now()->subYear()->toDateString(),
                'status' => 'active',
            ]);

            $this->org = compact(
                'year', 'faculty', 'department', 'specialty', 'course',
                'semester', 'group', 'teacherUser', 'student'
            );
        }

        return User::find($this->org['student']->user_id);
    }

    /**
     * Як баҳои семестрӣ бо ҳолти дархост месозад.
     */
    private function makeGrade(
        \App\Models\Student $student,
        string $subjectName,
        string $code,
        int $assignmentCredits,
        string $status,
        ?int $semesterId = null
    ): void {
        $subject = Subject::create([
            'name' => $subjectName, 'code' => $code,
            'department_id' => $this->org['department']->id,
            'credits' => 1, 'exam_type' => 'exam',
        ]);

        $assignment = SubjectAssignment::create([
            'subject_id' => $subject->id,
            'group_id' => $this->org['group']->id,
            'teacher_id' => $this->org['teacherUser']->id,
            'semester_id' => $semesterId ?? $this->org['semester']->id,
            'lesson_type' => 'lecture', 'total_hours' => 60,
            'credits' => $assignmentCredits, 'is_active' => true,
        ]);

        SemesterGrade::create([
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'subject_assignment_id' => $assignment->id,
            'semester_id' => $assignment->semester_id,
            'status' => $status,
            'letter_grade' => $status === 'passed' ? 'C' : 'F',
            'grade_point' => $status === 'passed' ? 2.0 : 0.0,
            'total_score' => $status === 'passed' ? 65 : 40,
            'is_finalized' => true,
        ]);
    }

    // ==================== earnedCredits() ====================

    public function test_passed_subjects_sum_their_assignment_credits(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        // 6 + 2 = 8
        $this->makeGrade($student, 'Забони тоҷикӣ', 'TJ1', 6, 'passed');
        $this->makeGrade($student, 'Алгебра', 'AL1', 2, 'passed');

        $this->assertEquals(8, $student->earnedCredits());
        $this->assertEquals(8, $student->fresh()->earned_credits);
    }

    public function test_failed_subject_contributes_no_credits(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $this->makeGrade($student, 'Забони тоҷикӣ', 'TJ1', 6, 'passed');
        $this->makeGrade($student, 'Алгебра', 'AL1', 2, 'failed');   // F -> 0

        $this->assertEquals(6, $student->earnedCredits());
    }

    public function test_subject_with_no_final_grade_contributes_nothing(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $this->makeGrade($student, 'Забони тоҷикӣ', 'TJ1', 6, 'passed');
        // Ҳолти «ин_progress» = баҳои ниҳоӣ нест
        $this->makeGrade($student, 'Физика', 'FZ1', 4, 'in_progress');

        $this->assertEquals(6, $student->earnedCredits());
    }

    public function test_passed_retake_counts_only_once(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        // Такрорӣ сатри ҳамро кор мекунад (semester_grade_unique = student+assignment+semester),
        // пас ҳолти воқеӣ: як сатр, ки аввал F буд ва баъди такрорӣ «гузашта» шуд.
        $subject = Subject::create([
            'name' => 'Забони тоҷикӣ', 'code' => 'TJ1',
            'department_id' => $this->org['department']->id,
            'credits' => 1, 'exam_type' => 'exam',
        ]);
        $assignment = SubjectAssignment::create([
            'subject_id' => $subject->id, 'group_id' => $this->org['group']->id,
            'teacher_id' => $this->org['teacherUser']->id,
            'semester_id' => $this->org['semester']->id,
            'lesson_type' => 'lecture', 'total_hours' => 60,
            'credits' => 6, 'is_active' => true,
        ]);

        $grade = SemesterGrade::create([
            'student_id' => $student->id, 'subject_id' => $subject->id,
            'subject_assignment_id' => $assignment->id,
            'semester_id' => $assignment->semester_id,
            'status' => 'passed', 'letter_grade' => 'C',
            'grade_point' => 2.0, 'total_score' => 65,
            // Нишондиҳи такрорӣ: кӯҳна аз кӯшиши аввал, ки 0 буд
            'retake_score' => 65, 'retake_date' => now()->toDateString(),
            'credits_earned' => 0,
            'is_finalized' => true,
        ]);

        // Як маротиба -> 6 (на 12, на 0 аз рӯи credits_earned-и кӯҳна)
        $this->assertEquals(6, $student->earnedCredits());

        // Агар як сатр ду маротиба ба вобасташавад, ҳамон 6 мемонад
        $student->semesterGrades()->update(['status' => 'passed']);
        $this->assertEquals(6, $student->fresh()->earnedCredits());
    }

    public function test_same_subject_in_two_semesters_counts_once(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $subject = Subject::create([
            'name' => 'Физика', 'code' => 'FZ1',
            'department_id' => $this->org['department']->id,
            'credits' => 1, 'exam_type' => 'exam',
        ]);

        // Ҳамин фан дар ду семестр, ду таъйин, ҳар яке кредити 3
        foreach ([2 => 'lecture', 3 => 'practice'] as $semNumber => $lessonType) {
            $semester = Semester::create([
                'academic_year_id' => $this->org['year']->id, 'number' => $semNumber,
                'name' => "Семестри $semNumber",
                'start_date' => '2026-09-01', 'end_date' => '2027-01-15',
            ]);
            $assignment = SubjectAssignment::create([
                'subject_id' => $subject->id, 'group_id' => $this->org['group']->id,
                'teacher_id' => $this->org['teacherUser']->id,
                'semester_id' => $semester->id,
                'lesson_type' => $lessonType, 'total_hours' => 60,
                'credits' => 3, 'is_active' => true,
            ]);
            SemesterGrade::create([
                'student_id' => $student->id, 'subject_id' => $subject->id,
                'subject_assignment_id' => $assignment->id,
                'semester_id' => $semester->id,
                'status' => 'passed', 'letter_grade' => 'C',
                'grade_point' => 2.0, 'total_score' => 65, 'is_finalized' => true,
            ]);
        }

        $this->assertEquals(3, $student->earnedCredits());
    }

    public function test_no_passed_subjects_gives_zero(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $this->makeGrade($student, 'Алгебра', 'AL1', 2, 'failed');

        $this->assertEquals(0, $student->earnedCredits());
    }

    public function test_earned_credits_ignores_the_unmaintained_column(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $this->makeGrade($student, 'Забони тоҷикӣ', 'TJ1', 6, 'passed');
        $this->makeGrade($student, 'Алгебра', 'AL1', 2, 'passed');

        // Сутуни кӯҳна дар коди барнома пур намешавад -> 0
        $student->forceFill(['total_credits_earned' => 0])->save();

        $this->assertEquals(0, $student->fresh()->total_credits_earned);
        $this->assertEquals(8, $student->fresh()->earnedCredits());
    }

    // ==================== БОНУСИ «КРЕДИТҲО» ====================

    public function test_dashboard_credit_card_shows_number_without_denominator(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;

        $this->makeGrade($student, 'Забони тоҷикӣ', 'TJ1', 6, 'passed');
        $this->makeGrade($student, 'Алгебра', 'AL1', 2, 'passed');

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        // танҳо рақм ва лейбли «Кредитҳо» — бе «/ N»
        $this->assertMatchesRegularExpression(
            '/<h3 class="mb-0">8<\/h3>\s*<small class="text-muted">Кредитҳо<\/small>/',
            $html
        );
        $this->assertStringNotContainsString('Кредитҳо /', $html);
        $this->assertStringNotContainsString('Кредитҳо / 120', $html);
    }

    public function test_dashboard_credit_card_shows_zero_with_no_passes(): void
    {
        $user = $this->makeStudent();

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<h3 class="mb-0">0<\/h3>\s*<small class="text-muted">Кредитҳо<\/small>/',
            $html
        );
    }

    // ==================== ДАВОМОТ (аз аудити қабулӣ) ====================

    public function test_attendance_is_dash_when_no_records_exist(): void
    {
        $user = $this->makeStudent();

        $this->assertNull($user->student->getAttendancePercentage());

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<h3 class="mb-0">—<\/h3>\s*<small class="text-muted">Давомот<\/small>/',
            $html
        );
        $this->assertStringNotContainsString('100%', $html);
    }

    public function test_attendance_shows_present_ratio_when_records_exist(): void
    {
        $user = $this->makeStudent();
        $student = $user->student;
        $admin = User::factory()->create();

        foreach ([
            ['present', '2026-09-01'],
            ['present', '2026-09-02'],
            ['absent', '2026-09-03'],
            ['present', '2026-09-04'],
        ] as [$status, $date]) {
            DB::table('daily_attendance')->insert([
                'student_id' => $student->id,
                'group_id' => $student->group_id,
                'attendance_date' => $date,
                'status' => $status,
                'marked_by' => $admin->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->assertEquals(75.0, $student->getAttendancePercentage());

        $html = $this->actingAs($user)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/<h3 class="mb-0">75%<\/h3>\s*<small class="text-muted">Давомот<\/small>/',
            $html
        );
    }
}