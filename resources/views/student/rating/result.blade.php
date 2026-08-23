@extends('layouts.app')

@section('title', 'Натиҷаи рейтинг')
@section('page-header', 'Натиҷаи рейтинг')
@section('page-description', 'Давом ва натиҷаи тести онлайн')

@section('content')
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">{{ $attempt->subject?->name }}</h5>
                <small class="text-muted">
                    Кӯшиши {{ $attempt->attempt_number }} аз {{ $session->max_attempts }}
                    | {{ $attempt->finished_at?->format('d.m.Y H:i') }}
                </small>
            </div>
            <div class="text-end">
                @php
                    $score = $attempt->correct_count ?? 0;
                    $total = $attempt->total_questions ?? 0;
                    $pct = $attempt->percentage ?? 0;
                    $grade = \App\Enums\GradeScale::fromPercentage($pct);
                @endphp
                <div class="fs-3 fw-bold {{ $pct >= 50 ? 'text-success' : 'text-danger' }}">
                    {{ $pct }}%
                </div>
                <div class="text-muted small">
                    Дуруст: {{ $score }} / {{ $total }} савол
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0">Саволҳо</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="text-center">#</th>
                        <th>Савол</th>
                        <th>Ҷавоби шумо</th>
                        <th>Ҷавооби дуруст</th>
                        <th class="text-center">Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($questions as $i => $q)
                        @php
                            $selectedId = $attempt->answers_json['answers'][$q->id] ?? null;
                            $correctOption = $q->answerOptions->firstWhere('is_correct', true);
                            $isCorrect = $selectedId && $correctOption && (int) $selectedId === (int) $correctOption->id;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $i + 1 }}</td>
                            <td>{{ $q->question_text }}</td>
                            <td>
                                @if($selectedId)
                                    {{ $q->answerOptions->firstWhere('id', $selectedId)?->option_text ?? '—' }}
                                @else
                                    <span class="text-muted">Ҷавоб дода нашуда</span>
                                @endif
                            </td>
                            <td>
                                {{ $correctOption?->option_text ?? '—' }}
                            </td>
                            <td class="text-center">
                                @if($isCorrect)
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                @else
                                    <i class="bi bi-x-circle-fill text-danger fs-5"></i>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between">
    <a href="{{ route('student.rating.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Ба рӯйхат
    </a>
    @if($used < $session->max_attempts)
        <a href="{{ route('student.rating.start', [$session, $attempt->subject]) }}" class="btn btn-primary">
            <i class="bi bi-arrow-repeat me-1"></i> Кӯшиши дигар
        </a>
    @endif
</div>
@endsection
