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
{{-- Профили донишҷӯ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-column flex-sm-row align-items-center gap-3">
        @include('partials.avatar', ['user' => auth()->user(), 'size' => 96])
        <div class="text-center text-sm-start">
            <h5 class="mb-1">{{ $student->full_name }}</h5>
            <p class="text-muted mb-2">
                {{ $student->student_id_number ?? '—' }}
                @if($student->group) | {{ $student->group->full_name }} @endif
                @if($student->specialty) | {{ $student->specialty->name }} @endif
            </p>
            <a href="{{ route('student.profile') }}" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-person-circle me-1"></i> Профили ман
            </a>
        </div>
    </div>
</div>

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
                    <h3 class="mb-0">{{ $student->earned_credits }}</h3>
                    <small class="text-muted">Кредитҳо</small>
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
                    <h3 class="mb-0">{{ $attendance_percentage === null ? '—' : number_format($attendance_percentage, 0) . '%' }}</h3>
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
    <div class="col-12 col-12">
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
</div>
@endif
@endsection