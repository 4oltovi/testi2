<?php

namespace App\Console\Commands;

use App\Enums\DebtStatus;
use App\Models\AcademicDebt;
use App\Models\SemesterGrade;
use Illuminate\Console\Command;

class BackfillResolvedDebts extends Command
{
    /**
     * One-time migration-style fix: resolve debts for students whose
     * SemesterGrade already shows 'passed' but whose AcademicDebt is still
     * open (active/retake_scheduled/escalated) — leftovers from before the
     * retake-debt fix that switched debt resolution to use the final
     * combined semester grade instead of the raw retake exam score.
     *
     * Run this ONCE manually after deploying the retake-debt fix. It is NOT
     * scheduled — do not add it to routes/console.php's Schedule::call()
     * list. Re-running it is harmless (already-resolved debts are skipped)
     * but unnecessary.
     */
    protected $signature = 'app:backfill-resolved-debts {--dry-run : Show what would change without saving anything}';

    protected $description = 'One-time fix: resolve open debts for students whose SemesterGrade already shows passed (pre-fix data)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $openDebts = AcademicDebt::whereIn('status', [
            DebtStatus::ACTIVE,
            DebtStatus::RETAKE_SCHEDULED,
            DebtStatus::ESCALATED,
        ])->get();

        $this->info("Found {$openDebts->count()} open debt(s) to check.");

        $resolvedCount = 0;
        $untouchedCount = 0;

        foreach ($openDebts as $debt) {
            $semesterGrade = SemesterGrade::where('student_id', $debt->student_id)
                ->where('subject_id', $debt->subject_id)
                ->where('semester_id', $debt->semester_id)
                ->first();

            if (!$semesterGrade) {
                $this->warn("Debt #{$debt->id} (student #{$debt->student_id}): no matching SemesterGrade found, skipping.");
                $untouchedCount++;
                continue;
            }

            if ($semesterGrade->status === 'passed') {
                $this->line("Debt #{$debt->id} (student #{$debt->student_id}): SemesterGrade shows PASSED ({$semesterGrade->letter_grade}, {$semesterGrade->total_score}%) but debt is still '{$debt->status->value}' — " . ($dryRun ? 'WOULD RESOLVE' : 'resolving now'));

                if (!$dryRun) {
                    $debt->resolve(
                        $semesterGrade->total_score,
                        $semesterGrade->letter_grade,
                        \Illuminate\Support\Facades\Auth::id() ?? 1
                    );
                }

                $resolvedCount++;
            } else {
                $untouchedCount++;
            }
        }

        $this->info(($dryRun ? '[DRY RUN] Would resolve' : 'Resolved') . " {$resolvedCount} debt(s). Left {$untouchedCount} debt(s) unchanged (still genuinely failing / no grade found).");
    }
}