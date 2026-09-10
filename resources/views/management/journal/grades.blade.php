@extends('layouts.app')

@section('title', 'Баҳоҳои ҷорӣ')
@section('page-header', 'Баҳоҳои ҷорӣ')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
        </div>
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
                <tr>
                    <th class="text-muted">Фан:</th>
                    <td>{{ $subjectAssignment->subject?->name }}</td>
                    <th class="text-muted ms-4">Гурӯҳ:</th>
                    <td>{{ $subjectAssignment->group?->name }}</td>
                </tr>
                <tr>
                    <th class="text-muted">Омӯзгор:</th>
                    <td>{{ $subjectAssignment->teacher?->short_name ?? '—' }}</td>
                    <th class="text-muted ms-4">Навъ:</th>
                    <td>
                        @php
                            $typeLabel = match($subjectAssignment->lesson_type) {
                                'lecture' => 'Лексия',
                                'practice' => 'Амалӣ',
                                'lab' => 'Лабораторӣ',
                                default => $subjectAssignment->lesson_type,
                            };
                        @endphp
                        {{ $typeLabel }}
                    </td>
                </tr>
                <tr>
                    <th class="text-muted">Семестр:</th>
                    <td>{{ $semester->name }}</td>
                    <th class="text-muted ms-4">Кредит:</th>
                    <td>{{ $subjectAssignment->credits }}</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Баҳоҳои сабтшуда</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm journal-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="student-name">#</th>
                            <th class="student-name">Донишҷӯ</th>
                            @for($week = 1; $week <= 16; $week++)
                                <th title="Ҳафтаи {{ $week }}">{{ $week }}</th>
                            @endfor
                            <th title="Миёна аз 100">Миёна</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $studentGrades = $grades[$student->id] ?? collect();
                                $weeklyAvg = $studentGrades->groupBy('week_number')->map(fn($wg) => $wg->avg(fn($g) => ($g->score / max($g->max_score, 1)) * 100));
                                $totalAvg = $studentGrades->isEmpty() ? 0 : $studentGrades->avg(fn($g) => ($g->score / max($g->max_score, 1)) * 100);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="student-name text-start">
                                    <a href="{{ route('management.students.show', $student) }}" class="text-decoration-none">
                                        {{ $student->user?->short_name }}
                                    </a>
                                </td>
                                @for($week = 1; $week <= 16; $week++)
                                    <td>
                                        @if(isset($weeklyAvg[$week]))
                                            <span class="{{ $weeklyAvg[$week] >= 50 ? 'text-success' : 'text-danger' }}" title="{{ number_format($weeklyAvg[$week], 1) }}%">
                                                {{ round($weeklyAvg[$week]) }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endfor
                                <td>
                                    <strong class="{{ $totalAvg >= 50 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($totalAvg, 0) }}
                                    </strong>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <a href="{{ route('management.journal.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт ба журнал
            </a>
        </div>
    </div>
@endsection
