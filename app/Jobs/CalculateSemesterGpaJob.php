<?php

namespace App\Jobs;

use App\Models\Semester;
use App\Models\Student;
use App\Services\GpaCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CalculateSemesterGpaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $studentId;
    public int $semesterId;

    /**
     * Ҳосили нав барои ҳисоби GPA
     */
    public function __construct(int $studentId, int $semesterId)
    {
        $this->studentId = $studentId;
        $this->semesterId = $semesterId;
    }

    /**
     * Иҷоди кор
     */
    public function handle(): void
    {
        $student = Student::find($this->studentId);
        $semester = Semester::find($this->semesterId);

        if (!$student || !$semester) {
            return;
        }

        app(GpaCalculator::class)->calculateSemesterGpa($student, $semester);
    }
}