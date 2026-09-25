<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Services\DebtDetector;
use Illuminate\Console\Command;

class ProcessExamNoShows extends Command
{
    protected $signature = 'app:process-exam-no-shows';

    protected $description = 'Fail students who never started an exam that has now ended, and flag them as debtors';

    public function handle(DebtDetector $debtDetector)
    {
        $expiredExams = Exam::whereNotNull('ends_at')
            ->where('ends_at', '<', now())
            ->whereNull('no_shows_processed_at')
            ->where('status', '!=', 'archived')
            ->get();

        $processed = 0;

        foreach ($expiredExams as $exam) {
            $subjectAssignment = $exam->subjectAssignment;

            if (!$subjectAssignment) {
                $exam->update(['no_shows_processed_at' => now()]);
                continue;
            }

            $debtDetector->autoFailAbsentStudents(
                $subjectAssignment->subject_id,
                $exam->semester_id
            );

            $exam->update(['no_shows_processed_at' => now()]);
            $processed++;
        }

        $this->info("Processed no-shows for {$processed} expired exam(s).");
    }
}
