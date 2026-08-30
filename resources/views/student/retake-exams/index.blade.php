@extends('layouts.app')

@section('title', 'Имтиҳонҳои такрорӣ')
@section('page-header', 'Имтиҳонҳои такрорӣ')
@section('page-description', 'Имтиҳонҳои такроре, ки ба шумо таъин шудаанд')

@section('content')
<div class="row g-4">
    @if(!$retakeExams->isEmpty())
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Имтиҳонҳои такрорӣ</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($retakeExams as $exam)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="badge bg-warning text-dark">Имтиҳони такрорӣ</span>
                                    <span class="badge bg-{{ $exam->status === 'scheduled' ? 'info' : 'success' }}">
                                        {{ $exam->status === 'scheduled' ? 'Интизорӣ' : ($exam->status === 'completed' ? 'Анҷомшуда' : $exam->status) }}
                                    </span>
                                </div>
                                <h6 class="card-title">{{ $exam->title }}</h6>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-book me-1"></i> {{ $exam->subject->name ?? 'Фан номаълум' }}
                                </p>
                                <ul class="list-unstyled small text-muted">
                                    <li><i class="bi bi-calendar me-1"></i> {{ $exam->exam_date?->format('d.m.Y') ?? '-' }}</li>
                                    <li><i class="bi bi-clock me-1"></i> {{ $exam->duration_minutes }} дақиқа</li>
                                    <li><i class="bi bi-bullseye me-1"></i> Ҳадди гузариш: {{ $exam->passing_score }}%</li>
                                </ul>
                            </div>
                            <div class="card-footer bg-white border-0">
                                @php
                                    $examAttempts = $attempts[$exam->id] ?? collect();
                                    $activeAttempt = $examAttempts->where('status', 'in_progress')->first();
                                    $canStart = $examAttempts->count() < $exam->max_attempts && ($exam->status === 'scheduled' || $exam->status === 'active');
                                @endphp
                                @if($activeAttempt)
                                <a href="{{ route('student.retake-exams.take', [$exam, $activeAttempt]) }}" class="btn btn-warning btn-sm w-100">
                                    <i class="bi bi-play-fill me-1"></i> Давом додан
                                </a>
                                @elseif($canStart)
                                <form method="POST" action="{{ route('student.retake-exams.start', $exam) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm w-100"
                                            onclick="return confirm('Имтиҳони такрорӣ-ро оғоз мекунед? Вақт: {{ $exam->duration_minutes }} дақиқа.')">
                                        <i class="bi bi-play-circle me-1"></i> Оғоз кардан
                                    </button>
                                </form>
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
            </div>
        </div>
    </div>
    @endif

    @if($retakeExams->isEmpty())
    <div class="col-12">
        <div class="text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <p class="text-muted">Ҳоло имтиҳони такрорӣ дастрас нест.</p>
        </div>
    </div>
    @endif
</div>
@endsection
