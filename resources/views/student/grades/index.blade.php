@extends('layouts.app')
@section('title', 'Баҳоҳои ман')
@section('page-header', 'Баҳоҳои ман')
@section('page-description', 'Натиҷаҳои таҳсил дар ҳамаи семестрҳо')

@section('content')
@forelse($grades as $semesterId => $semGrades)
@php $sem = $semGrades->first()['semester'] ?? null; @endphp
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
                    @foreach($semGrades as $item)
                    @php
                        $grade = $item['semester_grade'];
                        $calc = $item;
                    @endphp
                    <tr>
                        <td>{{ $item['subject']?->name ?? '—' }}</td>
                        <td class="text-center">{{ $calc['rating1'] !== null ? number_format($calc['rating1'], 0) : '—' }}</td>
                        <td class="text-center">{{ $calc['rating2'] !== null ? number_format($calc['rating2'], 0) : '—' }}</td>
                        <td class="text-center">{{ $calc['exam'] !== null ? number_format($calc['exam'], 0) : '—' }}</td>
                        <td class="text-center"><strong>{{ $calc['total_score'] !== null ? number_format($calc['total_score'], 1) : '—' }}</strong></td>
                        <td class="text-center">
                            @if($calc['letter_grade'])
                            @php $g = \App\Enums\GradeScale::tryFrom($calc['letter_grade']); @endphp
                            <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $calc['letter_grade'] }}</span>
                            @else — @endif
                        </td>
                        <td class="text-center">
                            @php
                            $statusLabel = match($calc['status']) {
                                'passed' => ['Гузашт', 'success'],
                                'failed' => ['Нагузашт', 'danger'],
                                'retake' => ['Такрорсупорӣ', 'warning'],
                                'in_progress' => ['Дар ҷараён', 'info'],
                                default => [$calc['status'] ?? '—', 'secondary'],
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
