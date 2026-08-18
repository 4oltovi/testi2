@extends('layouts.app')

@section('title', 'Баҳоҳои ҷорӣ')
@section('page-header', 'Баҳоҳои ҷорӣ')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    {{-- Сабти баҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Сабти баҳои нав</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('teacher.journal.grades.store', $subjectAssignment) }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-2">
                        <label class="form-label">Сана</label>
                        <input type="date" name="grade_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Ҳафта</label>
                        <input type="number" name="week_number" class="form-control" min="1" max="18" value="1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Навъ</label>
                        <select name="grade_type" class="form-select" required>
                            <option value="classwork">Кори синфӣ</option>
                            <option value="homework">Вазифа</option>
                            <option value="quiz">Тест</option>
                            <option value="lab_work">Лаборат.</option>
                            <option value="presentation">Презентат.</option>
                            <option value="other">Дигар</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Макс. балл</label>
                        <input type="number" name="max_score" class="form-control" value="100" min="1" max="100" required>
                    </div>
                </div>

                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr><th>#</th><th>Донишҷӯ</th><th>Балл</th></tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->user?->full_name }}</td>
                                <td style="width: 120px;">
                                    <input type="number" name="grades[{{ $student->id }}]"
                                           class="form-control form-control-sm" min="0" max="100" step="0.5">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабт
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвали баҳоҳо --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Баҳоҳо аз рӯйи ҳафта</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm journal-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="student-name">#</th>
                            <th class="student-name">Донишҷӯ</th>
                            @for($w = 1; $w <= 16; $w++)
                                <th>{{ $w }}</th>
                            @endfor
                            <th>Ср.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $sg = $grades[$student->id] ?? collect();
                                $weekAvg = $sg->groupBy('week_number')->map(fn($g) => $g->avg(fn($x) => ($x->score / max($x->max_score, 1)) * 100));
                                $totalAvg = $sg->isEmpty() ? 0 : $sg->avg(fn($x) => ($x->score / max($x->max_score, 1)) * 100);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="student-name text-start">{{ $student->user?->short_name }}</td>
                                @for($w = 1; $w <= 16; $w++)
                                    <td>
                                        @if(isset($weekAvg[$w]))
                                            <span class="{{ $weekAvg[$w] >= 50 ? 'text-success' : 'text-danger' }}">{{ round($weekAvg[$w]) }}</span>
                                        @else —
                                        @endif
                                    </td>
                                @endfor
                                <td><strong class="{{ $totalAvg >= 50 ? 'text-success' : 'text-danger' }}">{{ number_format($totalAvg, 0) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <a href="{{ route('teacher.journal.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>
        </div>
    </div>
@endsection
