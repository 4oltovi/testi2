@extends('layouts.app')

@section('title', 'Панели донишҷӯ')
@section('page-header', 'Панели асосӣ')
@section('page-description')
Хуш омадед, {{ auth()->user()->first_name }}!
@if($student) | {{ $student->group?->name }} | {{ $student->course?->name }} @endif
@endsection

@section('content')
@if(!$student)
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Профили донишҷӯ ёфт нашуд. Бо администратор тамос гиред.
</div>
@else
{{-- Карточкаҳо --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                    <i class="bi bi-trophy fs-4 text-primary"></i>
                </div>
                <div>
                    <h3 class="mb-0 {{ $gpa >= 3.0 ? 'text-success' : ($gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                        {{ number_format($gpa, 2) }}
                    </h3>
                    <small class="text-muted">GPA кумулятивӣ</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i class="bi bi-mortarboard fs-4 text-success"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ $student->total_credits_earned }}</h3>
                    <small class="text-muted">Кредитҳо / {{ $student->specialty?->total_credits ?? '—' }}</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                    <i class="bi bi-check2-square fs-4 text-info"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ number_format($attendance_percentage, 0) }}%</h3>
                    <small class="text-muted">Давомот</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100 {{ $debts_count > 0 ? 'border-danger border-2' : '' }}">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle {{ $debts_count > 0 ? 'bg-danger' : 'bg-secondary' }} bg-opacity-10 p-3 me-3">
                    <i class="bi bi-exclamation-triangle fs-4 {{ $debts_count > 0 ? 'text-danger' : 'text-secondary' }}"></i>
                </div>
                <div>
                    <h3 class="mb-0 {{ $debts_count > 0 ? 'text-danger' : '' }}">{{ $debts_count }}</h3>
                    <small class="text-muted">Қарздориҳо</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Баҳоҳо дар ин семестр --}}
<div class="row g-3">
    <div class="col-12 col-lg-7">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Баҳоҳо дар {{ $semester?->name ?? 'семестри ҷорӣ' }}</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Фан</th>
                                <th>R1</th>
                                <th>R2</th>
                                <th>Имтиҳон</th>
                                <th>Ниҳоӣ</th>
                                <th>Баҳо</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($grades as $grade)
                            <tr>
                                <td>{{ $grade->subject?->name }}</td>
                                <td>{{ $grade->rating1_score !== null ? number_format($grade->rating1_score, 0) : '—' }}</td>
                                <td>{{ $grade->rating2_score !== null ? number_format($grade->rating2_score, 0) : '—' }}</td>
                                <td>{{ $grade->exam_score !== null ? number_format($grade->exam_score, 0) : '—' }}</td>
                                <td><strong>{{ $grade->total_score !== null ? number_format($grade->total_score, 0) : '—' }}</strong></td>
                                <td>
                                    @if($grade->letter_grade)
                                    @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                                    <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                                    @else — @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">Баҳое ҳоло нест.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Натиҷаҳои тестҳо --}}
    <div class="col-12 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Тестҳои охирин</h6>
            </div>
            <div class="card-body p-0">
                @if($recent_exams->isEmpty())
                <div class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    <small>Тест ҳоло супорида нашудааст.</small>
                </div>
                @else
                <div class="list-group list-group-flush">
                    @foreach($recent_exams as $ea)
                    @php $passed = $ea->percentage >= ($ea->exam?->passing_score ?? 50); @endphp
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="d-block">{{ $ea->exam?->subjectAssignment?->subject?->name ?? 'Тест' }}</strong>
                                <small class="text-muted">{{ $ea->submitted_at?->format('d.m.Y H:i') }}</small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-{{ $passed ? 'success' : 'danger' }} fs-6">
                                    {{ number_format($ea->percentage, 0) }}%
                                </span>
                                <br><small>{{ $ea->letter_grade }}</small>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endif
@endsection