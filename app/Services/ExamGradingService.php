<?php

namespace App\Services;

use App\Enums\GradeScale;
use App\Models\ExamAnswer;
use App\Models\ExamAttempt;
use App\Models\ExamQuestion;
use App\Models\RetakeExamAnswer;
use App\Models\RetakeExamAttempt;
use Illuminate\Support\Collection;

class ExamGradingService
{
    public function gradeExamAttempt(ExamAttempt $attempt, ExamQuestion $examQuestion, ExamAnswer $answer, float $questionWeight): array
    {
        $question = $examQuestion->question;
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
            $result = $this->gradeMatchingQuestion($question, $answer->text_answer ?? '');
            $isCorrect = $result['is_correct'];
            $pointsEarned = $result['points_earned'];
        }

        return [
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'is_graded' => $question->type !== 'open_text',
        ];
    }

    public function gradeRetakeExamAttempt(RetakeExamAttempt $attempt, ExamQuestion $examQuestion, RetakeExamAnswer $answer, float $questionWeight): array
    {
        $question = $examQuestion->question;
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
            $result = $this->gradeMatchingQuestion($question, $answer->text_answer ?? '');
            $isCorrect = $result['is_correct'];
            $pointsEarned = $result['points_earned'];
        }

        return [
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
            'is_graded' => $question->type !== 'open_text',
        ];
    }

    private function gradeMatchingQuestion($question, string $textAnswer): array
    {
        $selectedPairs = [];
        $correctOptions = $question->answerOptions->where('is_correct', true)->values();

        if (str_contains($textAnswer, '||')) {
            foreach (explode('||', (string) $textAnswer) as $pair) {
                if (trim($pair) === '') continue;
                [$qId, $choice] = array_pad(explode(':', $pair, 2), 2, '');
                $selectedPairs[(string) $qId] = trim((string) $choice);
            }
        } else {
            $decoded = json_decode($textAnswer, true);
            if (is_array($decoded)) {
                foreach ($correctOptions as $idx => $correctOption) {
                    $selectedPairs[(string) $correctOption->id] = trim((string) ($decoded[$idx] ?? ''));
                }
            }
        }

        $correctCount = 0;
        foreach ($correctOptions as $correctOption) {
            $expected = trim(explode('|||', $correctOption->option_text, 2)[1] ?? '');
            $selectedValue = $selectedPairs[(string) $correctOption->id] ?? null;
            if ($selectedValue !== null && trim((string) $selectedValue) === $expected) {
                $correctCount++;
            }
        }

        $totalCorrect = $correctOptions->count();
        $isCorrect = $correctCount > 0 && $correctCount === $totalCorrect;
        $pointsEarned = $correctCount * 2.5;

        return [
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
        ];
    }

    public function calculatePercentage(float $totalScore, float $maxPossible): float
    {
        return $maxPossible > 0 ? round(($totalScore / $maxPossible) * 100, 2) : 0;
    }

    public function determineGrade(float $percentage): array
    {
        $grade = GradeScale::fromPercentage($percentage);

        return [
            'letter_grade' => $grade->value,
            'grade_point' => $grade->gradePoint(),
            'is_passing' => $grade->isPassing(),
            'can_retake' => $grade->canRetake(),
            'must_repeat' => $grade->mustRepeatCourse(),
        ];
    }
}
