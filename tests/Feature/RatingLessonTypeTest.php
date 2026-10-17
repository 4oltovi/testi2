<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\CategoryScore;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\GradeCategorySetting;
use App\Models\Group;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\GradeCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Формулаи рейтинг бо навъи дарс:
 *
 *   журнал = min( Σ_дарсҳо ( earned_L / max_L ) × refMax , 60 )
 *
 *  - маълумоти кӯҳнаи якнавъ (ҳамаи категорияҳо мувофиқ) = хом-ҷамъи
 *    пешин ⇒ натиҷа 100% якхела;
 *  - лексияи пурра (10/10) = амалӣии пурра (15/15) = 15 балл;
 *  - лексияи қисман (5/10) = 7.5 балл.
 */
class RatingLessonTypeTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $studentUser;
    private int $studentId;
    private AcademicYear $year;
    private Group $group;
    private Semester $semester;
    private Subject $subject;
    private SubjectAssignment $assignment;

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

        $faculty = Faculty::create(['name' => 'Факултеи Тест', 'code' => 'FAC-RL', 'is_active' => true]);
        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-RL', 'is_active' => true,
        ]);
        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Ихтисоси Тест', 'code' => 'SP-RL',
            'total_credits' => 120, 'is_active' => true,
        ]);
        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);
        $this->group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи Тест', 'code' => '501', 'is_active' => true,
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
            'name' => 'Фани Рейтинг',
            'code' => 'SBJ-RL',
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);

        $this->assignment = SubjectAssignment::create([
            'subject_id' => $this->subject->id,
            'teacher_id' => $this->teacher->id,
            'group_id' => $this->group->id,
            'semester_id' => $this->semester->id,
            'lesson_type' => 'practice',
            'hours_per_week' => 2,
            'credits' => 6,
        ]);

        $studentUser = User::factory()->create(['status' => 'active']);
        $studentUser->roles()->attach($studentRole->id);
        $student = $studentUser->student()->create([
            'group_id' => $this->group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'RL0001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);
        $this->studentId = $student->id;

        // Танзимоти пешфарз (Савод барои лексия = off барои сатрҳои нав)
        GradeCategorySetting::getOrCreateDefaults($this->assignment->id);
    }

    /**
     * Ҷойгузории як дарс: категория => [score, max].
     *
     * @param array<string, array{0: float, 1: float}> $categories
     */
    private function seedLesson(string $date, int $number, ?string $type, array $categories): void
    {
        foreach ($categories as $category => [$score, $max]) {
            CategoryScore::create([
                'student_id' => $this->studentId,
                'subject_assignment_id' => $this->assignment->id,
                'semester_id' => $this->semester->id,
                'lesson_date' => $date,
                'lesson_number' => $number,
                'lesson_type' => $type,
                'category' => $category,
                'period' => 'rating1',
                'score' => $score,
                'max_score' => $max,
                'graded_by' => $this->teacher->id,
            ]);
        }
    }

    private function rating1(): float
    {
        return app(GradeCalculator::class)->calculateRating1(
            $this->studentId,
            $this->assignment->id,
            $this->semester->id
        );
    }

    /**
     * Якнавъ (маълумоти кӯҳна, навъ = null): натиҷа = хом-ҷамъи пешин.
     * min( Σ score , 60 ) — ҲАМОН ҳисоби кӯҳна.
     */
    public function test_single_type_result_equals_legacy_raw_sum(): void
    {
        // 2 дарс, ҳамаи 5 категория, навъ холӣ (= ҳама мувофиқ)
        $this->seedLesson('2026-09-01', 1, null, [
            'savod' => [5, 5],
            'sarulibos' => [2, 2],
            'jihoz' => [1, 2],
            'ishtirok' => [3, 3],
            'intizom' => [2, 3],
        ]);
        $this->seedLesson('2026-09-03', 2, null, [
            'savod' => [4, 5],
            'sarulibos' => [1, 2],
            'jihoz' => [2, 2],
            'ishtirok' => [2, 3],
            'intizom' => [3, 3],
        ]);

        // Ҳисоби кӯҳна: хом-ҷамъ = 13 + 12 = 25
        $legacyRawSum = 25.0;

        $this->assertSame($legacyRawSum, $this->rating1());
    }

    /**
     * Лексияи пурра (10/10) = амалӣии пурра (15/15) → навъҳо баробаранд.
     */
    public function test_full_lecture_lesson_counts_same_as_full_practical(): void
    {
        // Лексия: 4 категорияи мувофиқ пурра = 10/10
        $this->seedLesson('2026-09-01', 1, 'lecture', [
            'sarulibos' => [2, 2],
            'jihoz' => [2, 2],
            'ishtirok' => [3, 3],
            'intizom' => [3, 3],
        ]);

        $lectureOnly = $this->rating1();
        // (10/10) × refMax(15) = 15
        $this->assertSame(15.0, $lectureOnly);

        // Амалӣӣ: 5 категорияи пурра = 15/15 → ҳамон 15 балл
        $this->seedLesson('2026-09-08', 1, 'practice', [
            'savod' => [5, 5],
            'sarulibos' => [2, 2],
            'jihoz' => [2, 2],
            'ishtirok' => [3, 3],
            'intizom' => [3, 3],
        ]);

        // Ҷамъ: 15 + 15 = 30
        $this->assertSame(30.0, $this->rating1());
    }

    /**
     * Лексияи қисман: 5/10 → (5/10) × 15 = 7.5
     */
    public function test_partial_lecture_scales_to_reference_max(): void
    {
        $this->seedLesson('2026-09-02', 1, 'lecture', [
            'sarulibos' => [1, 2],
            'jihoz' => [1, 2],
            'ishtirok' => [2, 3],
            'intizom' => [1, 3],
        ]); // 5 аз 10

        $this->assertSame(7.5, $this->rating1());
    }

    /**
     * Ҳадди журнал = 60 (танзимот), новобаста ба шумораи дарсҳо.
     */
    public function test_journal_part_is_capped_at_60(): void
    {
        // 5 дарси амалӣии пурра = 5 × 15 = 75 → 60
        foreach (range(1, 5) as $number) {
            $this->seedLesson('2026-09-1' . $number, 1, 'practice', [
                'savod' => [5, 5],
                'sarulibos' => [2, 2],
                'jihoz' => [2, 2],
                'ishtirok' => [3, 3],
                'intizom' => [3, 3],
            ]);
        }

        $this->assertSame(60.0, $this->rating1());
    }

    /**
     * Омехта: лексия + амалӣӣ + лабораторӣ = ҷамъи нормализатсияшуда.
     */
    public function test_mixed_lesson_types_are_summed_normalized(): void
    {
        // Лексия 8/10 → (8/10)×15 = 12
        $this->seedLesson('2026-09-01', 1, 'lecture', [
            'sarulibos' => [2, 2],
            'jihoz' => [1, 2],
            'ishtirok' => [3, 3],
            'intizom' => [2, 3],
        ]); // 8

        // Амалӣӣ 9/15 → (9/15)×15 = 9
        $this->seedLesson('2026-09-02', 1, 'practice', [
            'savod' => [3, 5],
            'sarulibos' => [2, 2],
            'jihoz' => [1, 2],
            'ishtirok' => [2, 3],
            'intizom' => [1, 3],
        ]); // 9

        // Лаборатория 10/15 → (10/15)×15 = 10
        $this->seedLesson('2026-09-03', 1, 'lab', [
            'savod' => [4, 5],
            'sarulibos' => [1, 2],
            'jihoz' => [1, 2],
            'ishtirok' => [2, 3],
            'intizom' => [2, 3],
        ]); // 10

        // 12 + 9 + 10 = 31
        $this->assertSame(31.0, $this->rating1());
    }
}
