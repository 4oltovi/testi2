@extends('layouts.app')

@section('title', 'Топ донишҷӯён')
@section('page-header', 'Топ донишҷӯён')
@section('page-description', 'Рӯйхати донишҷӯёни беҳтарин аз рӯи GPA')

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
            <div class="col-md-3">
                <label for="faculty_id" class="form-label">Факултет</label>
                <select name="faculty_id" id="faculty_id" class="form-select">
                    <option value="">Ҳамаи факултетҳо</option>
                    @foreach($faculties as $faculty)
                        <option value="{{ $faculty->id }}" {{ $facultyId == $faculty->id ? 'selected' : '' }}>
                            {{ $faculty->name }}
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

<div class="card">
    <div class="card-body">
        @if($topStudents->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>Ихтисос</th>
                            <th>GPA</th>
                            <th>Кумулятивӣ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topStudents as $index => $student)
                            <tr>
                                <td>
                                    @if($index < 3)
                                        <i class="bi bi-trophy-fill {{ $index === 0 ? 'text-warning' : ($index === 1 ? 'text-secondary' : 'text-danger') }} fs-5"></i>
                                    @else
                                        {{ $index + 1 }}
                                    @endif
                                </td>
                                <td><strong>{{ $student->student_name }}</strong></td>
                                <td>{{ $student->group }}</td>
                                <td>{{ $student->specialty }}</td>
                                <td>
                                    <span class="fw-bold {{ $student->gpa >= 4.5 ? 'text-success' : ($student->gpa >= 4.0 ? 'text-primary' : 'text-info') }}">
                                        {{ number_format($student->gpa, 2) }}
                                    </span>
                                </td>
                                <td>{{ number_format($student->cumulative_gpa, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-trophy fs-1"></i>
                <p class="mt-2">Маълумот вуҷуд надорад</p>
            </div>
        @endif
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
