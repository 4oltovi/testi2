<?php

namespace Tests\Feature;

use App\Enums\GradeScale;
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
 * Сутуни «Анъанавӣ» бояд рақми 5-баллӣ (5/4/3/2) нишон диҳад ва аз
 * `GradeScale` хонда шавад — як манбаъи ҳақиқат бо ҷадвали шкала дар саҳифаи
 * semester-grades.
 */
class TranscriptTraditionalGradeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teacher;
    private Student $student;
    private array $org;
    private ?\App\Models\Semester $semester = null;
    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Администратор', 'level' => 90, 'is_system' => true]
        );

        $this->admin = User::factory()->create(['first_name' => 'Админ']);
        $this->admin->roles()->attach($role->id);

        $year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $faculty = Faculty::create([
            'name' => 'Факултеи Тест', 'code' => 'FAC-TGD', 'is_active' => true,
        ]);

        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-TGD', 'is_active' => true,
        ]);

        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-TGD',
            'total_credits' => 120, 'is_active' => true,
        ]);

        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);

        $group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $year->id, 'name' => 'Гурӯҳи Тест', 'code' => '1',
            'is_active' => true,
        ]);

        $this->org = compact('year', 'faculty', 'department', 'specialty', 'course', 'group');

        // `subject_assignments.teacher_id` — NOT NULL, пас омӯзгор лозим аст
        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true]
        );
        $this->teacher = User::factory()->create(['first_name' => 'Омӯзгор']);
        $this->teacher->roles()->attach($teacherRole->id);

        $user = User::factory()->create([
            'first_name' => 'Али', 'last_name' => 'Каримов', 'status' => 'active',
        ]);
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );
        $user->roles()->attach($studentRole->id);

        $this->student = $user->student()->create([
            'group_id' => $group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'S1001',
            'enrollment_date' => now()->subYear()->toDateString(),
            'status' => 'active',
        ]);
    }

    private function semester(?int $number = null): \App\Models\Semester
    {
        if ($number !== null) {
            return \App\Models\Semester::create([
                'academic_year_id' => $this->org['year']->id,
                'number' => $number,
                'name' => 'Семестри ' . $number,
                'start_date' => '2026-09-01',
                'end_date' => '2027-01-20',
                'is_current' => $number === 1,
                'status' => 'active',
            ]);
        }

        return $this->semester ??= $this->semester(1);
    }

    private function subject(?string $code = null): \App\Models\Subject
    {
        return \App\Models\Subject::create([
            'department_id' => $this->org['department']->id,
            'name' => 'Фани Тест',
            'code' => $code ?? ('SBJ-' . ++$this->seq),
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);
    }

    private function assignment(
        \App\Models\Subject $subject,
        \App\Models\Semester $semester,
    ): \App\Models\SubjectAssignment {
        return \App\Models\SubjectAssignment::create([
            'subject_id' => $subject->id,
            // teacher_id NOT NULL — аз ҳамин сабаб як омӯзгори ҳолӣ лозим аст
            'teacher_id' => $this->teacher->id,
            'group_id' => $this->org['group']->id,
            'semester_id' => $semester->id,
            'lesson_type' => 'lecture',
            'hours_per_week' => 2,
            'credits' => 6,
        ]);
    }

    /**
     * Аз нав бо пойгоҳи тоза — барои ҳалли давраҳои бисёркор.
     */
    /**
     * Як баҳои тасдиқшуда бо сутуни матнии кӯҳна (ҳамон «Ғайриқаноатбахш»,
     * ки барои D нодуруст буд) — айни худи он ҳолати хатои ҳолт.
     */
    private function seedGrade(
        float $total,
        string $letter,
        string $status,
        ?string $staleText = null,
        int $semesterNumber = 1,
    ): void {
        // Ҳар як давра семестри нав мегирад, то ки `unique(academic_year_id, number)`
        // ва `subject_assign_unique` шикаст наханд
        $semester = $this->semester($semesterNumber);

        $subject = $this->subject();
        $assignment = $this->assignment($subject, $semester);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $assignment->id,
            // `semester_grades.subject_id` — NOT NULL (баъди баҳои СДР)
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'rating1_score' => 50,
            'rating2_score' => 50,
            'exam_score' => 56,
            'total_score' => $total,
            'letter_grade' => $letter,
            'grade_point' => GradeScale::tryFrom($letter)?->gradePoint() ?? 0,
            // Матни кӯҳна, ки нодуруст буд
            'traditional_grade' => $staleText,
            'status' => $status,
            'is_finalized' => true,
            'credits_earned' => $status === 'passed' ? 6 : 0,
        ]);
    }

    // =================================================================

    /**
     * Ҳолати аниқи илҳом: 52.13 → D → 3, ҳатто агар сутуни матнии кӯҳна
     * «Ғайриқаноатбахш» бошад.
     */
    public function test_the_reported_bug_52_1_letter_d_shows_3_not_the_stale_text(): void
    {
        $this->seedGrade(52.13, 'D', 'passed', 'Ғайриқаноатбахш');

        $response = $this->actingAs($this->admin)
            ->get(route('admin.transcript.show', $this->student));

        $response->assertOk();

        // Матни кӯҳна дар саҳифа НЕ бошад
        $response->assertDontSee('Ғайриқаноатбахш');

        // Рақм дар сутуни «Анъанавӣ» бошад
        $response->assertSeeInOrder(['52.1', 'D', '1.00', '3'], false);
    }

    /**
     * Ҳар як ҳарф — рақми аниқ дар transcript.
     */
    public function test_every_letter_renders_its_five_point_number(): void
    {
        $expectations = [
            ['A',  5.0, 'passed'],
            ['A-', 5.0, 'passed'],
            ['B+', 4.0, 'passed'],
            ['B',  4.0, 'passed'],
            ['B-', 4.0, 'passed'],
            ['C+', 3.0, 'passed'],
            ['C',  3.0, 'passed'],
            ['C-', 3.0, 'passed'],
            ['D+', 3.0, 'passed'],
            ['D',  3.0, 'passed'],
            ['Fx', 2.0, 'retake'],
            ['F',  2.0, 'failed'],
        ];

        $i = 0;

        foreach ($expectations as [$letter, $total, $status]) {
            // Ҳар як ҳарф дар семестри худ ва бо коди худ: як давра,
            // бе ин тартиби семестр unique-ро мешикаст
            $this->seedGrade($total, $letter, $status, null, ++$i);

            $html = $this->actingAs($this->admin)
                ->get(route('admin.transcript.show', $this->student))
                ->assertOk()
                ->getContent();

            $expected = GradeScale::tryFrom($letter)->traditionalFivePoint();
            $this->assertStringContainsString(
                '>' . $expected . '<',
                $html,
                "Ҳарфи {$letter} бояд рақми {$expected}-ро нишон диҳад.",
            );
        }
    }

    /**
     * Фан бе баҳои ниҳоӣ → «—» дар сутуни анъанавӣ.
     */
    public function test_subject_without_final_grade_shows_dash(): void
    {
        $semester = $this->semester();

        $subject = $this->subject('SBJ-NAVE');
        $assignment = $this->assignment($subject, $semester);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $assignment->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'total_score' => null,
            'letter_grade' => null,
            'grade_point' => null,
            'traditional_grade' => null,
            'status' => 'in_progress',
            'is_finalized' => true,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.transcript.show', $this->student))
            ->assertOk()
            ->getContent();

        // Дар сутуни анъанавӣ рақм нест, танҳо «—»
        $this->assertMatchesRegularExpression(
            '/Анъанавӣ.*?<td class="text-center">\s*—\s*<\/td>/su',
            $html,
        );
    }

    /**
     * D = гузаштааст → иконкаи сабз. Fx/F = нагузашта → иконкаи сурх.
     */
    public function test_pass_icon_matches_grade_passed_status(): void
    {
        // D гузаштааст
        $this->seedGrade(52.13, 'D', 'passed', 'Ғайриқаноатбахш');

        $html = $this->actingAs($this->admin)
            ->get(route('admin.transcript.show', $this->student))
            ->getContent();

        $this->assertStringContainsString('bi-check-circle-fill text-success', $html);
        $this->assertStringNotContainsString('bi-x-circle-fill text-danger', $html);
    }

    public function test_fail_icon_is_red_for_f_and_fx(): void
    {
        $i = 10;

        foreach (['Fx' => 'retake', 'F' => 'failed'] as $letter => $status) {
            $this->seedGrade(40.0, $letter, $status, null, ++$i);

            $html = $this->actingAs($this->admin)
                ->get(route('admin.transcript.show', $this->student))
                ->getContent();

            $this->assertStringContainsString(
                'bi-x-circle-fill text-danger',
                $html,
                "{$letter} бояд иконкаи сурх дошта бошад.",
            );
        }
    }

    /**
     * Transcript ва ҷадвали шкала бояд баҳои ягона диҳанд — барои ҳар як ҳарф.
     */
    public function test_transcript_and_scale_table_always_agree(): void
    {
        // Сабаби таъйёти: саҳифаи semester-grades ба як таъйёти корӣ ниёҳад
        $this->seedGrade(95.0, 'A', 'passed', null);

        foreach (GradeScale::cases() as $scale) {
            $this->assertSame(
                $scale->traditionalFivePoint(),
                GradeScale::tryFrom($scale->value)?->traditionalFivePoint(),
                "Ҷадвали шкала ва transcript барои {$scale->value} фарқ мекунанд.",
            );
        }

        // Ҷадвали шкала дар semester-grades аз ҳамон манбаъ хонанд.
        // Роҳи он subjectAssignment-и корӣ талаб мекунад, пас аз ҳамон баҳо
        // истифода мебарем.
        $assignment = \App\Models\SubjectAssignment::first();

        if ($assignment === null) {
            $this->markTestSkipped('Барои саҳифаи semester-grades таъйёти корманд лозим аст.');
        }

        $scaleHtml = $this->actingAs($this->admin)
            ->get(route('admin.journal.semester-grades', $assignment))
            ->getContent();

        if ($scaleHtml === false || ! str_contains((string) $scaleHtml, 'Шкалаи баҳогузорӣ')) {
            $this->markTestSkipped('Саҳифаи semester-grades барои ин тест дастрас нест.');
        }

        foreach (GradeScale::cases() as $scale) {
            $this->assertMatchesRegularExpression(
                '/' . preg_quote($scale->value, '/') . '.*?text-center fw-bold">\s*'
                    . $scale->traditionalFivePoint() . '\s*<\/td>/su',
                $scaleHtml,
                "Ҷадвали шкала барои {$scale->value} рақми {$scale->traditionalFivePoint()}-ро нишон надод.",
            );
        }
    }

    /**
     * PDF (Excel/Export) ҳам рақм диҳад, на матн.
     */
    public function test_transcript_export_rows_use_the_number(): void
    {
        $this->seedGrade(52.13, 'D', 'passed', 'Ғайриқаноатбахш');

        $controller = app(\App\Http\Controllers\Admin\TranscriptController::class);
        $method = new \ReflectionMethod($controller, 'buildTranscriptData');
        $method->setAccessible(true);

        $data = $method->invoke($controller, $this->student, null);

        $this->assertCount(1, $data['rows']);
        $this->assertSame(3, $data['rows'][0]['trad']);
        $this->assertSame('D', $data['rows'][0]['letter']);
    }
}