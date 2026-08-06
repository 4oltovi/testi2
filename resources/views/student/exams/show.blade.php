@extends('layouts.app')

@section('title', $exam->title)
@section('page-header', $exam->title)
@section('page-description', 'Имтиҳон')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Маълумоти имтиҳон</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Фан</th>
                            <td>{{ $exam->subjectAssignment->subject->name ?? $exam->subject_name ?? '' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Давомнокӣ</th>
                            <td>{{ $exam->duration_minutes }} дақиқа</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Шумораи саволҳо</th>
                            <td>{{ $exam->questions_count ?? $exam->questions->count() ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳадди гузариш</th>
                            <td>{{ $exam->passing_score }}%</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кӯшишҳо</th>
                            <td>{{ $attemptsCount }} / {{ $exam->max_attempts }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Last attempt result --}}
        @if($lastAttempt && $lastAttempt->is_graded)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-clipboard-check"></i> Натиҷаи охирин</h5>
                </div>
                <div class="card-body text-center">
                    @php
                        $percentage = $lastAttempt->score_percentage ?? $lastAttempt->percentage ?? 0;
                        $passed = $percentage >= $exam->passing_score;
                    @endphp
                    <div class="display-4 fw-bold {{ $passed ? 'text-success' : 'text-danger' }}">
                        {{ number_format($percentage, 1) }}%
                    </div>
                    <p class="mt-2">
                        @if($passed)
                            <span class="badge bg-success fs-6">Гузашт</span>
                        @else
                            <span class="badge bg-danger fs-6">Нагузашт</span>
                        @endif
                    </p>
                    @if($lastAttempt->grade)
                        <p class="text-muted">Баҳо: <strong>{{ $lastAttempt->grade }}</strong></p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Start button or message --}}
        <div class="card">
            <div class="card-body text-center py-4">
                @if($canAttempt)
                    <p class="text-muted mb-3">Барои оғози имтиҳон тугмаро пахш кунед</p>
                    <form action="{{ route('student.exams.start', $exam) }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-success btn-lg px-5">
                            <i class="bi bi-play-circle"></i> Оғоз кардан
                        </button>
                    </form>
                @else
                    <div class="text-warning">
                        <i class="bi bi-exclamation-triangle fs-1"></i>
                        <p class="mt-2 mb-0">
                            @if($attemptsCount >= $exam->max_attempts)
                                Шумо тамоми кӯшишҳоро истифода кардед.
                            @else
                                Вақти имтиҳон ба анҷом расидааст.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
