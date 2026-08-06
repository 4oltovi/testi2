@extends('layouts.app')

@section('title', 'Имтиҳон: ' . $exam->title)

@section('content')
<div class="row" x-data="examTimer({{ $remainingSeconds }})">
    {{-- Таймер --}}
    <div class="col-12 mb-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex justify-content-between align-items-center py-2">
                <div>
                    <strong>{{ $exam->title }}</strong>
                    <span class="text-muted ms-2">| {{ $exam->subjectAssignment?->curriculum?->subject?->name }}</span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3 text-muted">Вақти боқимонда:</span>
                    <span class="badge fs-5" :class="isDanger ? 'bg-danger' : (isWarning ? 'bg-warning' : 'bg-primary')"
                          x-text="formatted"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Саволҳо --}}
    <div class="col-lg-9">
        <form id="exam-form" method="POST" action="{{ route('student.exams.submit', $attempt) }}">
            @csrf
            @foreach($questions as $index => $examQuestion)
                @php
                    $question = $examQuestion->question;
                    $options = $question->answerOptions;
                    if ($exam->shuffle_answers) { $options = $options->shuffle(); }
                    $savedAnswer = $existingAnswers[$examQuestion->id] ?? null;
                @endphp
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white d-flex justify-content-between">
                        <span><strong>Савол {{ $index + 1 }}</strong> аз {{ $questions->count() }}</span>
                        <span class="badge bg-secondary">{{ $examQuestion->points }} балл</span>
                    </div>
                    <div class="card-body">
                        <p class="fw-semibold mb-3">{!! nl2br(e($question->question_text)) !!}</p>

                        @if($question->type === 'single_choice' || $question->type === 'true_false')
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="answers[{{ $examQuestion->id }}][]"
                                           value="{{ $option->id }}"
                                           id="opt_{{ $examQuestion->id }}_{{ $option->id }}"
                                           {{ $savedAnswer && in_array($option->id, (array)$savedAnswer) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="opt_{{ $examQuestion->id }}_{{ $option->id }}">
                                        {{ $option->option_text }}
                                    </label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'multiple_choice')
                            @foreach($options as $option)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="answers[{{ $examQuestion->id }}][]"
                                           value="{{ $option->id }}"
                                           id="opt_{{ $examQuestion->id }}_{{ $option->id }}"
                                           {{ $savedAnswer && in_array($option->id, (array)$savedAnswer) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="opt_{{ $examQuestion->id }}_{{ $option->id }}">
                                        {{ $option->option_text }}
                                    </label>
                                </div>
                            @endforeach
                        @elseif($question->type === 'open_text')
                            <textarea class="form-control" name="text_answers[{{ $examQuestion->id }}]" rows="4"
                                      placeholder="Ҷавоби худро дар ин ҷо нависед..."></textarea>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="d-flex justify-content-between mb-5">
                <span class="text-muted">{{ $questions->count() }} савол</span>
                <button type="submit" class="btn btn-success btn-lg"
                        data-confirm="Оё мехоҳед имтиҳонро супоред? Баъд тағйир додан мумкин нест.">
                    <i class="bi bi-send me-2"></i> Супоридан
                </button>
            </div>
        </form>
    </div>

    {{-- Навигатсия --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm position-sticky" style="top: 80px;">
            <div class="card-header bg-white"><h6 class="mb-0">Саволҳо</h6></div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-1">
                    @foreach($questions as $index => $eq)
                        <a href="#" class="btn btn-sm {{ isset($existingAnswers[$eq->id]) ? 'btn-success' : 'btn-outline-secondary' }}"
                           style="width: 36px; height: 36px;">{{ $index + 1 }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
