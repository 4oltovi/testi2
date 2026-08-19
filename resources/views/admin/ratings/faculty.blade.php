@extends('layouts.app')

@section('title', 'Рейтинги факултет: ' . $faculty->name)
@section('page-header', 'Рейтинги факултет: ' . $faculty->name)
@section('page-description', 'Рейтинги гурӯҳҳо ва донишҷӯёни беҳтарин')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="academic_year_id" class="form-label">Соли таҳсил</label>
                <select name="academic_year_id" id="academic_year_id" class="form-select">
                    <option value="">Ҳамаи солҳо</option>
                    @foreach($academicYears ?? [] as $year)
                        <option value="{{ $year->id }}" {{ ($academicYearId ?? '') == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="semester_id" class="form-label">Семестр</label>
                <select name="semester_id" id="semester_id" class="form-select">
                    <option value="">Ҳамаи семестрҳо</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }} data-year="{{ $semester->academic_year_id }}">
                            {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Филтр
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row">
    {{-- Left column: Groups table --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-people"></i> Рейтинги гурӯҳҳо</h5>
            </div>
            <div class="card-body">
                @if($groupsRating->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Гурӯҳ</th>
                                    <th>Ихтисос</th>
                                    <th>GPA миёна</th>
                                    <th>Донишҷӯён</th>
                                    <th>Қарздорон</th>
                                    <th>Сифат %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groupsRating as $index => $group)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ url('/admin/ratings/group/' . ($group['group_id'] ?? $group['id'] ?? '')) }}">
                                                {{ $group['group_name'] }}
                                            </a>
                                        </td>
                                        <td>{{ $group['specialty'] }}</td>
                                        <td>
                                            <span class="fw-bold {{ $group['avg_gpa'] >= 4.0 ? 'text-success' : ($group['avg_gpa'] >= 3.0 ? 'text-primary' : 'text-warning') }}">
                                                {{ number_format($group['avg_gpa'], 2) }}
                                            </span>
                                        </td>
                                        <td>{{ $group['total_students'] }}</td>
                                        <td>
                                            @if($group['students_with_debts'] > 0)
                                                <span class="text-danger">{{ $group['students_with_debts'] }}</span>
                                            @else
                                                <span class="text-success">0</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $group['quality_percentage'] >= 70 ? 'bg-success' : ($group['quality_percentage'] >= 50 ? 'bg-warning' : 'bg-danger') }}">
                                                {{ number_format($group['quality_percentage'], 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bar-chart fs-1"></i>
                        <p class="mt-2">Маълумот вуҷуд надорад</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right column: Top students --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-trophy"></i> Донишҷӯёни беҳтарин</h5>
            </div>
            <div class="card-body p-0">
                @if($topStudents->count() > 0)
                        <ul class="list-group list-group-flush">
                            @foreach($topStudents as $index => $student)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                        <strong>{{ $student['student_name'] }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $student['group'] }}</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">{{ number_format($student['gpa'], 2) }}</span>
                                </li>
                            @endforeach
                        </ul>
                @else
                    <div class="text-center py-4 text-muted">
                        <p class="mb-0">Маълумот вуҷуд надорад</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ url('/admin/ratings') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Бозгашт
    </a>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const yearSelect = document.getElementById('academic_year_id');
    const semesterSelect = document.getElementById('semester_id');

    if (yearSelect && semesterSelect) {
        function filterSemesters() {
            const selectedYear = yearSelect.value;
            const options = semesterSelect.querySelectorAll('option[data-year]');

            options.forEach(option => {
                if (!selectedYear || option.dataset.year === selectedYear) {
                    option.hidden = false;
                } else {
                    option.hidden = true;
                }
            });

            const currentOption = semesterSelect.querySelector('option:checked');
            if (currentOption && currentOption.hidden) {
                const visibleOptions = semesterSelect.querySelectorAll('option[data-year]:not([hidden])');
                if (visibleOptions.length > 0) {
                    visibleOptions[0].selected = true;
                }
            }
        }

        yearSelect.addEventListener('change', filterSemesters);
        filterSemesters();
    }
});
</script>
@endsection
