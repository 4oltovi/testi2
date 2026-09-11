<?php

namespace App\Observers;

use App\Models\RatingAttempt;
use App\Models\SubjectAssignment;
use App\Services\GradeCalculator;
use Illuminate\Support\Facades\Log;

class RatingAttemptObserver
{
    /**
     * Ҳангоми сохтани attempt (кам дучор мешавад — одатан created бо status=in_progress)
     */
    public function created(RatingAttempt $attempt): void
    {
        if ($attempt->status === 'finished') {
            $this->sync($attempt);
        }
    }

    /**
     * Ҳангоми навсозӣ — асосан ҳангоми finished шудан
     */
    public function updated(RatingAttempt $attempt): void
    {
        // Танҳо вақте ки статус ба "finished" тағйир ёфт
        if ($attempt->wasChanged('status') && $attempt->status === 'finished') {
            $this->sync($attempt);
        }
    }

    /**
     * Синхрони rating_attempts → semester_grades
     */
    private function sync(RatingAttempt $attempt): void
    {
        $semesterId = $attempt->session?->semester_id;

        if (!$semesterId) {
            Log::warning('RatingAttemptObserver: session ё semester_id нест', [
                'attempt_id' => $attempt->id,
                'session_id' => $attempt->rating_session_id,
            ]);
            return;
        }

        // Ёфтани subject_assignment (эҳтимолан якчанд — аввалинашро мегирем)
        $subjectAssignment = SubjectAssignment::where('subject_id', $attempt->subject_id)
            ->where('semester_id', $semesterId)
            ->first();

        if (!$subjectAssignment) {
            Log::warning('RatingAttemptObserver: subject_assignment нест', [
                'attempt_id' => $attempt->id,
                'subject_id' => $attempt->subject_id,
                'semester_id' => $semesterId,
            ]);
            return;
        }

        try {
            app(GradeCalculator::class)->recalculateAndPersist(
                $attempt->student_id,
                $subjectAssignment->id,
                $semesterId
            );

            Log::info('RatingAttemptObserver: синхрон карда шуд', [
                'attempt_id' => $attempt->id,
                'student_id' => $attempt->student_id,
                'subject_assignment_id' => $subjectAssignment->id,
                'semester_id' => $semesterId,
            ]);
        } catch (\Throwable $e) {
            Log::error('RatingAttemptObserver: хато', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
