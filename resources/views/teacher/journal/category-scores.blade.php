@extends('layouts.app')

@section('title', 'Баҳогузорӣ бо категорияҳо')
@section('page-header', 'Журнал — Баҳоҳои категориявӣ')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    {{-- Интихоби сана ва дарс --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Санаи дарс</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Рақами дарс</label>
                    <input type="number" name="lesson_number" class="form-control" min="1" max="8" value="{{ $lessonNumber }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Нишон деҳ
                    </button>
                </div>
                <div class="col-md-5 text-end">
                    @php
                        $routePrefix = request()->is('admin/*') ? 'admin.journal' : 'teacher.journal';
                    @endphp
                    <a href="{{ route($routePrefix . '.category-settings', $subjectAssignment) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-gear me-1"></i> Танзимоти категорияҳо
                    </a>
                    <a href="{{ route($routePrefix . '.category-report', $subjectAssignment) }}" class="btn btn-outline-info btn-sm">
                        <i class="bi bi-bar-chart me-1"></i> Гузориш
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвали баҳогузорӣ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-journal-check me-2"></i>
                Баҳогузорӣ: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }} — Дарси {{ $lessonNumber }}
            </h6>
            <div>
                @foreach($categorySettings->where('is_active', true) as $cs)
                    <span class="badge bg-{{ $cs->category->colorClass() }} me-1">
                        {{ $cs->category->shortLabel() }}: макс {{ $cs->max_score }}
                    </span>
                @endforeach
            </div>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="{{ route((request()->is('admin/*') ? 'admin.journal' : 'teacher.journal') . '.category-scores.store', $subjectAssignment) }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <input type="hidden" name="lesson_number" value="{{ $lessonNumber }}">

                <div class="table-responsive">
                    <table class="table table-sm table-bordered table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="min-width: 180px;">Донишҷӯ</th>
                                @foreach($categorySettings->where('is_active', true) as $cs)
                                    <th class="text-center" style="min-width: 80px;">
                                        <i class="bi {{ $cs->category->icon() }} me-1"></i>
                                        {{ $cs->category->shortLabel() }}
                                        <br><small class="text-muted">(0-{{ $cs->max_score }})</small>
                                    </th>
                                @endforeach
                                <th class="text-center">Ҷамъ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->user?->full_name }}</td>
                                    @foreach($categorySettings->where('is_active', true) as $cs)
                                        @php
                                            $key = $student->id . '_' . $cs->category->value;
                                            $existing = $existingScores[$key] ?? null;
                                            $existingScore = $existing ? $existing->first()->score : '';
                                        @endphp
                                        <td class="text-center">
                                            <input type="number"
                                                   name="scores[{{ $student->id }}][{{ $cs->category->value }}]"
                                                   class="form-control form-control-sm text-center score-input"
                                                   min="0" max="{{ $cs->max_score }}" step="0.5"
                                                   value="{{ $existingScore }}"
                                                   data-max="{{ $cs->max_score }}"
                                                   data-student="{{ $student->id }}"
                                                   placeholder="—">
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <strong class="student-total" id="total-{{ $student->id }}">0</strong>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer bg-white text-end">
                    <a href="{{ route('teacher.journal.index') }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабт кардан
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.score-input');

    function updateTotals() {
        const students = {};
        inputs.forEach(input => {
            const studentId = input.dataset.student;
            if (!students[studentId]) students[studentId] = 0;
            const val = parseFloat(input.value) || 0;
            students[studentId] += val;
        });

        Object.keys(students).forEach(studentId => {
            const el = document.getElementById('total-' + studentId);
            if (el) el.textContent = students[studentId].toFixed(1);
        });
    }

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            const max = parseFloat(this.dataset.max);
            if (parseFloat(this.value) > max) {
                this.value = max;
            }
            if (parseFloat(this.value) < 0) {
                this.value = 0;
            }
            updateTotals();
        });
    });

    updateTotals();
});
</script>
@endpush
