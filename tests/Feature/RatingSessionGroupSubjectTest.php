<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AnswerOption;
use App\Models\Course;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\RatingAttempt;
use App\Models\RatingSession;
use App\Models\Role;
use App\Models\Semester;
use App\Models\Specialty;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\User;
use App\Services\RatingSubjectPairService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Қоидаи «гурӯҳ танҳо фанҳои худро мехӯҷад» дар рейтингҳо:
 *
 *  - сарчашма = `subject_assignments` (таъиноти журнал: фан + гурӯҳ + семестр);
 *  - гурӯҳи А → [Фан А, Фан Б], гурӯҳи Б → [Фан Б, Фан В];
 *  - «Фан Г» ҳеҷ гурӯҳ надорад.
 */
class RatingSessionGroupSubjectTest extends TestCase
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
    private Group $groupA;
    private Group $groupB;
    private Subject $subjectA;
    private Subject $subjectB;
    private Subject $subjectC;
    private Subject $subjectD;
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

        $studentRole = Role::firstOrCreate(
            ['name' => 'student'],
            ['display_name' => 'Донишҷӯ', 'level' => 10, 'is_system' => true]
        );

        $this->year = AcademicYear::create([
            'name' => '2026-2027', 'start_year' => 2026, 'end_year' => 2027,
            'start_date' => '2026-09-01', 'end_date' => '2027-08-31', 'is_active' => true,
        ]);

        $this->faculty = Faculty::create([
            'name' => 'Факултеи Тест', 'code' => 'FAC-RG', 'is_active' => true,
        ]);

        $this->department = Department::create([
            'faculty_id' => $this->faculty->id, 'name' => 'Кафедраи Тест',
            'code' => 'DEP-RG', 'is_active' => true,
        ]);

        $this->specialty = Specialty::create([
            'department_id' => $this->department->id, 'faculty_id' => $this->faculty->id,
            'name' => 'Хулосаи Тест', 'code' => 'SP-RG',
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

        $this->groupA = $this->makeGroup('Гурӯҳи А');
        $this->groupB = $this->makeGroup('Гурӯҳи Б');

        $this->subjectA = $this->makeSubject('Фан А');
        $this->subjectB = $this->makeSubject('Фан Б');
        $this->subjectC = $this->makeSubject('Фан В');
        $this->subjectD = $this->makeSubject('Фан Г');

        // Гурӯҳи А: Фан А + Фан Б
        $this->makeAssignment($this->groupA, $this->subjectA);
        $this->makeAssignment($this->groupA, $this->subjectB);
        // Гурӯҳи Б: Фан Б + Фан В
        $this->makeAssignment($this->groupB, $this->subjectB);
        $this->makeAssignment($this->groupB, $this->subjectC);
        // Фан Г — ҳеҷ гурӯҳ надорад

        $this->makeStudent($this->groupA, $studentRole);
        $this->makeStudent($this->groupB, $studentRole);
    }

    // ==================== ЁРГОҲ ====================

    private function makeGroup(string $name): Group
    {
        return Group::create([
            'specialty_id' => $this->specialty->id,
            'course_id' => $this->course->id,
            'academic_year_id' => $this->year->id,
            'name' => $name,
            'code' => 'G' . ++$this->seq,
            'is_active' => true,
        ]);
    }

    private function makeSubject(string $name): Subject
    {
        return Subject::create([
            'department_id' => $this->department->id,
            'name' => $name, 'code' => 'SBJ-' . ++$this->seq,
            'credits' => 6, 'total_hours' => 100, 'is_active' => true,
        ]);
    }

    private function makeAssignment(Group $group, Subject $subject, string $lessonType = 'lecture'): SubjectAssignment
    {
        return SubjectAssignment::create([
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherUser->id,
            'group_id' => $group->id,
            'semester_id' => $this->semester->id,
            'lesson_type' => $lessonType,
            'hours_per_week' => 2,
            'credits' => 6,
        ]);
    }

    private function makeStudent(Group $group, Role $studentRole): Student
    {
        $user = User::factory()->create([
            'first_name' => 'Донишҷӯ', 'status' => 'active',
        ]);
        $user->roles()->attach($studentRole->id);

        return $user->student()->create([
            'group_id' => $group->id,
            'specialty_id' => $this->specialty->id,
            'course_id' => $this->course->id,
            'student_id_number' => 'S' . (10000 + ++$this->seq),
            'enrollment_date' => now()->subYear()->toDateString(),
            'status' => 'active',
            'has_debts' => false,
        ]);
    }

    private function makeRatingQuestions(Subject $subject, int $count = 5): void
    {
        $bank = QuestionBank::create([
            'subject_id' => $subject->id,
            'teacher_id' => $this->teacherUser->id,
            'name' => 'Банки рейтинг — ' . $subject->name,
            'total_questions' => $count,
            'is_active' => true,
            'bank_type' => 'rating',
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $question = Question::create([
                'question_bank_id' => $bank->id,
                'subject_id' => $subject->id,
                'type' => 'single_choice',
                'question_text' => 'Саволи ' . $i,
                'difficulty_level' => 1,
                'points' => 1,
                'is_active' => true,
            ]);

            AnswerOption::create([
                'question_id' => $question->id,
                'option_text' => 'Гирифт',
                'is_correct' => true,
                'sort_order' => 1,
            ]);
            AnswerOption::create([
                'question_id' => $question->id,
                'option_text' => 'Нодуруст',
                'is_correct' => false,
                'sort_order' => 2,
            ]);
        }
    }

    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Рейтинги 1',
            'period' => 'rating1',
            'semester_id' => $this->semester->id,
            'start_at' => now()->subHour()->toDateTimeString(),
            'end_at' => now()->addDay()->toDateTimeString(),
            'duration_minutes' => 45,
            'questions_count' => 5,
            'max_attempts' => 2,
            'schedule_mode' => 'all',
            'subject_ids' => [
                $this->subjectA->id,
                $this->subjectB->id,
                $this->subjectC->id,
                $this->subjectD->id,
            ],
        ], $overrides);
    }

    private function activeSession(array $subjects = []): RatingSession
    {
        $session = RatingSession::create([
            'name' => 'Рейтинги фаъол',
            'period' => 'rating1',
            'semester_id' => $this->semester->id,
            'start_at' => now()->subHour(),
            'end_at' => now()->addDay(),
            'duration_minutes' => 45,
            'questions_count' => 5,
            'max_attempts' => 2,
            'schedule_mode' => 'all',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $session->subjects()->attach(
            collect($subjects ?: [$this->subjectA, $this->subjectB, $this->subjectC])
                ->mapWithKeys(fn ($s) => [$s->id => ['questions_count' => null]])
                ->all()
        );

        return $session->fresh();
    }

    // ==================== ТЕСТҲО ====================

    /**
     * Ҳамаи фанҳои интихобшуда, вале танҳо онҳое, ки ягон гурӯҳ онҳоро
     * дар семестр мехӯҷад, ба сессия замида мешаванд.
     */
    public function test_store_attaches_only_subjects_that_groups_actually_study(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.rating-sessions.store'), $this->storePayload());

        $session = RatingSession::latest('id')->first();
        $this->assertNotNull($session);
        $response->assertRedirect(route('admin.rating-sessions.show', $session));

        $attached = $session->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
        sort($attached);

        $expected = [$this->subjectA->id, $this->subjectB->id, $this->subjectC->id];
        sort($expected);

        $this->assertSame($expected, $attached, 'Фан бе гурӯҳ (Фан Г) бояд гузашта шавад.');
    }

    /**
     * Хулоса: шумораи ҷуфтҳо + номҳои фанҳои гузашташуда.
     */
    public function test_store_summary_reports_pair_count_and_skipped_subjects(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.rating-sessions.store'), $this->storePayload());

        $message = session('success');

        $this->assertIsString($message);
        // Ҷуфтҳо: (А→А), (Б→А), (Б→Б), (В→Б) = 4
        $this->assertStringContainsString('4 ҷуфт', $message);
        $this->assertStringContainsString('Фанҳои бе гурӯҳ', $message);
        $this->assertStringContainsString($this->subjectD->name, $message);

        $response->assertSessionHasNoErrors();
    }

    /**
     * Ҳолати «Аз рӯи гурӯҳҳо» — ҳамон қоида.
     */
    public function test_by_group_mode_respects_the_same_rule(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.rating-sessions.store'), $this->storePayload([
                'schedule_mode' => 'by_group',
                'group_windows' => [
                    $this->groupA->id => ['start_at' => '', 'end_at' => ''],
                    $this->groupB->id => ['start_at' => '', 'end_at' => ''],
                ],
            ]));

        $session = RatingSession::latest('id')->first();

        $attached = $session->subjects()->pluck('subjects.id')->map(fn ($id) => (int) $id)->all();
        sort($attached);
        $expected = [$this->subjectA->id, $this->subjectB->id, $this->subjectC->id];
        sort($expected);
        $this->assertSame($expected, $attached);

        $this->assertSame(
            [$this->groupA->id, $this->groupB->id],
            $session->groups()->pluck('groups.id')->map(fn ($id) => (int) $id)->sort()->values()->all()
        );

        $this->assertStringContainsString('4 ҷуфт', session('success'));
    }

    /**
     * Донишҷӯ танҳо фанҳои гурӯҳи худро мебинад.
     */
    public function test_student_sees_only_subjects_of_own_group(): void
    {
        $session = $this->activeSession();

        $studentA = Student::where('group_id', $this->groupA->id)->firstOrFail();
        $studentB = Student::where('group_id', $this->groupB->id)->firstOrFail();

        $htmlA = $this->actingAs($studentA->user)
            ->get(route('student.rating.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->subjectA->name, $htmlA);
        $this->assertStringContainsString($this->subjectB->name, $htmlA);
        $this->assertStringNotContainsString($this->subjectC->name, $htmlA);

        $htmlB = $this->actingAs($studentB->user)
            ->get(route('student.rating.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->subjectB->name, $htmlB);
        $this->assertStringContainsString($this->subjectC->name, $htmlB);
        $this->assertStringNotContainsString($this->subjectA->name, $htmlB);
    }

    /**
     * Ҳимояи серверӣ: фанҳи гурӯҳи дигарро донишҷӯ оғоз карда наметавонад.
     */
    public function test_start_is_forbidden_for_subject_of_another_group(): void
    {
        $session = $this->activeSession();

        $studentA = Student::where('group_id', $this->groupA->id)->firstOrFail();

        $this->actingAs($studentA->user)
            ->post(route('student.rating.start', [$session, $this->subjectC]))
            ->assertForbidden();

        $this->assertSame(0, RatingAttempt::where('rating_session_id', $session->id)->count());
    }

    /**
     * Фанҳи гурӯҳи худ савол дост — тест оғоз мешавад.
     */
    public function test_start_is_allowed_for_subject_of_own_group(): void
    {
        $this->makeRatingQuestions($this->subjectA);

        $session = $this->activeSession();

        $studentA = Student::where('group_id', $this->groupA->id)->firstOrFail();

        $this->actingAs($studentA->user)
            ->post(route('student.rating.start', [$session, $this->subjectA]))
            ->assertRedirect();

        $this->assertSame(1, RatingAttempt::where('rating_session_id', $session->id)
            ->where('subject_id', $this->subjectA->id)
            ->count());
    }

    /**
     * Агар ягон фан гурӯҳ надорад — сессия сохта намешавад.
     */
    public function test_store_fails_when_no_selected_subject_has_any_group(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.rating-sessions.store'), $this->storePayload([
                'subject_ids' => [$this->subjectD->id],
            ]))
            ->assertSessionHasErrors('subject_ids');

        $this->assertSame(0, RatingSession::count());
    }

    /**
     * Сатри обудашуда (merged_into_id) гурӯҳро аз ҷуфтҳо хона намекунад:
     * гурӯҳи он фанро дар семестр мехӯҷад ва донишҷӯи он санҷишро
     * оғоз карда метавонад.
     */
    public function test_merged_assignment_keeps_the_group_in_the_rating_pairs(): void
    {
        // Сатри канон (фаъол) + сатри якхела, ки ба он обуда шудааст
        $canonical = $this->makeAssignment($this->groupA, $this->subjectA, 'lecture');
        $merged = $this->makeAssignment($this->groupA, $this->subjectA, 'practice');
        $merged->update(['merged_into_id' => $canonical->id, 'is_active' => false]);

        $service = app(RatingSubjectPairService::class);

        $pairs = $service->pairs($this->semester->id);
        $this->assertContains(
            ['subject_id' => $this->subjectA->id, 'group_id' => $this->groupA->id],
            $pairs
        );

        $this->assertTrue(
            $service->groupStudiesSubject($this->semester->id, $this->groupA->id, $this->subjectA->id)
        );
    }

    /**
     * Ҳолати мушатшаш: аз таъиноти канон танҳо сатри обудашуда мондааст
     * (сатри канон ғайрифаъол шудааст). Гурӯҳ боз ҳам фанро мехӯҷад —
     * аз рӯи ҳадафи обуда.
     */
    public function test_group_is_kept_when_only_a_merged_assignment_remains(): void
    {
        // Фан В — дар setUp танҳо гурӯҳи Б мехӯҷадад; барои гурӯҳи А нест
        $canonical = $this->makeAssignment($this->groupA, $this->subjectC, 'lecture');
        $merged = $this->makeAssignment($this->groupA, $this->subjectC, 'practice');
        $merged->update(['merged_into_id' => $canonical->id, 'is_active' => false]);

        // Канон ғайрифаъол мешавад — танҳо сатри обудашуда бокӣ мемонад
        $canonical->update(['is_active' => false]);

        $service = app(RatingSubjectPairService::class);

        $this->assertContains(
            ['subject_id' => $this->subjectC->id, 'group_id' => $this->groupA->id],
            $service->pairs($this->semester->id)
        );

        $this->assertTrue(
            $service->groupStudiesSubject($this->semester->id, $this->groupA->id, $this->subjectC->id)
        );

        // Донишҷӯи гурӯҳ фанро мебинад ва санҷишро оғоз карда метавонад
        $session = $this->activeSession([$this->subjectC]);
        $studentA = Student::where('group_id', $this->groupA->id)->firstOrFail();

        $html = $this->actingAs($studentA->user)
            ->get(route('student.rating.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString($this->subjectC->name, $html);

        $this->makeRatingQuestions($this->subjectC);

        $this->actingAs($studentA->user)
            ->post(route('student.rating.start', [$session, $this->subjectC]))
            ->assertRedirect();

        $this->assertSame(1, RatingAttempt::where('rating_session_id', $session->id)
            ->where('subject_id', $this->subjectC->id)
            ->count());
    }

    /**
     * Лексияи муштарак: сатри гурӯҳи дигар ба сатри канон обуда шудааст →
     * гурӯҳ он фанро аз рӯи ҳадафи обуда мегирад.
     */
    public function test_merged_group_resolves_subject_through_merge_target(): void
    {
        // Канони гурӯҳи А — фан А
        $canonical = $this->makeAssignment($this->groupA, $this->subjectA, 'lecture');

        // Сатри гурӯҳи Б обуда ба канони гурӯҳи А мешавад
        $shared = $this->makeAssignment($this->groupB, $this->subjectC, 'lecture');
        $shared->update(['merged_into_id' => $canonical->id, 'is_active' => false]);

        $service = app(RatingSubjectPairService::class);

        // Гурӯҳи Б фанро аз рӯи ҳадафи обуда (Фан А) мегирад
        $this->assertContains(
            $this->subjectA->id,
            $service->subjectIdsForGroup($this->semester->id, $this->groupB->id)
        );

        $this->assertTrue(
            $service->groupStudiesSubject($this->semester->id, $this->groupB->id, $this->subjectA->id)
        );

        $this->assertFalse(
            $service->groupStudiesSubject($this->semester->id, $this->groupA->id, $this->subjectD->id)
        );
    }

    /**
     * Матритсаи натиҷаҳо сатри холи корӣ надорад:
     * ҳар донишҷӯ танҳо фанҳои гурӯҳи худ.
     */
    public function test_results_matrix_has_no_rows_for_subjects_of_other_groups(): void
    {
        $session = $this->activeSession();

        $studentA = Student::where('group_id', $this->groupA->id)->firstOrFail();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.rating-sessions.show', $session))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->subjectA->name, $html);
        $this->assertStringContainsString($this->subjectB->name, $html);

        // Сатри «Донишҷӯи А + Фан В» набояд
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/s', $html, $rows);
        foreach ($rows[1] as $row) {
            if (
                str_contains($row, $studentA->user->full_name)
                && str_contains($row, $this->subjectC->name)
            ) {
                $this->fail('Донишҷӯи гурӯҳи А сатре бо фани гурӯҳи Б (Фан В) дошта бошад.');
            }
        }
    }

    /**
     * Саҳифаи сохтан: шумораи гурӯҳҳо дар ҳар фан ва хомӯш кардани
     * фанҳои бе гурӯҳ.
     */
    public function test_create_page_shows_group_counts_and_disables_subjects_without_groups(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.rating-sessions.create'))
            ->assertOk()
            ->getContent();

        // Фан Б дар ду гурӯҳ аст
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($this->subjectB->name, '/') . '<\/label>\s*<small[^>]*>\s*2 гурӯҳ/s',
            $html
        );

        // Фан А дар як гурӯҳ аст
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($this->subjectA->name, '/') . '<\/label>\s*<small[^>]*>\s*1 гурӯҳ/s',
            $html
        );

        // Фан Г — «Гурӯҳ нест» ва чекбокси хомӯш
        $this->assertMatchesRegularExpression(
            '/' . preg_quote($this->subjectD->name, '/') . '<\/label>\s*<small[^>]*>\s*Гурӯҳ нест/s',
            $html
        );

        preg_match('/<input[^>]*id="subj_' . $this->subjectD->id . '"[^>]*>/s', $html, $m);
        $this->assertNotEmpty($m, 'Чекбокси фан бе гурӯҳ бояд дар HTML ҳаст.');
        $this->assertStringContainsString('disabled', $m[0]);

        preg_match('/<input[^>]*id="subj_' . $this->subjectA->id . '"[^>]*>/s', $html, $m);
        $this->assertNotEmpty($m);
        $this->assertStringNotContainsString('disabled', $m[0]);
    }

    /**
     * AJAX: шумораи гурӯҳҳо бо иваз кардани семестр нав мешавад.
     */
    public function test_subjects_by_semester_returns_group_counts(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.rating-sessions.subjects-by-semester'), [
                'semester_id' => $this->semester->id,
            ])
            ->assertOk();

        $counts = collect($response->json('subjects'))
            ->mapWithKeys(fn ($s) => [(int) $s['id'] => (int) $s['groups_count']])
            ->all();

        $this->assertSame(1, $counts[$this->subjectA->id]);
        $this->assertSame(2, $counts[$this->subjectB->id]);
        $this->assertSame(1, $counts[$this->subjectC->id]);
        $this->assertSame(0, $counts[$this->subjectD->id]);
    }

    /**
     * Сессияи қадим (пеш аз ислоҳ) бояд кор кардаи давом дорад:
     * ҳамон сабтҳо, танҳо ҳуқуқи фанҳо аз рӯи гурӯҳ ҳазф мешавад.
     */
    public function test_existing_session_keeps_working_with_group_rule(): void
    {
        $session = $this->activeSession();

        $this->assertDatabaseHas('rating_sessions', ['id' => $session->id, 'status' => 'active']);

        $studentB = Student::where('group_id', $this->groupB->id)->firstOrFail();

        $html = $this->actingAs($studentB->user)
            ->get(route('student.rating.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString($this->subjectC->name, $html);
        $this->assertStringNotContainsString($this->subjectA->name, $html);
    }
}
