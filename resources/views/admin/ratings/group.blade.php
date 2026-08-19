@extends('layouts.app')

@section('title', 'Рейтинги гурӯҳ: ' . $group->name)
@section('page-header', 'Рейтинги гурӯҳ: ' . $group->name)
@section('page-description')
    {{ $group->specialty->name ?? '' }} | Курси {{ $group->course?->number ?? $group->course?->name ?? '' }} | {{ $group->specialty->department?->faculty?->name ?? '' }}
@endsection

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

<div class="card">
    <div class="card-body">
        @if($groupRating->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>GPA</th>
                            <th>Кумулятивӣ</th>
                            <th>Кредитҳо</th>
                            <th>Гузашт</th>
                            <th>Нагузашт</th>
                            <th>Қарз</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupRating as $index => $rating)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $rating['student_name'] }}</td>
                                <td>
                                    <span class="fw-bold {{ $rating['gpa'] >= 4.0 ? 'text-success' : ($rating['gpa'] >= 3.0 ? 'text-primary' : ($rating['gpa'] >= 2.0 ? 'text-warning' : 'text-danger')) }}">
                                        {{ number_format($rating['gpa'], 2) }}
                                    </span>
                                </td>
                                <td>{{ number_format($rating['cumulative_gpa'], 2) }}</td>
                                <td>{{ $rating['credits_earned'] }}</td>
                                <td><span class="text-success">{{ $rating['subjects_passed'] }}</span></td>
                                <td><span class="text-danger">{{ $rating['subjects_failed'] }}</span></td>
                                <td>
                                    @if($rating['has_debts'])
                                        <span class="badge bg-danger">Ҳа</span>
                                    @else
                                        <span class="badge bg-success">Не</span>
                                    @endif
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
