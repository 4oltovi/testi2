@extends('layouts.app')

@section('title', 'Тестҳои ман')
@section('page-header', 'Тестҳои дастрас')
@section('page-description', 'Имтиҳонҳои онлайни гурӯҳи шумо')

@section('content')
    @if($exams->isEmpty())
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted">Ҳоло тести дастрасе нест.</p>
            <small class="text-muted">
                Агар админ имтиҳон сохта бошад, бояд онро <strong>нашр</strong> кунад.<br>
                Шумо танҳо имтиҳонҳои онлайни гурӯҳи худро мебинед.
            </small>
        </div>
    @else
        <div class="row g-3">
            @foreach($exams as $exam)
                @php
                    $examAttempts = $attempts[$exam->id] ?? collect();
                    $completedAttempts = $examAttempts->whereIn('status', ['submitted', 'auto_submitted', 'graded']);
                    $activeAttempt = $examAttempts->where('status', 'in_progress')->first();
                    $canStart = $examAttempts->count() < $exam->max_attempts && $exam->status === 'active';
                    $isAvailable = (!$exam->starts_at || now()->gte($exam->starts_at)) && (!$exam->ends_at || now()->lte($exam->ends_at));
                @endphp
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge bg-{{ $exam->status === 'active' ? 'success' : 'info' }}">
                                    {{ $exam->status === 'active' ? 'Фаъол' : ($exam->status === 'scheduled' ? 'Интизорӣ' : $exam->status) }}
                                </span>
                                <span class="badge bg-light text-dark">{{ $exam->exam_type?->label() ?? $exam->exam_type }}</span>
                            </div>
                            <h6 class="card-title">{{ $exam->title }}</h6>
                            <p class="text-muted small mb-2">
                                <i class="bi bi-book me-1"></i> {{ $exam->subjectAssignment?->subject?->name ?? 'Фан номаълум' }}
                            </p>
                            <ul class="list-unstyled small text-muted">
                                <li><i class="bi bi-clock me-1"></i> {{ $exam->duration_minutes }} дақиқа</li>
                                <li><i class="bi bi-list-ol me-1"></i> {{ $exam->total_questions_count }} савол</li>
                                <li><i class="bi bi-bullseye me-1"></i> Ҳадди гузариш: {{ $exam->passing_score }}%</li>
                                <li><i class="bi bi-arrow-repeat me-1"></i> Кӯшиш: {{ $examAttempts->count() }}/{{ $exam->max_attempts }}</li>
                            </ul>

                            @if($completedAttempts->isNotEmpty())
                                @php $best = $completedAttempts->sortByDesc('percentage')->first(); @endphp
                                <div class="alert alert-{{ $best->percentage >= $exam->passing_score ? 'success' : 'danger' }} py-1 px-2 small mb-2">
                                    Беҳтарин натиҷа: <strong>{{ number_format($best->percentage, 1) }}%</strong>
                                    ({{ $best->letter_grade }})
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-white border-0">
                            @if($activeAttempt)
                                <a href="{{ route('student.exams.take', [$exam, $activeAttempt]) }}" class="btn btn-warning btn-sm w-100">
                                    <i class="bi bi-play-fill me-1"></i> Давом додан
                                </a>
                            @elseif($canStart && $isAvailable)
                                <form method="POST" action="{{ route('student.exams.start', $exam) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100"
                                            onclick="return confirm('Тестро оғоз мекунед? Вақт: {{ $exam->duration_minutes }} дақиқа.')">
                                        <i class="bi bi-play-circle me-1"></i> Оғоз кардан
                                    </button>
                                </form>
                            @elseif(!$isAvailable && $exam->starts_at && now()->lt($exam->starts_at))
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    Оғоз: {{ $exam->starts_at->format('d.m.Y H:i') }}
                                </button>
                            @else
                                <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                    Кӯшишҳо тамом
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
