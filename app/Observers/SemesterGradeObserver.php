<?php

namespace App\Observers;

use App\Models\SemesterGrade;
use App\Services\GradeCalculator;
use Illuminate\Support\Facades\Log;

class SemesterGradeObserver
{
    public function saved(SemesterGrade $semesterGrade): void
    {
        $scoringFieldsChanged = $semesterGrade->wasChanged([
            'rating1_score', 'rating2_score', 'exam_score', 'retake_score',
        ]);

        if (!$scoringFieldsChanged) {
            return;
        }

        try {
            app(GradeCalculator::class)->recalculateAndPersist(
                $semesterGrade->student_id,
                $semesterGrade->subject_assignment_id,
                $semesterGrade->semester_id
            );

            Log::info('SemesterGradeObserver: recalculated and synced', [
                'semester_grade_id' => $semesterGrade->id,
                'student_id' => $semesterGrade->student_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('SemesterGradeObserver: error', [
                'semester_grade_id' => $semesterGrade->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
