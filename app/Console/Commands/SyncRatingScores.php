<?php

namespace App\Console\Commands;

use App\Models\RatingAttempt;
use App\Models\SubjectAssignment;
use App\Services\GradeCalculator;
use Illuminate\Console\Command;

class SyncRatingScores extends Command
{
    protected $signature = 'rating:sync 
                            {--semester= : ID-и семестр (ихтиёрӣ)}
                            {--student= : ID-и донишҷӯ (ихтиёрӣ)}';

    protected $description = 'Ҳамоҳангсозии rating_attempts → semester_grades';

    public function handle(GradeCalculator $calculator): int
    {
        $query = RatingAttempt::where('status', 'finished')->with('session');

        if ($semesterId = $this->option('semester')) {
            $query->whereHas('session', fn($q) => $q->where('semester_id', $semesterId));
        }

        if ($studentId = $this->option('student')) {
            $query->where('student_id', $studentId);
        }

        $attempts = $query->get();

        if ($attempts->isEmpty()) {
            $this->warn('Ҳеҷ attempt-и finished ёфт нашуд.');
            return self::SUCCESS;
        }

        $this->info("Ёфт шуд: {$attempts->count()} attempt");
        $bar = $this->output->createProgressBar($attempts->count());
        $bar->start();

        $synced = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($attempts as $attempt) {
            $semesterId = $attempt->session?->semester_id;
            if (!$semesterId) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $subjectAssignment = SubjectAssignment::where('subject_id', $attempt->subject_id)
                ->where('semester_id', $semesterId)
                ->first();

            if (!$subjectAssignment) {
                $this->newLine();
                $this->warn("⚠️ attempt #{$attempt->id}: subject_assignment нест (subject={$attempt->subject_id}, sem={$semesterId})");
                $skipped++;
                $bar->advance();
                continue;
            }

            try {
                $calculator->recalculateAndPersist(
                    $attempt->student_id,
                    $subjectAssignment->id,
                    $semesterId
                );
                $synced++;
            } catch (\Throwable $e) {
                $this->newLine();
                $this->error("❌ attempt #{$attempt->id}: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Синхрон: {$synced}");
        $this->warn("⚠️ Гузаронида: {$skipped}");
        if ($errors > 0) {
            $this->error("❌ Хато: {$errors}");
        }

        return self::SUCCESS;
    }
}
