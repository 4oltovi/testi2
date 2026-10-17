<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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
 * Навъи дарс дар журнал:
 *  - Лексия: 4 категория, «Савод» пинҳон ва ҳисоб намешавад (макс = 10);
 *  - Амалӣӣ/Лаборатория: ҳамаи 5 категория (макс = 15);
 *  - навъ бо ҳар сатр сабт мешавад ва барои саҳифаи баъдӣ ёддошт мешавад;
 *  - ивазкунии навъ ҳангоми мавҷуд будани балл ҳифз мешавад (таъйид лозим,
 *    ҳеҷ балл нест карда намешавад);
 *  - танзимот: байнишонаҳои мувофиқӣ барои ҳар навъ.
 */
class LessonTypeJournalTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $studentUser;
    private Student $student;
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

        $faculty = Faculty::create(['name' => 'Факултеи Тест', 'code' => 'FAC-LT', 'is_active' => true]);
        $department = Department::create([
            'faculty_id' => $faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-LT', 'is_active' => true,
        ]);
        $specialty = Specialty::create([
            'department_id' => $department->id, 'faculty_id' => $faculty->id,
            'name' => 'Ихтисоси Тест', 'code' => 'SP-LT',
            'total_credits' => 120, 'is_active' => true,
        ]);
        $course = Course::create(['number' => 1, 'name' => 'Курси 1']);
        $this->group = Group::create([
            'specialty_id' => $specialty->id, 'course_id' => $course->id,
            'academic_year_id' => $this->year->id,
            'name' => 'Гурӯҳи Тест', 'code' => '401', 'is_active' => true,
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
            'name' => 'Фани Навъҳо',
            'code' => 'SBJ-LT',
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

        $this->studentUser = User::factory()->create(['status' => 'active']);
        $this->studentUser->roles()->attach($studentRole->id);
        $this->student = $this->studentUser->student()->create([
            'group_id' => $this->group->id,
            'specialty_id' => $specialty->id,
            'course_id' => $course->id,
            'student_id_number' => 'LT0001',
            'enrollment_date' => '2025-09-01',
            'status' => 'active',
        ]);
    }

    private function categoryScoresUrl(array $query = []): string
    {
        return route('teacher.journal.category-scores', array_merge([$this->assignment], $query));
    }

    public function test_lecture_hides_savod_and_has_lower_max(): void
    {
        $html = $this->actingAs($this->teacher)
            ->get($this->categoryScoresUrl(['lesson_type' => 'lecture', 'date' => '2026-09-01']))
            ->assertOk()
            ->getContent();

        // «Савод» пинҳон: ҳеҷ input барои savod нест
        $this->assertStringNotContainsString('scores[' . $this->student->id . '][savod]', $html);

        // 4 категорияи дигар мавҷуданд
        foreach (['sarulibos', 'jihoz', 'ishtirok', 'intizom'] as $category) {
            $this->assertStringContainsString(
                'scores[' . $this->student->id . '][' . $category . ']',
                $html
            );
        }

        // Ҳадди аксар барои лексия = 2+2+3+3 = 10
        $this->assertStringContainsString('ҶАМЪ: 10', $html);
    }

    public function test_practical_shows_five_categories(): void
    {
        $html = $this->actingAs($this->teacher)
            ->get($this->categoryScoresUrl(['lesson_type' => 'practice', 'date' => '2026-09-01']))
            ->assertOk()
            ->getContent();

        foreach (['savod', 'sarulibos', 'jihoz', 'ishtirok', 'intizom'] as $category) {
            $this->assertStringContainsString(
                'scores[' . $this->student->id . '][' . $category . ']',
                $html
            );
        }

        $this->assertStringContainsString('ҶАМЪ: 15', $html);
    }

    public function test_lab_shows_five_categories(): void
    {
        $html = $this->actingAs($this->teacher)
            ->get($this->categoryScoresUrl(['lesson_type' => 'lab', 'date' => '2026-09-01']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('scores[' . $this->student->id . '][savod]', $html);
        $this->assertStringContainsString('ҶАМЪ: 15', $html);
    }

    public function test_selected_type_is_saved_with_lesson_and_remembered(): void
    {
        // Сабти дарси амалӣӣ бо 5 категория
        $this->actingAs($this->teacher)
            ->post(route('teacher.journal.category-scores.store', $this->assignment), [
                'date' => '2026-09-02',
                'lesson_number' => 3,
                'period' => 'rating1',
                'lesson_type' => 'practice',
                'scores' => [
                    $this->student->id => [
                        'savod' => 5,
                        'sarulibos' => 2,
                        'jihoz' => 1,
                        'ishtirok' => 3,
                        'intizom' => 3,
                    ],
                ],
            ])
            ->assertRedirect();

        // Навъ бо ҳар сатр сабт шуд
        $scores = CategoryScore::where('subject_assignment_id', $this->assignment->id)->get();
        $this->assertNotEmpty($scores);
        foreach ($scores as $score) {
            $this->assertSame('practice', $score->lesson_type);
        }

        // Саҳифаи баъдӣ бе lesson_type → аз сатрҳои дарс ёд мегирад
        $html = $this->actingAs($this->teacher)
            ->get($this->categoryScoresUrl(['date' => '2026-09-02', 'lesson_number' => 3]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('value="practice" checked', $html);
        $this->assertStringContainsString('ҶАМЪ: 15', $html);
    }

    public function test_lecture_store_ignores_non_applicable_category(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.journal.category-scores.store', $this->assignment), [
                'date' => '2026-09-05',
                'lesson_number' => 1,
                'period' => 'rating1',
                'lesson_type' => 'lecture',
                'scores' => [
                    $this->student->id => [
                        'savod' => 5, // ғайримувофиқ барои лексия → сабт намешавад
                        'sarulibos' => 2,
                        'jihoz' => 2,
                        'ishtirok' => 3,
                        'intizom' => 3,
                    ],
                ],
            ])
            ->assertRedirect();

        $categories = CategoryScore::where('subject_assignment_id', $this->assignment->id)
            ->where('lesson_date', '2026-09-05')
            ->pluck('category')
            ->all();

        $this->assertNotContains('savod', $categories);
        $this->assertCount(4, $categories);
    }

    public function test_type_change_with_scores_is_protected_and_keeps_data(): void
    {
        // 1) Дарси амалӣӣ бо «Савод»
        $this->actingAs($this->teacher)
            ->post(route('teacher.journal.category-scores.store', $this->assignment), [
                'date' => '2026-09-07',
                'lesson_number' => 1,
                'period' => 'rating1',
                'lesson_type' => 'practice',
                'scores' => [
                    $this->student->id => ['savod' => 5, 'sarulibos' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, CategoryScore::where('subject_assignment_id', $this->assignment->id)->count());

        // 2) Иваз ба лексия БЕ тасдиқ → рад мешавад, ҳеҷ чиз тағйир намеёбад
        $this->actingAs($this->teacher)
            ->from($this->categoryScoresUrl(['date' => '2026-09-07', 'lesson_number' => 1]))
            ->post(route('teacher.journal.category-scores.store', $this->assignment), [
                'date' => '2026-09-07',
                'lesson_number' => 1,
                'period' => 'rating1',
                'lesson_type' => 'lecture',
                'scores' => [
                    $this->student->id => ['sarulibos' => 2],
                ],
            ])
            ->assertSessionHasErrors('lesson_type');

        // Баллҳо ҳифз шуданд, навъ иваз НАшуд
        $this->assertSame(2, CategoryScore::where('subject_assignment_id', $this->assignment->id)->count());
        $this->assertSame(
            'practice',
            CategoryScore::where('subject_assignment_id', $this->assignment->id)
                ->where('category', 'savod')
                ->value('lesson_type')
        );

        // 3) Бо тасдиқ → навъ иваз мешавад, БАЛЛҲО ДАР ҶОИ ХУД МЕМОНАНД
        $this->actingAs($this->teacher)
            ->post(route('teacher.journal.category-scores.store', $this->assignment), [
                'date' => '2026-09-07',
                'lesson_number' => 1,
                'period' => 'rating1',
                'lesson_type' => 'lecture',
                'confirm_type_change' => '1',
                'scores' => [
                    $this->student->id => ['sarulibos' => 2],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(2, CategoryScore::where('subject_assignment_id', $this->assignment->id)->count());
        $this->assertSame(
            'lecture',
            CategoryScore::where('subject_assignment_id', $this->assignment->id)
                ->where('category', 'savod')
                ->value('lesson_type')
        );
    }
}
