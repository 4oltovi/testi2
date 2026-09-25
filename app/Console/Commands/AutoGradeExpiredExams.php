<?php

namespace App\Console\Commands;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\RetakeExam;
use App\Models\RetakeExamAttempt;
use App\Services\GradeCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoGradeExpiredExams extends Command
{
    protected $signature = 'app:auto-grade-expired-exams';

    protected $description = 'Auto-grade expired exams and mark attempts as auto_submitted';

    public function handle()
    {
        $now = now();

        $mainExams = Exam::whereNotNull('ends_at')
            ->where('ends_at', '<', $now)
            ->where('status', '!=', 'archived')
            ->get();

        $processed = 0;

        foreach ($mainExams as $exam) {
            $attempts = ExamAttempt::where('exam_id', $exam->id)
                ->where('status', 'in_progress')
                ->get();

            foreach ($attempts as $attempt) {
                $this->processMainExamAttempt($attempt, $exam);
                $processed++;
            }
        }

        // foreach ($retakeExams as $retakeExam) {
        //     $attempts = RetakeExamAttempt::where('retake_exam_id', $retakeExam->id)
        //         ->where('status', 'in_progress')
        //         ->get();
        //
        //     foreach ($attempts as $attempt) {
        //         $this->processRetakeExamAttempt($attempt, $retakeExam);
        //         $processed++;
        //     }
        // }

        $this->info("Auto-graded {$processed} expired exam attempts.");
    }

    private function questionWeight($question): float
    {
        return match ($question->type ?? '') {
            'single_choice', 'multiple_choice', 'true_false' => 2.5,
            'matching' => 10.0,
            'open_text' => 0.0,
            default => (float) ($question->points ?? 2.5),
        };
    }

    private function processMainExamAttempt(ExamAttempt $attempt, Exam $exam): void
    {
        DB::transaction(function () use ($attempt, $exam) {
            $answers = ExamAnswer::where('exam_attempt_id', $attempt->id)->get();
            $examQuestions = ExamQuestion::where('exam_id', $exam->id)
                ->with('question.answerOptions')
                ->get()
                ->keyBy('id');

            $totalScore = 0;
            $maxPossible = 0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $questionWeight = $this->questionWeight($question);
                $maxPossible += $questionWeight;

                $answer = $answers->where('exam_question_id', $eq->id)->first();
                if (!$answer) continue;

                $isCorrect = false;
                $pointsEarned = 0;

                if (in_array($question->type, ['single_choice', 'true_false'])) {
                    $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->toArray();
                    $selected = json_decode($answer->selected_options ?? '[]', true) ?: [];
                    $isCorrect = !empty($selected) && $selected == $correctOptions;
                    $pointsEarned = $isCorrect ? $questionWeight : 0;
                } elseif ($question->type === 'multiple_choice') {
                    $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
                    $selected = collect(json_decode($answer->selected_options ?? '[]', true) ?: [])->sort()->values()->toArray();
                    $isCorrect = $selected === $correctOptions;
                    $pointsEarned = $isCorrect ? $questionWeight : 0;
                } elseif ($question->type === 'matching') {
                    $selectedPairs = [];
                    foreach (explode('||', (string) ($answer->text_answer ?? '')) as $pair) {
                        if (trim($pair) === '') continue;
                        [$qId, $choice] = array_pad(explode(':', $pair, 2), 2, '');
                        $selectedPairs[(string) $qId] = trim((string) $choice);
                    }

                    $correctCount = 0;
                    foreach ($question->answerOptions->where('is_correct', true) as $correctOption) {
                        $expected = trim(explode('|||', $correctOption->option_text, 2)[1] ?? '');
                        $selectedValue = $selectedPairs[(string) $correctOption->id] ?? null;
                        if ($selectedValue !== null && trim((string) $selectedValue) === $expected) {
                            $correctCount++;
                        }
                    }

                    $totalCorrect = $question->answerOptions->where('is_correct', true)->count();
                    $isCorrect = $correctCount > 0 && $correctCount === $totalCorrect;
                    $pointsEarned = $correctCount * 2.5;
                }

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'is_graded' => true,
                ]);

                $totalScore += $pointsEarned;
            }

            $percentage = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0;

            $attempt->update([
                'status' => 'auto_submitted',
                'auto_submitted_at' => now(),
                'total_score' => $totalScore,
                'max_possible_score' => $maxPossible,
                'percentage' => $percentage,
            ]);

            $this->updateSemesterGrade($attempt, $exam, $percentage);
        });
    }

    private function processRetakeExamAttempt(RetakeExamAttempt $attempt, RetakeExam $retakeExam): void
    {
        DB::transaction(function () use ($attempt, $retakeExam) {
            $answers = RetakeExamAnswer::where('retake_exam_attempt_id', $attempt->id)->get();
            $examQuestions = $retakeExam->mainExam->examQuestions()
                ->with('question.answerOptions')
                ->get()
                ->keyBy('id');

            $totalScore = 0;
            $maxPossible = 0;

            foreach ($examQuestions as $eq) {
                $question = $eq->question;
                $questionWeight = $question->points ?? 2.5;
                $maxPossible += $questionWeight;

                $answer = $answers->where('exam_question_id', $eq->id)->first();
                if (!$answer) continue;

                $isCorrect = false;
                $pointsEarned = 0;

                if (in_array($question->type, ['single_choice', 'true_false'])) {
                    $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->toArray();
                    $selected = json_decode($answer->selected_options ?? '[]', true) ?: [];
                    $isCorrect = !empty($selected) && $selected == $correctOptions;
                    $pointsEarned = $isCorrect ? $questionWeight : 0;
                } elseif ($question->type === 'multiple_choice') {
                    $correctOptions = $question->answerOptions->where('is_correct', true)->pluck('id')->sort()->values()->toArray();
                    $selected = collect(json_decode($answer->selected_options ?? '[]', true) ?: [])->sort()->values()->toArray();
                    $isCorrect = $selected === $correctOptions;
                    $pointsEarned = $isCorrect ? $questionWeight : 0;
                } elseif ($question->type === 'matching') {
                    $selectedPairs = [];
                    foreach (explode('||', (string) ($answer->text_answer ?? '')) as $pair) {
                        if (trim($pair) === '') continue;
                        [$qId, $choice] = array_pad(explode(':', $pair, 2), 2, '');
                        $selectedPairs[(string) $qId] = trim((string) $choice);
                    }

                    $correctCount = 0;
                    foreach ($question->answerOptions->where('is_correct', true) as $correctOption) {
                        $expected = trim(explode('|||', $correctOption->option_text, 2)[1] ?? '');
                        $selectedValue = $selectedPairs[(string) $correctOption->id] ?? null;
                        if ($selectedValue !== null && trim((string) $selectedValue) === $expected) {
                            $correctCount++;
                        }
                    }

                    $totalCorrect = $question->answerOptions->where('is_correct', true)->count();
                    $isCorrect = $correctCount > 0 && $correctCount === $totalCorrect;
                    $pointsEarned = $correctCount * 2.5;
                }

                $answer->update([
                    'is_correct' => $isCorrect,
                    'points_earned' => $pointsEarned,
                    'is_graded' => true,
                ]);

                $totalScore += $pointsEarned;
            }

            $percentage = $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0;

            $attempt->update([
                'status' => 'auto_submitted',
                'auto_submitted_at' => now(),
                'total_score' => $totalScore,
                'max_possible_score' => $maxPossible,
                'percentage' => $percentage,
            ]);

            $retakeExamStudent = $attempt->retakeExamStudent;
            if ($retakeExamStudent) {
                $retakeExamStudent->update([
                    'score' => $totalScore,
                    'examined_at' => now(),
                ]);

                app(GradeCalculator::class)->recalculateAndPersist(
                    $attempt->student_id,
                    $retakeExam->mainExam->subject_assignment_id,
                    $retakeExam->semester_id
                );
            }
        });
    }

    private function updateSemesterGrade(ExamAttempt $attempt, Exam $exam, float $percentage): void
    {
        $subjectAssignment = $exam->subjectAssignment;
        if (!$subjectAssignment) {
            return;
        }

        $semesterGrade = \App\Models\SemesterGrade::where('student_id', $attempt->student_id)
            ->where('subject_assignment_id', $subjectAssignment->id)
            ->where('semester_id', $exam->semester_id)
            ->first();

        if (!$semesterGrade) {
            $semesterGrade = \App\Models\SemesterGrade::create([
                'student_id' => $attempt->student_id,
                'subject_assignment_id' => $subjectAssignment->id,
                'subject_id' => $subjectAssignment->subject_id,
                'semester_id' => $exam->semester_id,
                'status' => 'in_progress',
            ]);
        }

        $semesterGrade->exam_score = $percentage;
        $semesterGrade->save();

        app(GradeCalculator::class)->recalculateAndPersist(
            $attempt->student_id,
            $subjectAssignment->id,
            $exam->semester_id
        );
    }
}
