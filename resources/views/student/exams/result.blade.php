@extends('layouts.app')

@section('title', 'Натиҷаи тест')
@section('page-header', 'Натиҷаи тест')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center py-4 py-md-5">
                @php
                $passed = $attempt->percentage >= $exam->passing_score;
                $statusLabel = match($attempt->status) {
                'submitted' => 'Супорида шуд',
                'auto_submitted' => 'Автоматикӣ супорида шуд',
                'graded' => 'Баҳогузорӣ шуд',
                'in_progress' => 'Дар ҷараён',
                'invalidated' => 'Бекор шуд',
                default => $attempt->status,
                };
                @endphp

                <div class="mb-3">
                    @if($passed)
                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle" style="width:80px;height:80px;">
                        <i class="bi bi-check-lg text-success" style="font-size: 2.5rem;"></i>
                    </div>
                    @else
                    <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 rounded-circle" style="width:80px;height:80px;">
                        <i class="bi bi-x-lg text-danger" style="font-size: 2.5rem;"></i>
                    </div>
                    @endif
                </div>

                <h4 class="{{ $passed ? 'text-success' : 'text-danger' }}">
                    {{ $passed ? 'Табрик! Шумо гузаштед!' : 'Мутаассифона, шумо нагузаштед.' }}
                </h4>

                <div class="row mt-4 g-2 justify-content-center">
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3">
                            <h3 class="mb-0 {{ $passed ? 'text-success' : 'text-danger' }}">
                                {{ number_format($attempt->percentage, 0) }}%
                            </h3>
                            <small class="text-muted">Фоиз</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="border rounded p-3">
                            <h3 class="mb-0">
                                {{ number_format($attempt->total_score, 0) }}/{{ number_format($attempt->max_possible_score, 0) }}
                            </h3>
                            <small class="text-muted">Баллҳо</small>
                        </div>
                    </div>
                </div>

                <div class="mt-3 small text-muted">
                    <small>
                        Ҳадди гузариш: {{ $exam->passing_score }}% |
                        Ҳолат: <strong>{{ $statusLabel }}</strong> |
                        Вақт: {{ ($attempt->submitted_at ?? $attempt->auto_submitted_at)?->format('d.m.Y H:i') }}
                    </small>
                </div>
            </div>
        </div>

        @if($showDetails && $attempt->examAnswers->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-list-check me-2"></i> Тафсилоти ҷавобҳо</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 5%">#</th>
                                <th style="width: 35%">Савол</th>
                                <th style="width: 25%">Ҷавоби шумо</th>
                                <th style="width: 25%">Ҷавоби дуруст</th>
                                <th style="width: 5%">Натиҷа</th>
                                <th style="width: 5%">Балл</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attempt->examAnswers->sortBy('examQuestion.sort_order') as $index => $answer)
                            @php
                                $question = $answer->question;
                                $correctOptions = $question?->answerOptions?->where('is_correct', true) ?? collect();
                                $selectedOptions = [];
                                $selectedRaw = is_array($answer->selected_options)
                                    ? $answer->selected_options
                                    : ($answer->selected_options ? json_decode($answer->selected_options, true) : []);
                                if (is_array($selectedRaw)) {
                                    foreach ($selectedRaw as $optionId) {
                                        $selectedOptions[] = $question?->answerOptions?->firstWhere('id', (int) $optionId)?->option_text ?? '—';
                                    }
                                }

                                if (($question?->type ?? null) === 'matching' && !empty($answer->text_answer)) {
                                    $selectedOptions = collect(explode('||', $answer->text_answer))
                                        ->filter(fn($pair) => trim($pair) !== '')
                                        ->mapWithKeys(function ($pair) {
                                            $parts = explode(':', $pair, 2);
                                            return [trim($parts[0] ?? '') => trim($parts[1] ?? '')];
                                        })->all();
                                }

                                if (($question?->type ?? null) === 'open_text') {
                                    $selectedOptions = [trim((string) ($answer->text_answer ?? '')) ?: '—'];
                                }

                                $correctText = match ($question?->type ?? '') {
                                    'single_choice', 'multiple_choice', 'true_false' => $correctOptions->pluck('option_text')->implode('; '),
                                    'matching' => collect($correctOptions)->map(function ($option) {
                                        $parts = explode('|||', $option->option_text);
                                        return trim($parts[0] ?? '') . ' → ' . trim($parts[1] ?? '');
                                    })->implode('; '),
                                    'open_text' => 'Ҷавоби кушод (устод тафтиш мекунад)',
                                    default => '—',
                                };
                                $isMatching = ($question?->type ?? null) === 'matching';
                                $matchingCorrectCount = 0;
                                if ($isMatching && !empty($answer->text_answer)) {
                                    $selectedPairs = collect(explode('||', $answer->text_answer))
                                        ->filter(fn($pair) => trim($pair) !== '')
                                        ->mapWithKeys(function ($pair) {
                                            $parts = explode(':', $pair, 2);
                                            return [trim($parts[0] ?? '') => trim($parts[1] ?? '')];
                                        });
                                    $matchingCorrectCount = $correctOptions->filter(function ($option) use ($selectedPairs) {
                                        $expected = trim(explode('|||', $option->option_text, 2)[1] ?? '');
                                        return ($selectedPairs[(string) $option->id] ?? null) === $expected;
                                    })->count();
                                }
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $question?->question_text }}</td>
                                <td>
                                    @if(($question?->type ?? null) === 'matching')
                                        @forelse($correctOptions as $correctOption)
                                            @php
                                                $correctParts = explode('|||', $correctOption->option_text, 2);
                                                $selectedValue = $selectedOptions[(string) $correctOption->id] ?? '—';
                                            @endphp
                                            <div class="mb-1">
                                                <strong>{{ trim($correctParts[0] ?? '') }}</strong>:
                                                <span>{{ $selectedValue }}</span>
                                            </div>
                                        @empty
                                            —
                                        @endforelse
                                    @elseif(!empty($selectedOptions))
                                        {{ implode('; ', $selectedOptions) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if(($question?->type ?? null) === 'matching')
                                        @foreach($correctOptions as $correctOption)
                                            @php $correctParts = explode('|||', $correctOption->option_text, 2); @endphp
                                            <div class="mb-1">{{ trim($correctParts[0] ?? '') }}: <strong>{{ trim($correctParts[1] ?? '') }}</strong></div>
                                        @endforeach
                                    @else
                                        {{ $correctText }}
                                    @endif
                                </td>
                                <td>
                                    @if($isMatching && $matchingCorrectCount > 0 && $matchingCorrectCount < $correctOptions->count())
                                    <span class="badge bg-warning text-dark">Қисман дуруст ({{ $matchingCorrectCount }}/{{ $correctOptions->count() }})</span>
                                    @elseif($answer->is_correct === true)
                                    <span class="badge bg-success">Дуруст</span>
                                    @elseif($answer->is_correct === false)
                                    <span class="badge bg-danger">Нодуруст</span>
                                    @else
                                    <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                                <td>
                                    {{ number_format((float) ($answer->points_earned ?? 0), 1) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        <div class="text-center mt-4">
            <a href="{{ route('student.exams.index') }}" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left me-1"></i> Ба рӯйхати тестҳо
            </a>
        </div>
    </div>
</div>
@endsection