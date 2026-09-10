@extends('layouts.app')

@section('title', 'Натиҷаи имтиҳони такрорӣ')
@section('page-header', 'Имтиҳони такрорӣ')
@section('page-description', 'Натиҷаи имтиҳони такрорӣ')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">{{ $retakeExam->title }}</h6>
                <small class="text-muted">
                    {{ $retakeExam->subject->name ?? '-' }} |
                    {{ $retakeExam->semester->name ?? '-' }}
                </small>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Баҳо</h6>
                                <div class="display-4 fw-bold {{ $attempt->percentage >= $retakeExam->passing_score ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($attempt->total_score, 1) }} / {{ number_format($attempt->max_possible_score, 1) }}
                                </div>
                                <small class="text-muted">{{ number_format($attempt->percentage, 1) }}%</small>
                                <div class="mt-2">
                                    <span class="badge bg-{{ $attempt->percentage >= $retakeExam->passing_score ? 'success' : 'danger' }}">
                                        {{ $attempt->letter_grade }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="text-muted mb-3">Маълумот</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Кӯшиш:</strong> {{ $attempt->attempt_number }}</li>
                                    <li><strong>Ҳолат:</strong> {{ $attempt->status }}</li>
                                    <li><strong>Супоридан:</strong> {{ $attempt->submitted_at?->format('d.m.Y H:i') ?? '-' }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                 @if($showDetails)
                 <div class="mt-4">
                     <h6 class="mb-3">Саволҳо</h6>
                     @foreach($attempt->answers as $answer)
                     @php
                     $eq = $answer->examQuestion;
                     $question = $eq?->question;
                     
                     $selectedOptions = [];
                     $selectedRaw = is_array($answer->selected_options)
                         ? $answer->selected_options
                         : ($answer->selected_options ? json_decode($answer->selected_options, true) : []);
                     if (is_array($selectedRaw)) {
                         foreach ($selectedRaw as $optionId) {
                             $selectedOptions[] = $question?->answerOptions?->firstWhere('id', (int) $optionId)?->option_text ?? '—';
                         }
                     }
                     
                     $matchingSelected = [];
                     $matchingCorrectCount = 0;
                     $correctOptions = $question?->answerOptions?->where('is_correct', true) ?? collect();
                     if (($question?->type ?? null) === 'matching' && !empty($answer->text_answer)) {
                         $matchingSelected = collect(explode('||', $answer->text_answer))
                             ->filter(fn($pair) => trim($pair) !== '')
                             ->mapWithKeys(function ($pair) {
                                 $parts = explode(':', $pair, 2);
                                 return [trim($parts[0] ?? '') => trim($parts[1] ?? '')];
                             })->all();
                         $matchingCorrectCount = $correctOptions->filter(function ($option) use ($matchingSelected) {
                             $expected = trim(explode('|||', $option->option_text, 2)[1] ?? '');
                             return ($matchingSelected[(string) $option->id] ?? null) === $expected;
                         })->count();
                     }
                     
                     if (($question?->type ?? null) === 'open_text') {
                         $selectedOptions = [trim((string) ($answer->text_answer ?? '')) ?: '—'];
                     }
                     @endphp
                     <div class="card mb-2">
                         <div class="card-body">
                             <div class="d-flex justify-content-between align-items-start">
                                 <div>
                                     <p class="mb-1">{{ $question->question_text ?? 'Савол #' . $question->id }}</p>
                                     @if(($question?->type ?? null) === 'matching')
                                         <div class="small mt-2">
                                             @foreach($correctOptions as $correctOption)
                                                 @php $parts = explode('|||', $correctOption->option_text, 2); @endphp
                                                 <div class="mb-1">
                                                     <strong>{{ trim($parts[0] ?? '') }}</strong>:
                                                     шумо — {{ $matchingSelected[(string) $correctOption->id] ?? '—' }};
                                                     дуруст — <strong>{{ trim($parts[1] ?? '') }}</strong>
                                                 </div>
                                             @endforeach
                                         </div>
                                     @else
                                         <small class="text-muted">
                                             Ҷавоб: {{ !empty($selectedOptions) ? implode('; ', $selectedOptions) : '-' }}
                                         </small>
                                     @endif
                                 </div>
                                     <span class="badge bg-{{ $question?->type === 'matching' && $matchingCorrectCount > 0 && $matchingCorrectCount < $correctOptions->count() ? 'warning text-dark' : ($answer->is_correct ? 'success' : 'danger') }}">
                                         @if($question?->type === 'matching' && $matchingCorrectCount > 0 && $matchingCorrectCount < $correctOptions->count())
                                             Қисман дуруст ({{ $matchingCorrectCount }}/{{ $correctOptions->count() }})
                                         @else
                                             {{ $answer->is_correct ? 'Дуруст' : 'Нодуруст' }}
                                         @endif
                                 </span>
                             </div>
                         </div>
                     </div>
                     @endforeach
                 </div>
                 @endif

                <div class="mt-3">
                    <a href="{{ route('student.retake-exams.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i> Ба рӯйхат
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
