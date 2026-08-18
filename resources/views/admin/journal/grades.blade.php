@extends('layouts.app')

@section('title', 'Баҳоҳои ҷорӣ')
@section('page-header', 'Баҳоҳои ҷорӣ')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }}
@endsection

@section('content')
    {{-- Формаи сабти баҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Сабти баҳои нав</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.journal.grades.store', $subjectAssignment) }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Сана</label>
                        <input type="date" name="grade_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ҳафта №</label>
                        <input type="number" name="week_number" class="form-control" min="1" max="18" value="{{ request('week', 1) }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Навъи баҳо</label>
                        <select name="grade_type" class="form-select" required>
                            <option value="classwork">Кори синфӣ</option>
                            <option value="homework">Вазифаи хонагӣ</option>
                            <option value="quiz">Тести кӯтоҳ</option>
                            <option value="lab_work">Кори лабораторӣ</option>
                            <option value="presentation">Презентатсия</option>
                            <option value="project">Лоиҳа</option>
                            <option value="other">Дигар</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Балли максималӣ</label>
                        <input type="number" name="max_score" class="form-control" value="100" min="1" max="100" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th width="5%">#</th>
                                <th width="55%">Донишҷӯ</th>
                                <th width="20%">Балл</th>
                                <th width="20%">Миёна (аз 100)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php
                                    $studentGrades = $grades[$student->id] ?? collect();
                                    $avg = $studentGrades->isEmpty() ? 0 : $studentGrades->avg(fn($g) => ($g->score / max($g->max_score, 1)) * 100);
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->user?->full_name }}</td>
                                    <td>
                                        <input type="number" name="grades[{{ $student->id }}]"
                                               class="form-control form-control-sm" min="0" max="100" step="0.5"
                                               placeholder="0-100">
                                    </td>
                                    <td>
                                        <span class="{{ $avg >= 50 ? 'text-success' : 'text-danger' }}">
                                            {{ number_format($avg, 1) }}
                                        </span>
                                        <small class="text-muted">({{ $studentGrades->count() }} баҳо)</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабт кардан
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Баҳоҳои мавҷуда --}}
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
                                <td class="student-name text-start">{{ $student->user?->short_name }}</td>
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
            <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт ба журнал
            </a>
        </div>
    </div>
@endsection
