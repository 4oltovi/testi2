<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\CategoryScore;
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
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Фармони `assignments:merge-duplicates`:
 *  - --dry-run танҳо нақша чоп мекунад;
 *  - обудакунӣ: як сатр, кредитҳо ҷамъ (2+2+2=6), соатҳо аз рӯи навъ;
 *  - сатрҳои кӯҳна нест карда намешаванд (merged_into_id + is_active=false);
 *  - дарсҳо (category_scores / attendances) кӯчида мешаванд + lesson_type;
 *  - (сана, рақам) ҷуфт як навъро нигоҳ медорад (бархӯрд → кӯчидан);
 *  - идентӣ (дурур такрорӣ ҳеҷ чизро вайрон намекунад);
 *  - як фан = як сатр дар /student/grades.
 */
class MergeSubjectAssignmentsTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $studentUser;
    private Student $student;
    private User $secondStudentUser;
    private Student $secondStudent;
    private AcademicYear $year;
    private Group $group;
    private Semester $semester;
    private Subject $subject;

    /** @var array<string, SubjectAssignment> */
    private array $assignments = [];

    protected function setUp(): void
    {
        parent::setUp();

        $teacherRole = Role::firstOrCreate(
            ['name' => 'teacher'],
            ['display_name' => 'Омӯзгор', 'level' => 50, 'is_system' => true]
        );
        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );

        $this->teacher = User::factory()->create(['status' => 'active']);
        $this->teacher->roles()->attach($teacherRole->id);

        $this->year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $faculty = Faculty::create(['name' => 'Факултеи Тест', 'code' => 'FAC-MG', 'is_active' => true]);
        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-MG', 'is_active' => true,
        ]);
        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Ихтисоси Тест', 'code' => 'SP-MG',
            'total_credits' => 120, 'is_active' => true,
        ]);
        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);
        $this->group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи Тест', 'code' => '301', 'is_active' => true,
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
            'name' => 'Фани Обудакунӣ',
            'code' => 'SBJ-MG',
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        // 3 таъиноти якхела: лексия/амалӣӣ/лабораторӣ (2 кредит, 2 соат/ҳафта)
        foreach (['lecture', 'practice', 'lab'] as $type) {
            $this->assignments[$type] = SubjectAssignment::create([
                'subject_id' => $this->subject->id,
                'teacher_id' => $this->teacher->id,
                'group_id' => $this->group->id,
                'semester_id' => $this->semester->id,
                'lesson_type' => $type,
                'hours_per_week' => 2,
                'credits' => 2,
            ]);
        }

        $this->studentUser = User::factory()->create(['status' => 'active']);
        $this->studentUser->roles()->attach($studentRole->id);
        $this->student = $this->studentUser->student()->create([
            'group_id' => $this->group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'MG0001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);

        $this->secondStudentUser = User::factory()->create(['status' => 'active']);
        $this->secondStudentUser->roles()->attach($studentRole->id);
        $this->secondStudent = $this->secondStudentUser->student()->create([
            'group_id' => $this->group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'MG0002',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);
    }

    /**
     * Ҷойгузории баллҳои журнал барои ҳар навъ.
     * lecture ва practice ҷуфти (сана, рақам)-ро мубодила мекунанд → бархӯрд.
     */
    private function seedJournal(): void
    {
        $lessonMap = [
            'lecture' => ['date' => '2026-09-01', 'number' => 1],
            'practice' => ['date' => '2026-09-01', 'number' => 1], // бархӯрд бо lecture
            'lab' => ['date' => '2026-09-03', 'number' => 2],
        ];

        foreach ($this->assignments as $type => $assignment) {
            foreach ([$this->student, $this->secondStudent] as $index => $student) {
                CategoryScore::create([
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assignment->id,
                    'semester_id' => $this->semester->id,
                    'lesson_date' => $lessonMap[$type]['date'],
                    'lesson_number' => $lessonMap[$type]['number'],
                    'category' => 'savod',
                    'period' => 'rating1',
                    'score' => 4 + $index,
                    'max_score' => 5,
                    'graded_by' => $this->teacher->id,
                ]);

                Attendance::create([
                    'student_id' => $student->id,
                    'subject_assignment_id' => $assignment->id,
                    'lesson_date' => $lessonMap[$type]['date'],
                    'lesson_number' => $lessonMap[$type]['number'],
                    'status' => 'present',
                    'marked_by' => $this->teacher->id,
                ]);
            }
        }
    }

    public function test_dry_run_prints_plan_and_changes_nothing(): void
    {
        $this->seedJournal();

        $this->artisan('assignments:merge-duplicates', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertExitCode(0);

        // Ҳеҷ чиз тағйир ёфт нашудааст
        $this->assertSame(3, SubjectAssignment::withoutGlobalScope('not_merged')->count());
        $this->assertSame(
            0,
            SubjectAssignment::withoutGlobalScope('not_merged')->whereNotNull('merged_into_id')->count()
        );

        $canonical = $this->assignments['lecture'];
        $this->assertSame(2, (int) $canonical->fresh()->credits);
        $this->assertNull($this->assignments['practice']->fresh()->merged_into_id);
    }

    public function test_merge_moves_children_and_sums_credits(): void
    {
        $this->seedJournal();

        $canonicalId = $this->assignments['lecture']->id;

        $this->artisan('assignments:merge-duplicates')
            ->assertExitCode(0);

        // 1 сатри фаъол, 2 сатри обудашуда (маълумот нест карда нашудааст)
        $active = SubjectAssignment::withoutGlobalScope('not_merged')
            ->whereNull('merged_into_id')
            ->count();
        $this->assertSame(1, $active);

        $mergedRows = SubjectAssignment::withoutGlobalScope('not_merged')
            ->whereNotNull('merged_into_id')
            ->get();
        $this->assertSame(2, $mergedRows->count());
        foreach ($mergedRows as $row) {
            $this->assertSame($canonicalId, (int) $row->merged_into_id);
            $this->assertFalse((bool) $row->is_active);
            $this->assertNotNull($row->lesson_type); // сатр нест карда нашуд
        }

        // Кредит ҷамъ: 2+2+2 = 6; соатҳо ҷамъ ва аз рӯи навъ
        $canonical = SubjectAssignment::withoutGlobalScope('not_merged')->find($canonicalId);
        $this->assertSame(6, (int) $canonical->credits);
        $this->assertSame(6, (int) $canonical->hours_per_week);
        $this->assertSame(2, (int) $canonical->lecture_hours);
        $this->assertSame(2, (int) $canonical->practical_hours);
        $this->assertSame(2, (int) $canonical->lab_hours);

        // Ҳамаи дарсҳо ба канон кӯчида шуданд + lesson_type пур шуд
        $this->assertSame(
            6,
            CategoryScore::where('subject_assignment_id', $canonicalId)->count()
        );
        $this->assertSame(
            6,
            Attendance::where('subject_assignment_id', $canonicalId)->count()
        );

        $types = CategoryScore::where('subject_assignment_id', $canonicalId)
            ->get()
            ->map(fn ($s) => $s->lesson_type)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $this->assertSame(['lab', 'lecture', 'practice'], $types);
    }

    public function test_colliding_lesson_pair_is_renumbered_not_lost(): void
    {
        $this->seedJournal();

        $canonicalId = $this->assignments['lecture']->id;

        $this->artisan('assignments:merge-duplicates')->assertExitCode(0);

        // (2026-09-01, #1) ҳам дар lecture буд ҳам дар practice →
        // ҷуфтҳо бояд як навъ дошта бошанд: рақамҳо ҷудо шуданд,
        // ҳамаи 6 сатр боқӣ монданд (ҳеҷ балл гум нашуд).
        // Ҷуфтҳо: 09-01/1 (lecture), 09-01/2 (practice-и кӯҷида), 09-03/2 (lab)
        $pairs = CategoryScore::where('subject_assignment_id', $canonicalId)
            ->get()
            ->unique(fn ($s) => $s->lesson_date->format('Y-m-d') . '_' . $s->lesson_number)
            ->count();

        $this->assertSame(3, $pairs, 'Ҷуфтҳои (сана, рақам) бояд якхела набошанд');
        $this->assertSame(6, CategoryScore::where('subject_assignment_id', $canonicalId)->count());
    }

    public function test_merge_is_idempotent(): void
    {
        $this->seedJournal();

        $this->artisan('assignments:merge-duplicates')->assertExitCode(0);

        $canonicalId = $this->assignments['lecture']->id;
        $creditsAfterFirst = (int) SubjectAssignment::withoutGlobalScope('not_merged')
            ->find($canonicalId)
            ->credits;

        // Дуюм бор: ҳеҷ гурӯҳи такрорӣ намемонад
        $this->artisan('assignments:merge-duplicates')
            ->expectsOutputToContain('обудакунӣ лозим нест')
            ->assertExitCode(0);

        $this->assertSame(1, SubjectAssignment::withoutGlobalScope('not_merged')
            ->whereNull('merged_into_id')
            ->count());
        $this->assertSame(
            $creditsAfterFirst,
            (int) SubjectAssignment::withoutGlobalScope('not_merged')
                ->find($canonicalId)
                ->credits
        );
    }

    public function test_student_grades_page_shows_subject_once(): void
    {
        // Пеш аз merge: 3 таъинот → фан бояд ЯК сатр нишон дода шавад
        $html = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Фани Обудакунӣ'),
            'Фан бояд дар /student/grades як маротиба ояд'
        );

        // Кредит ҷамъ: 2+2+2 = 6 (на 2 ва на 18)
        $this->assertStringContainsString('>6<', $html);

        // Пас аз merge ҳам як сатр
        $this->artisan('assignments:merge-duplicates')->assertExitCode(0);

        $html = $this->actingAs($this->studentUser)
            ->get(route('student.grades.index'))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            substr_count($html, 'Фани Обудакунӣ'),
            'Пас аз merge фан бояд як сатр боқӣ монад'
        );
        $this->assertStringContainsString('>6<', $html);
    }

    public function test_merged_rows_are_hidden_from_teacher_journal_list(): void
    {
        $this->artisan('assignments:merge-duplicates')->assertExitCode(0);

        // Рӯйхати омӯзгор: танҳо як сатр (сатрҳои обудашуда пинҳон)
        $visibleCount = SubjectAssignment::where('teacher_id', $this->teacher->id)
            ->where('group_id', $this->group->id)
            ->count();
        $this->assertSame(1, $visibleCount);
    }
}
