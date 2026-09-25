@extends('layouts.app')
@section('title', 'Баҳоҳои ' . $academicYear->name)
@section('page-header', 'Баҳоҳои ' . $academicYear->name)
@section('page-description', 'Семестрро интихоб кунед')

@section('content')
    <div class="mb-3">
        <a href="{{ route('student.grades.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 10px;">
            <i class="bi bi-arrow-left me-1"></i> Бозгашт
        </a>
    </div>

    @forelse($semesters as $semester)
        @php $semGrades = $semesterGrades[$semester->id] ?? null; @endphp
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-calendar me-2"></i> {{ $semester->name }}</h6>
            </div>
            <div class="card-body p-0">
                @if($semGrades && $semGrades['grades']->isEmpty())
                    <div class="text-center py-4 text-muted small">Дар ин семестр баҳо сабт нашудааст.</div>
                @elseif($semGrades)
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
                                @foreach($semGrades['grades'] as $item)
                                @php
                                    $calc = $item;
                                @endphp
                                <tr>
                                    <td>{{ $item['subject']?->name ?? '—' }}</td>
                                    <td class="text-center">
                                        @if($calc['rating1'] !== null)
                                            {{ number_format($calc['rating1'], 0) }}
                                        @else — @endif
                                    </td>
                                    <td class="text-center">
                                        @if($calc['rating2'] !== null)
                                            {{ number_format($calc['rating2'], 0) }}
                                        @else — @endif
                                    </td>
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
                @endif
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
            <p>Баҳое ҳоло мавҷуд нест.</p>
        </div>
    @endforelse
@endsection