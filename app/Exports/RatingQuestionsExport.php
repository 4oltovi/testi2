<?php

namespace App\Exports;

use App\Models\Question;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class RatingQuestionsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private int $subjectId) {}

    public function query()
    {
        return Question::query()
            ->where('subject_id', $this->subjectId)
            ->whereHas('questionBank', fn ($q) => $q->where('bank_type', 'rating'))
            ->with('answerOptions')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Савол', 'Вариантҳо', 'Дуруст', 'Душворӣ', 'Сабаб'];
    }

    public function map($question): array
    {
        $options = $question->answerOptions->map(fn($o) => $o->option_text)->implode('|');
        $correct = $question->answerOptions->search(fn($o) => $o->is_correct);

        return [
            $question->question_text,
            $options,
            $correct !== false ? (string) $correct : '0',
            $question->difficulty_level ?? 1,
            $question->explanation ?? '',
        ];
    }

    public function title(): string
    {
        return 'Саволҳои рейтинг';
    }
}
