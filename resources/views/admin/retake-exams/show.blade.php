@extends('layouts.app')

@section('title', 'Имтиҳони такрорӣ — ' . $retakeExam->title)
@section('page-header', 'Имтиҳони такрорӣ')
@section('page-description', $retakeExam->subject->name ?? 'Фан' . ' | ' . $retakeExam->semester->name ?? '')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">{{ $retakeExam->title }}</h6>
                    <small class="text-muted">
                        {{ $retakeExam->subject->name ?? '-' }} |
                        {{ $retakeExam->semester->name ?? '-' }} |
                        {{ $retakeExam->exam_date?->format('d.m.Y') ?? '-' }}
                    </small>
                </div>
                <div>
                    <a href="{{ route('admin.retake-exams.vedomost', $retakeExam) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Ведомост
                    </a>
                    <a href="{{ route('admin.retake-exams.print-vedomost', $retakeExam) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="bi bi-printer me-1"></i> Чоп
                    </a>
                    <form method="POST" action="{{ route('admin.retake-exams.destroy', $retakeExam) }}" class="d-inline" onsubmit="return confirm('Имтиҳони такрорӣ нест карда шавад?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-trash me-1"></i> Нест кардан
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Формат:</strong> {{ match($retakeExam->format) {
                            'online_test' => 'Онлайн',
                            'written' => 'Хаттӣ',
                            'oral' => 'Даҳонӣ',
                            'mixed' => 'Омехта',
                            default => $retakeExam->format,
                        } }}
                    </div>
                    <div class="col-md-3">
                        <strong>Даврият:</strong> {{ $retakeExam->duration_minutes }} дақ.
                    </div>
                    <div class="col-md-3">
                        <strong>Ҳадди ақал:</strong> {{ $retakeExam->passing_score }}%
                    </div>
                    <div class="col-md-3">
                        <strong>Ҳолат:</strong>
                        @php
                        $statusBadge = match($retakeExam->status) {
                            'draft' => 'bg-secondary',
                            'scheduled' => 'bg-info',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ $retakeExam->status }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Тавсеа:</strong>
                        @if($retakeExam->retake_type === 'fx')
                            <span class="badge bg-danger">Fx (45-49%)</span>
                        @elseif($retakeExam->retake_type === 'f')
                            <span class="badge bg-dark">F (0-44%)</span>
                        @else
                            <span class="badge bg-secondary">—</span>
                        @endif
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-1"></i>
                    Саволнома аз <strong>имтиҳони асосӣ</strong> гирифта шудааст.
                    @if($retakeExam->mainExam)
                        <br>Имтиҳони асосӣ: <strong>{{ $retakeExam->mainExam->title ?? '-' }}</strong>
                        ({{ $retakeExam->mainExam->examQuestions()->count() }} савол)
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Донишҷӯ</th>
                                <th>Гурӯҳ</th>
                                <th>Баҳои аслӣ</th>
                                <th>Кӯшиш</th>
                                <th>Натиҷа</th>
                                <th>Баҳо</th>
                                <th>Ҳолат</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($retakeExam->retakeExamStudents as $student)
                            @php
                                $studentAttempts = $attempts[$student->student_id] ?? collect();
                                $latestAttempt = $studentAttempts->sortByDesc('attempt_number')->first();
                                $originalGrade = $student->academicDebt->semesterGrade->letter_grade ?? '-';
                                $originalScore = $student->academicDebt->semesterGrade->total_score ?? '-';
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student->student->user?->short_name ?? 'Донишҷӯ #' . $student->student->id }}</td>
                                <td>{{ $student->student->group?->name ?? '-' }}</td>
                                <td>
                                    {{ $originalGrade }}
                                    ({{ $originalScore !== '-' ? number_format($originalScore, 2) : '-' }})
                                </td>
                                <td>
                                    {{ $student->attempt_number }}/{{ $retakeExam->max_attempts }}
                                </td>
                                <td>
                                    @if($latestAttempt && $latestAttempt->percentage !== null)
                                        <strong>{{ $latestAttempt->letter_grade }}</strong>
                                        <br><span class="text-muted">{{ number_format($latestAttempt->total_score, 2) }} / {{ number_format($latestAttempt->max_possible_score, 2) }}</span>
                                        <small class="text-muted">({{ number_format($latestAttempt->percentage, 2) }}%)</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($latestAttempt && $latestAttempt->percentage !== null)
                                        <strong>{{ number_format($latestAttempt->total_score, 2) }}</strong>
                                        <small class="text-muted">({{ number_format($latestAttempt->percentage, 2) }}%)</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $statusBadge = match($student->status) {
                                            'pending' => 'bg-warning',
                                            'passed' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'absent' => 'bg-secondary',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $student->status }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.retake-exams.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
