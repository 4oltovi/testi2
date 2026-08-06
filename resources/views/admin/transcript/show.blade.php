@extends('layouts.app')

@section('title', 'Transcript: ' . $student->user?->full_name)
@section('page-header', 'Transcript')
@section('page-description', $student->user?->full_name . ' | ' . $student->student_id_number)

@section('page-actions')
    <form action="{{ route('admin.transcript.generate', $student) }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-success"><i class="bi bi-file-earmark-pdf me-1"></i> Сохтани Transcript</button>
    </form>
@endsection

@section('content')
    {{-- Маълумоти донишҷӯ --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
                <h2 class="text-primary mb-0">{{ number_format($student->cumulative_gpa, 2) }}</h2>
                <small>GPA Кумулятивӣ</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10 text-center p-3">
                <h2 class="text-success mb-0">{{ $totalCreditsEarned }}</h2>
                <small>Кредит гирифта / {{ $totalCreditsRequired }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info bg-opacity-10 text-center p-3">
                <h2 class="text-info mb-0">{{ $student->semesterGrades->where('status', 'passed')->count() }}</h2>
                <small>Фанҳои гузашта</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 {{ $honors !== 'none' ? 'bg-warning bg-opacity-10' : 'bg-light' }} text-center p-3">
                <h5 class="mb-0">{{ $honorsLabel ?: '—' }}</h5>
                <small>Ифтихор</small>
            </div>
        </div>
    </div>

    {{-- Баҳоҳо аз рӯйи семестр --}}
    @foreach($gradesBySemester as $semId => $grades)
        @php $sem = $grades->first()?->semester; @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between">
                <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i> {{ $sem?->name }} ({{ $sem?->academicYear?->name }})</h6>
                @php
                    $semGpa = $student->semesterGpas->where('semester_id', $semId)->first();
                @endphp
                @if($semGpa)
                    <span class="badge bg-primary">GPA: {{ number_format($semGpa->gpa, 2) }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Фан</th><th>Кредит</th><th>Ниҳоӣ (%)</th><th>Баҳо</th><th>GP</th><th>Анъанавӣ</th><th>Ҳолат</th></tr>
                    </thead>
                    <tbody>
                        @foreach($grades as $grade)
                            <tr>
                                <td>{{ $grade->curriculum?->subject?->name }}</td>
                                <td>{{ $grade->curriculum?->credits }}</td>
                                <td>{{ $grade->total_score !== null ? number_format($grade->total_score, 1) : '—' }}</td>
                                <td>
                                    @if($grade->letter_grade)
                                        @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                                        <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                                    @endif
                                </td>
                                <td>{{ $grade->grade_point !== null ? number_format($grade->grade_point, 2) : '—' }}</td>
                                <td>{{ $grade->traditional_grade ?? '—' }}</td>
                                <td>
                                    @if($grade->isPassed())
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-x-circle-fill text-danger"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach

    @if($gradesBySemester->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
            Баҳоҳои тасдиқшуда мавҷуд нестанд.
        </div>
    @endif
@endsection
