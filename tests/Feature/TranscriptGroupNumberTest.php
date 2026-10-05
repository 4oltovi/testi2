<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Сутуни «Гурӯҳ» дар транскрипт бояд РАҚАМИ гурӯҳро нишон диҳад (101),
 * на номи он («Кори хамшираги») — чун дар сарлавҳаи «Бахш - Гурӯҳ» ҳамон
 * рақам дода мешавад.
 *
 * Илова бар он, ҳар сатр бояд гурӯҳи ҳамон семестри худро нишон диҳад, на
 * гурӯҳи ҷойиявӣ.
 */
class TranscriptGroupNumberTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Student $student;
    private int $seq = 0;

    private AcademicYear $year;
    private Department $department;
    private Group $group101;
    private Group $group201;
    private User $teacher;

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
        $this->teacher = User::factory()->create(['first_name' => 'Омӯзгор']);
        $this->teacher->roles()->attach($teacherRole->id);

        $this->year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $faculty = Faculty::create([
            'name' => 'Факултеи Тест', 'code' => 'FAC-GRP', 'is_active' => true,
        ]);

        $this->department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-GRP', 'is_active' => true,
        ]);

        $specialty = Specialty::create([
            'department_id' => $this->department->id, 'faculty_id' => $faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-GRP',
            'total_credits' => 120, 'is_active' => true,
        ]);

        $course1 = Course::create(['number' => 1, 'name' => 'Курси 1']);
        $course2 = Course::create(['number' => 2, 'name' => 'Курси 2']);

        $this->group101 = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course1->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Кори хамшираги', 'code' => '101', 'is_active' => true,
        ]);

        $this->group201 = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course2->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Кори хамшираги', 'code' => '201', 'is_active' => true,
        ]);

        $user = User::factory()->create([
            'first_name' => 'Али', 'last_name' => 'Каримов', 'status' => 'active',
        ]);
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );
        $user->roles()->attach($studentRole->id);

        // Донишҷӯ ҳоло дар курси 2 (201) аст
        $this->student = $user->student()->create([
            'group_id' => $this->group201->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course2->id,
            'student_id_number' => 'S2001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);
    }

    private function semester(int $number): Semester
    {
        return Semester::create([
            'academic_year_id' => $this->year->id,
            'number' => $number,
            'name' => 'Семестри ' . $number,
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'is_current' => $number === 1,
            'status' => 'active',
        ]);
    }

    private function seedGrade(int $semNumber, Group $group, ?string $code = null): SubjectAssignment
    {
        $semester = $this->semester($semNumber);

        $subject = Subject::create([
            'department_id' => $this->department->id,
            'name' => 'Фани Тест',
            'code' => $code ?? ('SBJ-' . ++$this->seq),
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        $assignment = SubjectAssignment::create([
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            'group_id' => $group->id,
            'semester_id' => $semester->id,
            'lesson_type' => 'lecture',
            'hours_per_week' => 2,
            'credits' => 6,
        ]);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $assignment->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'rating1_score' => 60,
            'rating2_score' => 60,
            'exam_score' => 60,
            'total_score' => 60.0,
            'letter_grade' => 'C-',
            'grade_point' => 1.67,
            'traditional_grade' => '3',
            'status' => 'passed',
            'is_finalized' => true,
            'credits_earned' => 6,
        ]);

        return $assignment;
    }

    /** Данные, ки PDF/print и экспорт ими истифода мебаранд. */
    private function transcriptRows(): \Illuminate\Support\Collection
    {
        $controller = app(\App\Http\Controllers\Admin\TranscriptController::class);
        $method = new \ReflectionMethod($controller, 'buildTranscriptData');
        $method->setAccessible(true);

        return collect($method->invoke($controller, $this->student->fresh(), null)['rows']);
    }

    // =================================================================

    /**
     * Сатри семестри 1 (гурӯҳ 101) ва семестри 3 (гурӯҳ 201) бояд рақами
     * ҳар як гурӯҳи худро нишон диҳанд — на гурӯҳи ҷойиявӣ (201) дар ҳар
     * як сатр.
     */
    public function test_each_row_shows_the_group_of_that_row_not_the_current_one(): void
    {
        $this->seedGrade(1, $this->group101);
        $this->seedGrade(3, $this->group201);

        $rows = $this->transcriptRows();

        $this->assertCount(2, $rows);

        $bySem = $rows->keyBy('sem');

        // сем 1 → (1-1)%2+1 = 1; сем 3 → (3-1)%2+1 = 1. Ҳарду «Сем 1»,
        // пас бинобар 1 мост, бар асоси семестри асли фарқ мекунем
        $groups = $rows->pluck('group')->values();

        $this->assertContains('101', $groups->all(), 'Сатри семестри 1 бояд 101 нишон диҳад.');
        $this->assertContains('201', $groups->all(), 'Сатри семестри 3 бояд 201 нишон диҳад.');

        // Дои як сатр бояд «201» дошта бошад — сатри семестри 3. Агар ҳар
        // ду сатр «201» мешуд, ин маънои онро дорад, ки коди гурӯҳ ҷойиявӣ
        // истифода шудааст ва таърихи семестр кунун шудааст.
        $this->assertSame(1, $groups->filter(fn ($g) => $g === '201')->count());
        $this->assertSame(1, $groups->filter(fn ($g) => $g === '101')->count());
    }

    /**
     * Рақами гурӯҳ, на номи он.
     */
    public function test_row_shows_group_number_not_group_name(): void
    {
        $this->seedGrade(1, $this->group101);

        $row = $this->transcriptRows()->first();

        $this->assertSame('101', $row['group']);
        $this->assertStringNotContainsString('Кори хамшираги', (string) $row['group']);
    }

    /**
     * PDF/print ва «экспорт» (ҳамон $rows) — як арзиш.
     */
    public function test_print_view_and_export_rows_use_the_same_value(): void
    {
        $this->seedGrade(1, $this->group101);

        $row = $this->transcriptRows()->first();

        $pdfHtml = view('admin.transcript.pdf', [
            'student' => $this->student->fresh(),
            'rows' => collect([$row]),
            'summary' => collect(),
            'totalEarned' => 6,
            'totalMandatory' => 6,
            'studyForm' => 'рӯзона',
            'bahshGroup' => '1-101',
            'specialtyCode' => 'SP-GRP',
            'facultyName' => 'Факултеи Тест',
            'transcriptNumber' => 'TR-TEST',
            'date' => now(),
            'institutionName' => 'Муассиса',
            'deputyDirector' => 'X',
            'centerHead' => 'Y',
        ])->render();

        $this->assertStringContainsString((string) $row['group'], $pdfHtml);
        $this->assertMatchesRegularExpression('/<td class="c">\s*101\s*<\/td>/su', $pdfHtml);
    }

    /**
     * Гурӯҳ бе рақам → «—», на номи он.
     */
    public function test_row_without_group_number_shows_dash(): void
    {
        $noCode = Group::create([
            'specialty_id' => $this->group101->specialty_id,
            'course_id' => $this->group101->course_id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи бе рақам', 'code' => '', 'is_active' => true,
        ]);

        // Гурӯҳи ҷойиявӣ ҳам бе рақам, то захира низ кор накунад
        $this->student->update(['group_id' => $noCode->id]);

        $this->seedGrade(1, $noCode);

        $row = $this->transcriptRows()->first();

        $this->assertSame('—', $row['group']);
    }

    /**
     * Захира: агар таъйёти фан гурӯҳ надошта бошад, рақами гурӯҳи
     * ҷойиявӣ истифода мешавад.
     */
    public function test_falls_back_to_current_group_code_when_row_has_no_group(): void
    {
        $semester = $this->semester(1);

        $subject = Subject::create([
            'department_id' => $this->department->id,
            'name' => 'Фани Тест', 'code' => 'SBJ-NOGRP',
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        $assignment = SubjectAssignment::create([
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacher->id,
            // Гурӯҳ ёфт нашудааст — танҳо курс
            'group_id' => $this->group201->id,
            'semester_id' => $semester->id,
            'lesson_type' => 'lecture',
            'credits' => 6,
        ]);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $assignment->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'total_score' => 60.0,
            'letter_grade' => 'C-',
            'grade_point' => 1.67,
            'traditional_grade' => '3',
            'status' => 'passed',
            'is_finalized' => true,
            'credits_earned' => 6,
        ]);

        // Гурӯҳи сатр 201 аст — ин ҳам аз гурӯҳи ҷойиявӣ мегирад
        $this->assertSame('201', $this->transcriptRows()->first()['group']);
    }

    /**
     * Сутуни PDF бояд рақам бошад — номи дароз дар он ҷо нест.
     */
    public function test_print_view_does_not_contain_the_group_name_in_rows(): void
    {
        $this->seedGrade(1, $this->group101);

        $pdfHtml = view('admin.transcript.pdf', [
            'student' => $this->student->fresh(),
            'rows' => $this->transcriptRows(),
            'summary' => collect(),
            'totalEarned' => 6,
            'totalMandatory' => 6,
            'studyForm' => 'рӯзона',
            'bahshGroup' => '1-101',
            'specialtyCode' => 'SP-GRP',
            'facultyName' => 'Факултеи Тест',
            'transcriptNumber' => 'TR-TEST',
            'date' => now(),
            'institutionName' => 'Муассиса',
            'deputyDirector' => 'X',
            'centerHead' => 'Y',
        ])->render();

        // «Кори хамшираги» танҳо дар самт-баршаванда ҳаст, на дар сатрҳо
        $this->assertMatchesRegularExpression('/101/', $pdfHtml);

        // Дар сутуни гурӯҳ номи гурӯҳ нашавад
        $this->assertStringNotContainsString(
            '<td class="c">Кори хамшираги</td>',
            $pdfHtml,
        );
    }

    /**
     * Панели донишҷӯ: PDF ва HTML бояд рақами гурӯҳро нишон диҳанд.
     */
    public function test_student_transcript_shows_group_number(): void
    {
        $this->seedGrade(1, $this->group101);

        $grades = $this->student->fresh()->semesterGrades()
            ->where('is_finalized', true)
            ->with(['subject', 'semester', 'subjectAssignment.subject', 'subjectAssignment.group'])
            ->get();

        $html = view('student.transcript.pdf', [
            'student' => $this->student->fresh(),
            'grades' => $grades,
        ])->render();

        $this->assertStringContainsString('>101<', $html);
        $this->assertStringNotContainsString('<p><strong>Гурӯҳ:</strong> Кори хамшираги', $html);
    }
}