@extends('layouts.app')

@section('title', 'Transcript: ' . ($student->user?->full_name ?? 'Донишҷӯ'))
@section('page-header', 'Transcript')
@section('page-description', ($student->user?->full_name ?? '-') . ' | ' . ($student->student_id_number ?? $student->id))

@section('page-actions')
{{-- Тугмаи сохтани Transcript --}}
<form action="{{ route('admin.transcript.generate', $student) }}" method="POST" class="d-inline">
    @csrf
    <button type="submit" class="btn btn-success">
        <i class="bi bi-file-earmark-pdf me-1"></i> Сохтани Transcript
    </button>
</form>

{{-- Тугмаи чопи PDF (намунаи нав — айнан мисли намунаи Excel) --}}
<a href="{{ url('admin/transcript/student/' . $student->id . '/print') }}" target="_blank"
    class="btn btn-info">
    <i class="bi bi-printer me-1"></i> 🖨 Чопи PDF
</a>

{{-- Тугмаи бозгашт --}}
<a href="{{ route('admin.transcript.index') }}" class="btn btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Бозгашт
</a>
@endsection

@section('content')
{{-- Маълумоти донишҷӯ --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-primary bg-opacity-10 text-center p-3">
            <h2 class="text-primary mb-0">{{ number_format($student->cumulative_gpa ?? 0, 2) }}</h2>
            <small>GPA Кумулятивӣ</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-success bg-opacity-10 text-center p-3">
            <h2 class="text-success mb-0">{{ $totalCreditsEarned ?? 0 }}</h2>
            <small>Кредит гирифта / {{ $totalCreditsRequired ?? 0 }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 text-center p-3">
            <h2 class="text-info mb-0">{{ optional($student->semesterGrades)->where('status', 'passed')->count() ?? 0 }}</h2>
            <small>Фанҳои гузашта</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 {{ ($honors ?? 'none') !== 'none' ? 'bg-warning bg-opacity-10' : 'bg-light' }} text-center p-3">
            <h5 class="mb-0">{{ $honorsLabel ?? '—' }}</h5>
            <small>Ифтихор</small>
        </div>
    </div>
</div>

{{-- Маълумоти умумии донишҷӯ --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-person me-2"></i> Маълумоти донишҷӯ</h6>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-6"><strong>Ному насаб:</strong> {{ $student->user?->full_name ?? '-' }}</div>
            <div class="col-md-6"><strong>ID донишҷӯ:</strong> {{ $student->student_id_number ?? $student->id }}</div>
            <div class="col-md-6"><strong>Ихтисос:</strong> {{ $student->specialty?->name ?? '-' }}</div>
            <div class="col-md-6"><strong>Гурӯҳ:</strong> {{ $student->group?->name ?? '-' }}</div>
            <div class="col-md-6"><strong>Факулта:</strong> {{ optional(optional(optional($student->specialty)->department)->faculty)->name ?? '-' }}</div>
            <div class="col-md-6">
                <strong>Шӯъба:</strong>
                @php
                $form = $student->study_form ?? 'full_time';
                $formLabel = match($form) {
                'full_time' => 'рӯзона',
                'part_time' => 'ғоибона',
                'evening' => 'шомина',
                default => $form,
                };
                @endphp
                {{ $formLabel }}
            </div>
        </div>
    </div>
</div>

{{-- Баҳоҳо аз рӯйи семестр --}}
@if(isset($gradesBySemester) && $gradesBySemester->isNotEmpty())
@foreach($gradesBySemester as $semId => $grades)
@php $sem = $grades->first()?->semester; @endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-calendar3 me-2"></i>
            {{ $sem?->name ?? ('Семестр ' . $semId) }}
            @if($sem?->academicYear)
            <small class="text-muted">({{ $sem->academicYear->name }})</small>
            @endif
        </h6>
        @php
        $semGpa = optional($student->semesterGpas)->where('semester_id', $semId)->first();
        @endphp
        @if($semGpa)
        <span class="badge bg-primary">GPA: {{ number_format($semGpa->gpa, 2) }}</span>
        @endif
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Фан</th>
                    <th class="text-center">Кредит</th>
                    <th class="text-center">Ниҳоӣ (%)</th>
                    <th class="text-center">Баҳо</th>
                    <th class="text-center">GP</th>
                    <th class="text-center">Анъанавӣ</th>
                    <th class="text-center">Ҳолат</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grades as $grade)
                <tr>
                    <td>{{ $grade->curriculum?->subject?->name ?? '-' }}</td>
                    <td class="text-center">{{ $grade->curriculum?->credits ?? '-' }}</td>
                    <td class="text-center">
                        {{ $grade->total_score !== null ? number_format($grade->total_score, 1) : '—' }}
                    </td>
                    <td class="text-center">
                        @if($grade->letter_grade)
                        @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                        <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">
                            {{ $grade->letter_grade }}
                        </span>
                        @else
                        —
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $grade->grade_point !== null ? number_format($grade->grade_point, 2) : '—' }}
                    </td>
                    <td class="text-center">{{ $grade->traditional_grade ?? '—' }}</td>
                    <td class="text-center">
                        @if(method_exists($grade, 'isPassed') && $grade->isPassed())
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
@else
<div class="text-center text-muted py-5">
    <i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>
    Баҳоҳои тасдиқшуда мавҷуд нестанд.
</div>
@endif
@endsection