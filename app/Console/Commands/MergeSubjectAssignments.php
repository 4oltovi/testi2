<?php

namespace App\Console\Commands;

use App\Enums\GradeScale;
use App\Models\Semester;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Services\DebtDetector;
use App\Services\GradeCalculator;
use App\Services\GpaCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Обудакунии таъинотҳои такрорӣ:
 * як фан + гурӯҳ + омӯзгор + семестр = як сатр.
 *
 * Қоидаҳо:
 *  - транзаксияи ягона барои ҳар гурӯҳ;
 *  - идентӣ (таъинотҳои обуданашуда танҳо кор карда мешаванд);
 *  - --dry-run: танҳо нақша чоп мешавад, маълумот тағйир намеёбад;
 *  - credits ҷамъ мешавад (2+2+2 = 6), соатҳои ҳар навъ нигоҳ дошта мешаванд;
 *  - сатрҳои кӯҳна НЕСТ карда намешаванд: merged_into_id + is_active=false;
 *  - (сана, рақами дарс) ҷуфт як навъ мегирад: дар ҳолати бархӯрд дарси
 *    воридшаванда ба озодтарин рақам кӯча мешавад;
 *  - semester_grades: баллҳо ҷамъ (max) + ҳисоби ниҳоӣ аз рӯи мантиқи мавҷуда.
 */
class MergeSubjectAssignments extends Command
{
    protected $signature = 'assignments:merge-duplicates
        {--dry-run : Танҳо нақшаи обудакуниро чоп кун, маълумотро тағйир надеҳ}';

    protected $description = 'Муттаҳидсозии таъинотҳои якхелаи фан+гурӯҳ+омӯзгор+семестр ба як сатр';

    private int $movedScoreRows = 0;

    private int $movedAttendanceRows = 0;

    private int $renumberedLessons = 0;

    private int $mergedGradeRows = 0;

    private int $conflicts = 0;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $groups = $this->findDuplicateGroups();

        if ($groups->isEmpty()) {
            $this->info('Таъинотҳои такрорӣ ёфт нашуд — обудакунӣ лозим нест.');

            if (!$dryRun) {
                $this->backfillLessonTypes();
            }

            return self::SUCCESS;
        }

        $this->info(sprintf('Гурӯҳҳои такрорӣ: %d', $groups->count()));

        foreach ($groups as $group) {
            $rows = DB::table('subject_assignments')
                ->whereNull('merged_into_id')
                ->where('subject_id', $group->subject_id)
                ->where('teacher_id', $group->teacher_id)
                ->where('group_id', $group->group_id)
                ->where('semester_id', $group->semester_id)
                ->orderBy('id')
                ->get();

            if ($rows->count() < 2) {
                continue;
            }

            $canonical = $rows->first();
            $sources = $rows->slice(1)->values();

            $this->printGroup($canonical, $sources, $dryRun);

            if ($dryRun) {
                continue;
            }

            DB::transaction(function () use ($canonical, $sources) {
                $this->mergeGroup($canonical, $sources);
            });
        }

        if ($dryRun) {
            $this->info('DRY-RUN: ягон маълумот тағйир ёфт нашуд.');
            $this->line('Барои иҷро: php artisan assignments:merge-duplicates');

            return self::SUCCESS;
        }

        $this->backfillLessonTypes();

        $this->info(sprintf(
            'Анҷом шуд. Кӯчида шуд: %d сатри баҳо, %d сатри давомот, %d дарс барқароб карда шуд, %d сатри semester_grades обуда шуд, зиддиятҳо: %d.',
            $this->movedScoreRows,
            $this->movedAttendanceRows,
            $this->renumberedLessons,
            $this->mergedGradeRows,
            $this->conflicts
        ));

        return self::SUCCESS;
    }

    /**
     * Гурӯҳҳои такрорӣ: як subject+teacher+group+semester беш аз як сатр.
     */
    private function findDuplicateGroups()
    {
        return DB::table('subject_assignments')
            ->whereNull('merged_into_id')
            ->groupBy('subject_id', 'teacher_id', 'group_id', 'semester_id')
            ->havingRaw('COUNT(*) > 1')
            ->get(['subject_id', 'teacher_id', 'group_id', 'semester_id']);
    }

    private function printGroup(object $canonical, $sources, bool $dryRun): void
    {
        $prefix = $dryRun ? '[DRY-RUN] ' : '';

        $this->line(sprintf(
            '%sФан#%d · Гурӯҳ#%d · Омӯзгор#%d · Семестр#%d → канон: #%d (навъ: %s)',
            $prefix,
            $canonical->subject_id,
            $canonical->group_id,
            $canonical->teacher_id,
            $canonical->semester_id,
            $canonical->id,
            $canonical->lesson_type ?? '-'
        ));

        foreach ($sources as $source) {
            $this->line(sprintf(
                '    ← обуда: #%d (навъ: %s, кредит: %s, соат/ҳафта: %d)',
                $source->id,
                $source->lesson_type ?? '-',
                $source->credits ?? 'null',
                $source->hours_per_week ?? 0
            ));
        }
    }

    /**
     * Обудакунии як гурӯҳ (дар транзаксия).
     */
    private function mergeGroup(object $canonical, $sources): void
    {
        // 1) Дарсҳои ҳар сатр аз нав пур мешаванд (lesson_type аз таъинот)
        $backfillMap = [];
        $backfillMap[$canonical->id] = $canonical->lesson_type;
        foreach ($sources as $source) {
            $backfillMap[$source->id] = $source->lesson_type;
        }
        $this->backfillForAssignments($backfillMap);

        foreach ($sources as $source) {
            // 2) Кӯчидани дарсҳои журнал (бо ҳалли бархӯрди рақам)
            $this->moveJournalRecords($source, $canonical);

            // 3) Фарзандҳои оддӣ: current_grades, exams
            DB::table('current_grades')
                ->where('subject_assignment_id', $source->id)
                ->update(['subject_assignment_id' => $canonical->id]);

            $examCount = DB::table('exams')
                ->where('subject_assignment_id', $source->id)
                ->update(['subject_assignment_id' => $canonical->id]);

            if ($examCount > 0) {
                $this->line("    имтиҳонҳо кӯчида шуд: {$examCount}");
            }

            // 4-6) Танзимот, ведомост, ҷадвал — бо ҳалли зиддиятҳо
            $this->moveCategorySettings($source, $canonical);
            $this->moveVedomost($source, $canonical);
            $this->moveSchedules($source, $canonical);

            // 7) semester_grades: обудакунӣ + ҳифзи баллҳо
            $this->mergeSemesterGrades($source, $canonical);
        }

        // 8) Кредитҳо ҷамъ, соатҳо аз рӯи навъ
        $this->mergeCreditsAndHours($canonical, $sources);

        // 9) Сатрҳои кӯҳна: обудашуда (на несткунӣ!)
        foreach ($sources as $source) {
            DB::table('subject_assignments')
                ->where('id', $source->id)
                ->update([
                    'merged_into_id' => $canonical->id,
                    'is_active' => false,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Кӯчидани сатрҳои (сана, рақами дарс): агар ҷуфт дар канон аллакай
     * ишғолшуда бошад, дарси воридшаванда ба озодтарин рақам кӯча мешавад →
     * ҷуфт як навъро нигоҳ медорад.
     */
    private function moveJournalRecords(object $source, object $canonical): void
    {
        $lessonKeys = DB::table('category_scores')
            ->where('subject_assignment_id', $source->id)
            ->get(['lesson_date', 'lesson_number'])
            ->merge(
                DB::table('attendances')
                    ->where('subject_assignment_id', $source->id)
                    ->get(['lesson_date', 'lesson_number'])
            )
            ->unique(fn ($r) => $r->lesson_date . '_' . $r->lesson_number)
            ->sortBy([['lesson_date', 'asc'], ['lesson_number', 'asc']])
            ->values();

        if ($lessonKeys->isEmpty()) {
            return;
        }

        $numberMap = $this->resolveNumberMap($canonical->id, $lessonKeys);

        foreach ($lessonKeys as $key) {
            $oldNumber = (int) $key->lesson_number;
            $newNumber = $numberMap[$key->lesson_date][$oldNumber] ?? $oldNumber;

            if ($newNumber !== $oldNumber) {
                $this->renumberedLessons++;
            }

            $this->movedScoreRows += DB::table('category_scores')
                ->where('subject_assignment_id', $source->id)
                ->where('lesson_date', $key->lesson_date)
                ->where('lesson_number', $oldNumber)
                ->update([
                    'subject_assignment_id' => $canonical->id,
                    'lesson_number' => $newNumber,
                    'updated_at' => now(),
                ]);

            $this->movedAttendanceRows += DB::table('attendances')
                ->where('subject_assignment_id', $source->id)
                ->where('lesson_date', $key->lesson_date)
                ->where('lesson_number', $oldNumber)
                ->update([
                    'subject_assignment_id' => $canonical->id,
                    'lesson_number' => $newNumber,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Қадам 1: ҷуфтҳои бархӯрдкунанда ҷудо мешаванд; қадам 2: ғайри-
     * бархӯрдҳо дар ҷои худ мемонанд, бархӯрдкунандаҳо ба озодтарин рақам.
     *
     * @return array<string, array<int, int>> [lesson_date => [old_number => new_number]]
     */
    private function resolveNumberMap(int $canonicalId, $lessonKeys): array
    {
        $map = [];
        $dates = $lessonKeys->pluck('lesson_date')->unique()->all();

        $canonicalUsed = [];
        $rows = DB::table('category_scores')
            ->where('subject_assignment_id', $canonicalId)
            ->whereIn('lesson_date', $dates)
            ->get(['lesson_date', 'lesson_number'])
            ->merge(
                DB::table('attendances')
                    ->where('subject_assignment_id', $canonicalId)
                    ->whereIn('lesson_date', $dates)
                    ->get(['lesson_date', 'lesson_number'])
            );

        foreach ($rows as $row) {
            $canonicalUsed[$row->lesson_date][(int) $row->lesson_number] = true;
        }

        $reserved = $canonicalUsed;
        $collisions = [];

        foreach ($lessonKeys as $key) {
            $date = $key->lesson_date;
            $number = (int) $key->lesson_number;

            if (isset($reserved[$date][$number])) {
                $collisions[] = [$date, $number];
            } else {
                $reserved[$date][$number] = true;
                $map[$date][$number] = $number;
            }
        }

        foreach ($collisions as [$date, $number]) {
            $candidate = 1;
            while (isset($reserved[$date][$candidate]) && $candidate <= 254) {
                $candidate++;
            }
            $reserved[$date][$candidate] = true;
            $map[$date][$number] = $candidate;

            $this->conflicts++;
        }

        return $map;
    }

    private function moveCategorySettings(object $source, object $canonical): void
    {
        $canonicalCategories = DB::table('grade_category_settings')
            ->where('subject_assignment_id', $canonical->id)
            ->pluck('category')
            ->all();

        $sourceSettings = DB::table('grade_category_settings')
            ->where('subject_assignment_id', $source->id)
            ->get();

        foreach ($sourceSettings as $setting) {
            if (in_array($setting->category, $canonicalCategories, true)) {
                // Дар канон аллакай мавҷуд — танзимоти канон афзалият дорад
                $this->conflicts++;
                continue;
            }

            DB::table('grade_category_settings')
                ->where('id', $setting->id)
                ->update([
                    'subject_assignment_id' => $canonical->id,
                    'updated_at' => now(),
                ]);
        }
    }

    private function moveVedomost(object $source, object $canonical): void
    {
        $hasCanonical = DB::table('vedomosts')
            ->where('subject_assignment_id', $canonical->id)
            ->where('semester_id', $canonical->semester_id)
            ->exists();

        $sourceVedomosts = DB::table('vedomosts')
            ->where('subject_assignment_id', $source->id)
            ->get();

        foreach ($sourceVedomosts as $vedomost) {
            if ($hasCanonical) {
                $this->conflicts++;
                $this->line("    зиддият: ведомости #{$vedomost->id} дар канон мавҷуд аст — гузошта шуд");
                continue;
            }

            DB::table('vedomosts')
                ->where('id', $vedomost->id)
                ->update([
                    'subject_assignment_id' => $canonical->id,
                    'updated_at' => now(),
                ]);
        }
    }

    private function moveSchedules(object $source, object $canonical): void
    {
        $schedules = DB::table('schedules')
            ->where('subject_assignment_id', $source->id)
            ->get();

        foreach ($schedules as $schedule) {
            // unique(classroom, semester, day, number, week_type): санҷиш пеш аз кӯчонидан
            $conflict = false;
            if ($schedule->classroom_id !== null) {
                $conflict = DB::table('schedules')
                    ->where('classroom_id', $schedule->classroom_id)
                    ->where('semester_id', $canonical->semester_id)
                    ->where('day_of_week', $schedule->day_of_week)
                    ->where('lesson_number', $schedule->lesson_number)
                    ->where('week_type', $schedule->week_type)
                    ->where('id', '!=', $schedule->id)
                    ->exists();
            }

            if ($conflict) {
                $this->conflicts++;
                $this->line("    зиддият: ҷадвали #{$schedule->id} бархӯрд — гузошта шуд");
                continue;
            }

            DB::table('schedules')
                ->where('id', $schedule->id)
                ->update([
                    'subject_assignment_id' => $canonical->id,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Обудакунии semester_grades: барои ҳар донишҷӯ як сатр дар канон.
     * Баллҳо = max аз ҳамаи сатрҳо (ҳеҷ балл гум намешавад), ҷамъбандӣ аз
     * рӯи мантиқи мавҷуда аз нав ҳисоб мешавад. Логҳо ва қарзҳо ба сатри
     * ниҳоӣ кӯчида мешаванд; сатрҳои зиёдӣ танҳо пас аз ҷамъкунӣ нест мешаванд.
     */
    private function mergeSemesterGrades(object $source, object $canonical): void
    {
        $rows = DB::table('semester_grades')
            ->whereIn('subject_assignment_id', [$canonical->id, $source->id])
            ->where('semester_id', $canonical->semester_id)
            ->get();

        $byStudent = $rows->groupBy('student_id');

        foreach ($byStudent as $studentId => $studentRows) {
            $target = $studentRows->first(fn ($r) => $r->subject_assignment_id == $canonical->id);
            $extras = $studentRows->filter(fn ($r) => $r->subject_assignment_id != $canonical->id);

            if ($extras->isEmpty()) {
                continue; // дар канон сатр нест → сатри кӯҳна кӯчонида мешавад
            }

            if ($target === null) {
                // Канон сатр надорад → аввалин сатр кӯчонида мешавад
                $target = $extras->shift();
                DB::table('semester_grades')
                    ->where('id', $target->id)
                    ->update([
                        'subject_assignment_id' => $canonical->id,
                        'updated_at' => now(),
                    ]);
            }

            if ($extras->isEmpty()) {
                continue;
            }

            // Ҷамъи баллҳо: ҳар майдон = max (ҳеҷ балл гум намешавад)
            $merged = [
                'rating1_score' => $this->maxNullable([$target->rating1_score, ...$extras->pluck('rating1_score')->all()]),
                'rating2_score' => $this->maxNullable([$target->rating2_score, ...$extras->pluck('rating2_score')->all()]),
                'exam_score' => $this->maxNullable([$target->exam_score, ...$extras->pluck('exam_score')->all()]),
                'retake_score' => $this->maxNullable([$target->retake_score, ...$extras->pluck('retake_score')->all()]),
                'retake2_score' => $this->maxNullable([$target->retake2_score ?? null, ...$extras->pluck('retake2_score')->all()]),
            ];

            // Аз нав ҳисоби рейтинг аз журналиттиҳодшуда (мантиқи мавҷуда)
            $gradeCalc = app(GradeCalculator::class);
            $computedR1 = $gradeCalc->calculateRating1((int) $studentId, $canonical->id, $canonical->semester_id);
            $computedR2 = $gradeCalc->calculateRating2((int) $studentId, $canonical->id, $canonical->semester_id);
            $computedExam = $gradeCalc->calculateExamScore((int) $studentId, $canonical->id, $canonical->semester_id);

            $merged['rating1_score'] = $this->maxNullable([$merged['rating1_score'], $computedR1 ?: null]);
            $merged['rating2_score'] = $this->maxNullable([$merged['rating2_score'], $computedR2 ?: null]);
            $merged['exam_score'] = $this->maxNullable([$merged['exam_score'], $computedExam]);

            // Сатрҳои зиёдӣ: аввал кӯчонидани логҳо/қарзҳо, баъд несткунӣ
            foreach ($extras as $extra) {
                DB::table('grade_change_log')
                    ->where('semester_grade_id', $extra->id)
                    ->update(['semester_grade_id' => $target->id]);

                DB::table('academic_debts')
                    ->where('semester_grade_id', $extra->id)
                    ->update(['semester_grade_id' => $target->id]);

                DB::table('semester_grades')->where('id', $extra->id)->delete();
                $this->mergedGradeRows++;
            }

            // Ҳисоби ниҳоӣ аз рӯи мантиқи мавҷуда
            $this->recomputeFinalGrade((int) $studentId, (int) $target->id, $canonical, $merged);
        }
    }

    /**
     * Ҳисоби ниҳоӣ + GPA + қарзҳо — такрори мантиқи recalculateAndPersist
     * бо ҳифзи баллҳои дастӣ (max ҳеҷ чизро нул намекунад).
     */
    private function recomputeFinalGrade(int $studentId, int $gradeId, object $canonical, array $merged): void
    {
        $effectiveExam = max(
            (float) ($merged['exam_score'] ?? 0),
            (float) ($merged['retake_score'] ?? 0),
            (float) ($merged['retake2_score'] ?? 0),
        );

        $r1 = (float) ($merged['rating1_score'] ?? 0);
        $r2 = (float) ($merged['rating2_score'] ?? 0);

        $total = null;
        $letter = null;
        $point = null;
        $traditional = null;
        $status = 'in_progress';
        $creditsEarned = 0;

        if ($effectiveExam > 0 || $r1 > 0 || $r2 > 0) {
            $total = round((($r1 + $r2) / 4) + ($effectiveExam * 0.5), 2);
            $grade = GradeScale::fromPercentage($total);
            $letter = $grade->value;
            $point = $grade->gradePoint();
            $traditional = $grade->traditionalGrade();

            if ($grade->isPassing()) {
                $status = 'passed';
                $creditsEarned = (int) ($canonical->credits ?? 0);
            } else {
                $status = $grade->canRetake() ? 'retake' : 'failed';
                $creditsEarned = 0;
            }
        }

        DB::table('semester_grades')->where('id', $gradeId)->update([
            'rating1_score' => $merged['rating1_score'],
            'rating2_score' => $merged['rating2_score'],
            'exam_score' => $merged['exam_score'],
            'retake_score' => $merged['retake_score'],
            'retake2_score' => $merged['retake2_score'],
            'total_score' => $total,
            'letter_grade' => $letter,
            'grade_point' => $point,
            'traditional_grade' => $traditional,
            'status' => $status,
            'credits_earned' => $creditsEarned,
            'updated_at' => now(),
        ]);

        // GPA барои ин семестр
        $student = Student::find($studentId);
        $semester = Semester::find($canonical->semester_id);
        if ($student && $semester && $total !== null) {
            app(GpaCalculator::class)->calculateSemesterGpa($student, $semester);
        }

        // Қарзҳо: мантиқи мавҷуда
        $semesterGrade = SemesterGrade::find($gradeId);
        if ($semesterGrade && $total !== null) {
            $debtDetector = app(DebtDetector::class);
            if ($total < 50) {
                $debtDetector->checkAndCreateDebt($semesterGrade);
            } elseif ($letter !== null) {
                $debtDetector->resolveDebtAfterRetake($studentId, $canonical->subject_id, $canonical->semester_id, $total, $letter);
            }
        }
    }

    private function mergeCreditsAndHours(object $canonical, $sources): void
    {
        $all = collect([$canonical, ...$sources->all()]);

        // Кредит: ҷамъи майдонҳои пур (2+2+2 = 6); агар ҳама null → мемонад
        $credits = $all
            ->map(fn ($r) => $r->credits !== null && $r->credits !== '' ? (int) $r->credits : null)
            ->filter()
            ->sum();

        $hoursTotal = $all->sum(fn ($r) => (int) ($r->hours_per_week ?? 0));

        $lectureHours = $all->where('lesson_type', 'lecture')->sum(fn ($r) => (int) ($r->hours_per_week ?? 0));
        $practicalHours = $all->where('lesson_type', 'practice')->sum(fn ($r) => (int) ($r->hours_per_week ?? 0));
        $labHours = $all->where('lesson_type', 'lab')->sum(fn ($r) => (int) ($r->hours_per_week ?? 0));

        DB::table('subject_assignments')->where('id', $canonical->id)->update([
            'credits' => $credits > 0 ? $credits : $canonical->credits,
            'hours_per_week' => $hoursTotal,
            'lecture_hours' => $lectureHours,
            'practical_hours' => $practicalHours,
            'lab_hours' => $labHours,
            'updated_at' => now(),
        ]);
    }

    /**
     * Пур кардани lesson_type дар сатрҳои журнал аз таъинот (идентӣ).
     */
    private function backfillLessonTypes(): void
    {
        $assignments = DB::table('subject_assignments')
            ->whereNotNull('lesson_type')
            ->get(['id', 'lesson_type']);

        $mapping = [];
        foreach ($assignments as $assignment) {
            $mapping[$assignment->id] = $assignment->lesson_type;
        }

        $this->backfillForAssignments($mapping);
    }

    /**
     * @param array<int, string|null> $mapping [assignment_id => lesson_type]
     */
    private function backfillForAssignments(array $mapping): void
    {
        $typeMap = [];
        foreach ($mapping as $id => $type) {
            if ($type !== null && $type !== '') {
                $typeMap[$type][] = (int) $id;
            }
        }

        foreach ($typeMap as $type => $ids) {
            if (empty($ids)) {
                continue;
            }

            DB::table('category_scores')
                ->whereIn('subject_assignment_id', $ids)
                ->whereNull('lesson_type')
                ->update(['lesson_type' => $type]);

            DB::table('attendances')
                ->whereIn('subject_assignment_id', $ids)
                ->whereNull('lesson_type')
                ->update(['lesson_type' => $type]);
        }
    }

    private function maxNullable(array $values): ?float
    {
        $values = array_filter($values, fn ($v) => $v !== null);

        return $values === [] ? null : (float) max($values);
    }
}
