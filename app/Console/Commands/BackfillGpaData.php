<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SemesterGrade;
use App\Models\Student;
use App\Models\Semester;
use App\Services\GpaCalculator;

class BackfillGpaData extends Command
{
    protected $signature = 'gpa:backfill';
    protected $description = 'Backfill GPA data for existing records';

    public function handle(): void
    {
        $gpaCalc = app(GpaCalculator::class);

        // Step 1: fix credits_earned on existing finalized+passed grades
        $updated = 0;
        SemesterGrade::where('is_finalized', true)
            ->where('status', 'passed')
            ->with('subjectAssignment')
            ->get()
            ->each(function ($grade) use (&$updated) {
                $credits = $grade->subjectAssignment?->credits ?? 0;
                if ($grade->credits_earned != $credits) {
                    $grade->update(['credits_earned' => $credits]);
                    $updated++;
                }
            });

        $this->info("Step 1 done: credits_earned updated for {$updated} grades");

        // Step 2: recalculate semester + cumulative GPA for every student/semester pair
        $processed = 0;
        Student::chunk(100, function ($students) use ($gpaCalc, &$processed) {
            foreach ($students as $student) {
                $semesterIds = SemesterGrade::where('student_id', $student->id)
                    ->where('is_finalized', true)
                    ->pluck('semester_id')
                    ->unique();

                foreach ($semesterIds as $semesterId) {
                    $semester = Semester::find($semesterId);
                    if ($semester) {
                        $gpaCalc->calculateSemesterGpa($student, $semester);
                        $processed++;
                    }
                }
            }
        });

        $this->info("Step 2 done: GPA recalculated for {$processed} student-semester pairs");
    }
}
