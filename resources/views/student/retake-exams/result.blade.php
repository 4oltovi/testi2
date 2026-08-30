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
                                    {{ number_format($attempt->percentage, 1) }}%
                                </div>
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
                    @endphp
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <p class="mb-1">{{ $question->question_text ?? 'Савол #' . $question->id }}</p>
                                    <small class="text-muted">
                                        Ҷавоб: {{ $answer->selected_options ?? $answer->text_answer ?? '-' }}
                                    </small>
                                </div>
                                <span class="badge bg-{{ $answer->is_correct ? 'success' : 'danger' }}">
                                    {{ $answer->is_correct ? 'Дуруст' : 'Нодуруст' }}
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
