<?php

namespace Tests\Feature;

use App\Models\AcademicDebt;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\RetakeExam;
use App\Models\RetakeExamStudent;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Сутуни «Имтиҳони такрорӣ» дар саҳифаи «Баҳоҳои ман»
 * (/student/grades) бояд беҳтарин (калонтарин) натиҷаи
 * имтиҳони такрориро нишон диҳад; агар имтиҳони такрорӣ
 * набуда ё ҳанӯз нагузашта бошад — «—».
 */
class StudentGradesRetakeColumnTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private Student $student;
    private User $teacher;
    private AcademicYear $year;
    private Group $group;
    private Semester $semester;
    private Subject $subject;
    private SubjectAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );
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

        $faculty = Faculty::create(['name' => 'Факултеи Тест', 'code' => 'FAC-RT', 'is_active' => true]);

        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-RT', 'is_active' => true,
        ]);

        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-RT',
            'total_credits' => 120, 'is_active' => true,
        ]);

        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);

        $this->group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи Тест', 'code' => '101', 'is_active' => true,
        ]);

        $this->semester = Semester::create([
            'academic_year_id' => $this->year->id,
            'number' => 1,
            'name' => 'Семестри 1',
            'start_date' => '2026-09-01',
            'end_date' => '2027-01-20',
            'is_current' => true,
            'status' => 'active',
        ]);

        $this->subject = Subject::create([
            'department_id' => $department->id,
            'name' => 'Фани Тест',
            'code' => 'SBJ-RT',
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        $this->assignment = SubjectAssignment::create([
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'group_id' => $this->group->id,
            'semester_id' => $this->semester->id,
            'lesson_type' => 'lecture',
            'hours_per_week' => 2,
            'credits' => 6,
        ]);

        $this->studentUser = User::factory()->create([
            'first_name' => 'Али', 'last_name' => 'Каримов', 'status' => 'active',
        ]);
        $this->studentUser->roles()->attach($studentRole->id);

        $this->student = $this->studentUser->student()->create([
            'group_id' => $this->group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'S90001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $this->assignment->id,
            'subject_id' => $this->subject->id,
            'semester_id' => $this->semester->id,
            'rating1_score' => 60,
            'rating2_score' => 60,
            'total_score' => 60.0,
            'letter_grade' => 'C-',
            'grade_point' => 1.67,
            'traditional_grade' => '3',
            'status' => 'passed',
            'is_finalized' => true,
            'credits_earned' => 6,
        ]);
    }

    // ==================== ЁРГОҲ ====================

    private function makeDebt(?Subject $subject = null, ?Semester $semester = null): AcademicDebt
    {
        $subject ??= $this->subject;
        $semester ??= $this->semester;

        $grade = $this->student->semesterGrades()
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->first();

        return AcademicDebt::create([
            'student_id' => $this->student->id,
            'semester_grade_id' => $grade->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'reason' => 'exam_failed',
            'debt_date' => now()->toDateString(),
            'created_by' => $this->teacher->id,
            'status' => 'active',
        ]);
    }

    private function makeRetakeExam(?Subject $subject = null, ?Semester $semester = null): RetakeExam
    {
        return RetakeExam::create([
            'subject_id' => ($subject ?? $this->subject)->id,
            'semester_id' => ($semester ?? $this->semester)->id,
            'teacher_id' => $this->teacher->id,
            'title' => 'Имтиҳони такрорӣ',
            'created_by' => $this->teacher->id,
            'exam_date' => now()->addDays(3)->toDateString(),
            'status' => 'scheduled',
        ]);
    }

    private function makeRetakeStudent(
        RetakeExam $retakeExam,
        AcademicDebt $debt,
        ?float $score,
        string $status = 'passed'
    ): RetakeExamStudent {
        return RetakeExamStudent::create([
            'retake_exam_id' => $retakeExam->id,
            'student_id' => $this->student->id,
            'academic_debt_id' => $debt->id,
            'attempt_number' => 1,
            'score' => $score,
            'letter_grade' => $score !== null ? 'C' : null,
            'status' => $status,
            'examiner_id' => $this->teacher->id,
            'examined_at' => $score !== null ? now() : null,
        ]);
    }

    /**
     * Ҳуҷҷатҳои сатри фани номдодашударо бармегардонад.
     * Индекси 0 — кредит, 1 — R1, 2 — R2, 3 — Имтиҳон,
     * 4 — Имтиҳони такрорӣ (дар саҳифаи умумии солона),
     * баъд — Ниҳоӣ, Баҳо, Ҳолат.
     */
    private function rowCells(string $html, string $subjectName): array
    {
        if (!preg_match(
            '/<tr>\s*<td>' . preg_quote($subjectName, '/') . '<\/td>(.*?)<\/tr>/s',
            $html,
            $m
        )) {
            return [];
        }

        preg_match_all('/<td class="text-center">(.*?)<\/td>/s', $m[1], $cells);

        return array_map('trim', $cells[1]);
    }

    // ==================== ТЕСТҲО ====================

    /** Баҳои имтиҳони такрорӣ дар сутуни махсус нишон дода мешавад. */
    public function test_index_shows_retake_score_when_student_has_retake_result(): void
    {
        $debt = $this->makeDebt();
        $retakeExam = $this->makeRetakeExam();
        $this->makeRetakeStudent($retakeExam, $debt, 75.0);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'));

        $response->assertOk()
            ->assertSee('Имтиҳони такрорӣ');

        $cells = $this->rowCells($response->getContent(), 'Фани Тест');

        $this->assertNotEmpty($cells, 'Сатри фан ёфт нашуд.');
        $this->assertSame('75', $cells[4], 'Сутуни «Имтиҳони такрорӣ» бояд 75 нишон диҳад.');
    }

    /** Имтиҳони такрорӣ набуд → «—». */
    public function test_index_shows_dash_when_no_retake_exam_exists(): void
    {
        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'));

        $response->assertOk()->assertSee('Имтиҳони такрорӣ');

        $cells = $this->rowCells($response->getContent(), 'Фани Тест');

        $this->assertNotEmpty($cells);
        // Ни имтиҳони асосӣ, ни такрорӣ — ҳарду «—»
        $this->assertSame('—', $cells[3]);
        $this->assertSame('—', $cells[4]);
    }

    /** Имтиҳони такрорӣ таъин шудааст, аммо ҳанӯз нагузаштааст → «—». */
    public function test_index_shows_dash_when_retake_result_is_not_yet_recorded(): void
    {
        $debt = $this->makeDebt();
        $retakeExam = $this->makeRetakeExam();
        $this->makeRetakeStudent($retakeExam, $debt, null, 'pending');

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'));

        $response->assertOk();

        $cells = $this->rowCells($response->getContent(), 'Фани Тест');

        $this->assertNotEmpty($cells);
        $this->assertSame('—', $cells[4], 'Натиҷаи ҳанӯз гирифта нашуда бояд «—» бошад.');
    }

    /** Ҳангоми чандин кӯшиш — беҳтарин (калонтарин) натиҷа нишон дода мешавад. */
    public function test_retake_score_is_the_max_across_attempts(): void
    {
        $retakeExam = $this->makeRetakeExam();

        $debt1 = $this->makeDebt();
        $this->makeRetakeStudent($retakeExam, $debt1, 40.0, 'failed');

        $debt2 = $this->makeDebt();
        $this->makeRetakeStudent($retakeExam, $debt2, 80.0, 'passed');

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'));

        $response->assertOk();

        $cells = $this->rowCells($response->getContent(), 'Фани Тест');

        $this->assertNotEmpty($cells);
        $this->assertSame('80', $cells[4], 'Беҳтарин натиҷа (80) бояд нишон дода шавад.');
    }

    /** Саҳифаи ягонаи семестр ҳамон сутунро дорад. */
    public function test_semester_page_shows_retake_score(): void
    {
        $debt = $this->makeDebt();
        $retakeExam = $this->makeRetakeExam();
        $this->makeRetakeStudent($retakeExam, $debt, 75.0);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.semester', $this->semester));

        $response->assertOk()->assertSee('Имтиҳони такрорӣ');

        $cells = $this->rowCells($response->getContent(), 'Фани Тест');

        $this->assertNotEmpty($cells);
        $this->assertSame('75', $cells[3], 'Саҳифаи семестр ҳам баҳои такрориро нишон диҳад.');
    }

    /** Баҳои такрорӣ танҳо барои фан ва семестри худ — на барои дигар фанҳо. */
    public function test_retake_score_only_matches_same_subject_and_semester(): void
    {
        // Фани дуюм дар ҳамон гурӯҳ ва семестр
        $subject2 = Subject::create([
            'department_id' => $this->subject->department_id,
            'name' => 'Фани Дуввум',
            'code' => 'SBJ-RT-2',
            'credits' => 4, 'total_hours' => 80, 'is_active' => true,
        ]);

        $assignment2 = SubjectAssignment::create([
            'subject_id' => $subject2->id,
            'teacher_id' => $this->teacher->id,
            'group_id' => $this->group->id,
            'semester_id' => $this->semester->id,
            'lesson_type' => 'lecture',
            'hours_per_week' => 2,
            'credits' => 4,
        ]);

        $this->student->semesterGrades()->create([
            'subject_assignment_id' => $assignment2->id,
            'subject_id' => $subject2->id,
            'semester_id' => $this->semester->id,
            'rating1_score' => 50,
            'rating2_score' => 50,
            'total_score' => 50.0,
            'letter_grade' => 'D',
            'grade_point' => 1.0,
            'traditional_grade' => '2',
            'status' => 'retake',
            'is_finalized' => true,
            'credits_earned' => 0,
        ]);

        // Имтиҳони такрорӣ танҳо барои фани дуюм
        $debt2 = $this->makeDebt($subject2);
        $retakeExam2 = $this->makeRetakeExam($subject2);
        $this->makeRetakeStudent($retakeExam2, $debt2, 90.0);

        $response = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'));

        $response->assertOk();

        $content = $response->getContent();

        $firstRow = $this->rowCells($content, 'Фани Тест');
        $secondRow = $this->rowCells($content, 'Фани Дуввум');

        $this->assertNotEmpty($firstRow);
        $this->assertNotEmpty($secondRow);

        $this->assertSame('—', $firstRow[4], 'Фани аввал имтиҳони такрорӣ надорад.');
        $this->assertSame('90', $secondRow[4], 'Фани дуюм баҳои такрорӣ 90 дорад.');
    }
}
