@extends('layouts.app')
@section('title', 'Баҳоҳои ман')
@section('page-header', 'Баҳоҳои ман')
@section('page-description', 'Натиҷаҳои таҳсил дар ҳамаи семестрҳо')

@section('content')
@forelse($grades as $semesterId => $semGrades)
@php $sem = $semGrades->first()?->semester; @endphp
<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calendar me-2"></i> {{ $sem?->name ?? "Семестр #{$semesterId}" }}</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Фан</th>
                        <th class="text-center">R1</th>
                        <th class="text-center">R2</th>
                        <th class="text-center">Имтиҳон</th>
                        <th class="text-center">Ниҳоӣ</th>
                        <th class="text-center">Баҳо</th>
                        <th class="text-center">Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($semGrades as $grade)
                    <tr>
                        <td>{{ $grade->subjectAssignment?->subject?->name ?? '—' }}</td>
                        <td class="text-center">{{ $grade->rating1_score !== null ? number_format($grade->rating1_score, 0) : '—' }}</td>
                        <td class="text-center">{{ $grade->rating2_score !== null ? number_format($grade->rating2_score, 0) : '—' }}</td>
                        <td class="text-center">{{ $grade->exam_score !== null ? number_format($grade->exam_score, 0) : '—' }}</td>
                        <td class="text-center"><strong>{{ $grade->total_score !== null ? number_format($grade->total_score, 0) : '—' }}</strong></td>
                        <td class="text-center">
                            @if($grade->letter_grade)
                            @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                            <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                            @else — @endif
                        </td>
                        <td class="text-center">
                            @php
                            $statusLabel = match($grade->status) {
                            'passed' => ['Гузашт', 'success'],
                            'failed' => ['Нагузашт', 'danger'],
                            'retake' => ['Такрорсупорӣ', 'warning'],
                            'in_progress' => ['Дар ҷараён', 'info'],
                            default => [$grade->status, 'secondary'],
                            };
                            @endphp
                            <span class="badge bg-{{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@empty
<div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
    <p>Баҳое ҳоло мавҷуд нест.</p>
</div>
@endforelse
@endsection