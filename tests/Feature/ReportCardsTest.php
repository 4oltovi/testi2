<?php

namespace Tests\Feature;

use App\Models\AcademicDebt;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Карточаҳои омории саҳифаи «Ҳисоботҳо» (/admin/reports):
 *
 *  - ҳамон дам рӯй навишта мешаванд (бе кеш);
 *  - танҳо сабтҳои фаъол ҳисоб мешаванд;
 *  - қарздорон — танҳо он донишҷӯёне, ки ҲОЛО қарзи
 *    кушод доранд (на сутураи кӯҳнаи `students.has_debts`).
 */
class ReportCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacherUser;
    private Faculty $faculty;
    private Department $department;
    private Specialty $specialty;
    private Course $course;
    private AcademicYear $year;
    private Semester $semester;
    private Group $studentGroup;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Администратор', 'level' => 90, 'is_system' => true]
        );
        $this->admin = User::factory()->create(['first_name' => 'Админ']);
        $this->admin->roles()->attach($adminRole->id);

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true]
        );
        $this->teacherUser = User::factory()->create(['first_name' => 'Омӯзгор']);
        $this->teacherUser->roles()->attach($teacherRole->id);

        $this->year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $this->faculty = Faculty::create([
            'name' => 'Факултеи Тест', 'code' => 'FAC-RC', 'is_active' => true,
        ]);

        $this->department = Department::create([
            'faculty_id' => $this->faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-RC', 'is_active' => true,
        ]);

        $this->specialty = Specialty::create([
            'department_id' => $this->department->id, 'faculty_id' => $this->faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-RC',
            'total_credits' => 120, 'is_active' => true,
        ]);

        $this->course = Course::create(['number' => 1, 'name' => 'Курси 1']);

        $this->semester = Semester::create([
            'academic_year_id' => $this->year->id,
            'number' => 1,
            'name' => 'Семестри 1',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'is_current' => true,
            'status' => 'active',
        ]);

        // Гурӯҳи умумии донишҷӯёни тест
        $this->studentGroup = $this->makeGroup();
    }

    // ==================== ЁРГОҲ ====================

    private function makeGroup(bool $active = true): Group
    {
        return Group::create([
            'specialty_id' => $this->specialty->id,
            'course_id' => $this->course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи ' . ++$this->seq,
            'code' => 'G' . $this->seq,
            'is_active' => $active,
        ]);
    }

    private function makeStudent(bool $active = true, bool $hasDebtsFlag = false): Student
    {
        $user = User::factory()->create([
            'first_name' => 'Донишҷӯ', 'status' => 'active',
        ]);

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );
        $user->roles()->attach($studentRole->id);

        return $user->student()->create([
            'group_id' => $this->studentGroup->id,
            'specialty_id' => $this->specialty->id,
            'course_id' => $this->course->id,
            'student_id_number' => 'S' . (10000 + ++$this->seq),
            'enrollment_date' => now()->subYear()->toDateString(),
            'status' => $active ? 'active' : 'expelled',
            'has_debts' => $hasDebtsFlag,
        ]);
    }

    private function makeDebt(Student $student, string $status = 'active'): AcademicDebt
    {
        $group = $this->makeGroup();

        $subject = Subject::create([
            'department_id' => $this->department->id,
            'name' => 'Фани Тест', 'code' => 'SBJ-' . $this->seq,
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        $assignment = SubjectAssignment::create([
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherUser->id,
            'group_id' => $group->id,
            'semester_id' => $this->semester->id,
            'lesson_type' => 'lecture',
            'hours_per_week' => 2,
            'credits' => 6,
        ]);

        $grade = $student->semesterGrades()->create([
            'subject_assignment_id' => $assignment->id,
            'subject_id' => $subject->id,
            'semester_id' => $this->semester->id,
            'total_score' => 30.0,
            'letter_grade' => 'F',
            'grade_point' => 0.0,
            'traditional_grade' => '1',
            'status' => 'failed',
            'is_finalized' => true,
            'credits_earned' => 0,
        ]);

        return AcademicDebt::create([
            'student_id' => $student->id,
            'semester_grade_id' => $grade->id,
            'subject_id' => $subject->id,
            'semester_id' => $this->semester->id,
            'reason' => 'exam_failed',
            'debt_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
            'status' => $status,
        ]);
    }

    /** Қимати корти номбаршударо аз HTML мебарад. */
    private function cardValue(string $html, string $label): ?string
    {
        if (!preg_match(
            '/<h3 class="[^"]*mb-0">(\d+)<\/h3>\s*<small class="text-muted">' . preg_quote($label, '/') . '<\/small>/s',
            $html,
            $m
        )) {
            return null;
        }

        return $m[1];
    }

    // ==================== ТЕСТҲО ====================

    /** Картачаҳо танҳо сабтҳои фаъолро ҳисоб мекунанд. */
    public function test_cards_count_active_students_teachers_groups_and_faculties_only(): void
    {
        $this->makeStudent();
        $this->makeStudent();
        $this->makeStudent(active: false);

        Teacher::create([
            'user_id' => $this->teacherUser->id,
            'department_id' => $this->department->id,
            'employee_id' => 'EMP-' . ++$this->seq,
            'position' => 'Ассистент',
            'hire_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);

        $inactiveTeacherUser = User::factory()->create(['first_name' => 'Омӯзгори Ғайрифаъол']);
        Teacher::create([
            'user_id' => $inactiveTeacherUser->id,
            'department_id' => $this->department->id,
            'employee_id' => 'EMP-' . ++$this->seq,
            'position' => 'Ассистент',
            'hire_date' => now()->subYear()->toDateString(),
            'status' => 'dismissed',
        ]);

        $this->makeGroup();
        $this->makeGroup(active: false);

        Faculty::create(['name' => 'Факултеи Ғайрифаъол', 'code' => 'FAC-IA', 'is_active' => false]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('2', $this->cardValue($html, 'Донишҷӯён'));
        $this->assertSame('1', $this->cardValue($html, 'Омӯзгорон'));
        // Гурӯҳи умумии донишҷӯён + як гурӯҳи алоҳидаи фаъол
        $this->assertSame('2', $this->cardValue($html, 'Гурӯҳҳо'));
        $this->assertSame('1', $this->cardValue($html, 'Факултетҳо'));
    }

    /**
     * Қарздорон — танҳо он донишҷӯёне, ки ҳоло қарзи кушод
     * доранд. Сутураи кӯҳнаи `has_debts` ба назар нагирифта мешавад.
     */
    public function test_debtors_card_counts_only_students_with_open_debts_now(): void
    {
        // Қарзи кушод дорад
        $debtor = $this->makeStudent();
        $this->makeDebt($debtor, 'active');

        // Қарз ҳал шудааст
        $resolved = $this->makeStudent();
        $this->makeDebt($resolved, 'resolved');

        // Сутураи has_debts кӯҳна ва дуруст нест, аммо қарзе нест
        $staleFlag = $this->makeStudent(hasDebtsFlag: true);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('1', $this->cardValue($html, 'Қарздорон'));
        $this->assertSame('1', $this->cardValue($html, 'Қарзҳои кушод'));
    }

    /** Ҳамаи ҳолатҳои кушода — ҳамон қарзҳои кушод. */
    public function test_active_debts_card_counts_all_open_statuses(): void
    {
        $student = $this->makeStudent();

        $this->makeDebt($student, 'active');
        $this->makeDebt($student, 'retake_scheduled');
        $this->makeDebt($student, 'escalated');
        $this->makeDebt($student, 'resolved');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('3', $this->cardValue($html, 'Қарзҳои кушод'));
        $this->assertSame('1', $this->cardValue($html, 'Қарздорон'));
    }

    /** Пас аз ҳал кардани қарз картачаҳо ДАР ҲАМОН ЗАМОН тағйир мешаванд. */
    public function test_cards_update_immediately_after_debt_is_resolved(): void
    {
        $student = $this->makeStudent();
        $debt = $this->makeDebt($student, 'active');

        $first = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('1', $this->cardValue($first, 'Қарздорон'));
        $this->assertSame('1', $this->cardValue($first, 'Қарзҳои кушод'));

        // Қарз ҳал мешавад
        $debt->update(['status' => 'resolved', 'resolved_date' => now()]);
        $student->update(['has_debts' => false]);

        $second = $this->actingAs($this->admin)
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame('0', $this->cardValue($second, 'Қарздорон'),
            'Пас аз ҳал кардани қарз картачаи қарздорон бояд фавран 0 шавад.');
        $this->assertSame('0', $this->cardValue($second, 'Қарзҳои кушод'));
    }
}
