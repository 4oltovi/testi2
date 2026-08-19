@extends('layouts.app')

@section('title', 'Рейтингҳо')
@section('page-header', 'Рейтингҳо')
@section('page-description', 'Рейтинги донишҷӯён, гурӯҳҳо ва факултетҳо')

@section('content')
{{-- Филтр --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Соли таҳсил</label>
                <select name="academic_year_id" id="academic_year_id" class="form-select">
                    <option value="">Ҳамаи солҳо</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" {{ $academicYearId == $year->id ? 'selected' : '' }}>
                            {{ $year->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Семестр</label>
                <select name="semester_id" id="semester_id" class="form-select">
                    @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $semesterId == $sem->id ? 'selected' : '' }} data-year="{{ $sem->academic_year_id }}">
                        {{ $sem->name }} — {{ $sem->academicYear?->name }} {{ $sem->is_current ? '(ҷорӣ)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-bar-chart-line me-1"></i> Нишон деҳ
                </button>
            </div>
        </form>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        {{-- Рейтинги факултетҳо --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-building me-2"></i> Рейтинги факултетҳо</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Факултет</th>
                            <th>GPA миёна</th>
                            <th>Донишҷӯён</th>
                            <th>Қарздорон</th>
                            <th>%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($facultyRating as $item)
                        <tr>
                            <td><span class="badge bg-{{ $item['rank'] <= 3 ? 'warning' : 'secondary' }}">{{ $item['rank'] }}</span></td>
                            <td>
                                {{-- ИСЛОҲ: линк ба факултети ҳақиқӣ --}}
                                <a href="{{ route('admin.ratings.faculty', ['faculty' => $item['faculty_id'] ?? 1, 'semester_id' => $semesterId]) }}">
                                    {{ $item['faculty_name'] }}
                                </a>
                            </td>
                            <td><strong class="{{ $item['avg_gpa'] >= 3.0 ? 'text-success' : 'text-warning' }}">{{ number_format($item['avg_gpa'], 2) }}</strong></td>
                            <td>{{ $item['total_students'] }}</td>
                            <td class="text-danger">{{ $item['students_with_debts'] }}</td>
                            <td><span class="badge {{ $item['debt_percentage'] > 20 ? 'bg-danger' : 'bg-success' }}">{{ $item['debt_percentage'] }}%</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Маълумот нест.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- НАВ: Рейтинги гурӯҳҳо бо GPA --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i> Рейтинги гурӯҳҳо</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Гурӯҳ</th>
                            <th>GPA миёна</th>
                            <th>Донишҷӯён</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groupStats as $i => $g)
                        <tr>
                            <td><span class="badge bg-{{ $i < 3 ? 'warning' : 'secondary' }}">{{ $i + 1 }}</span></td>
                            <td><strong>{{ $g['name'] }}</strong></td>
                            <td><strong class="{{ $g['avg_gpa'] >= 3.0 ? 'text-success' : 'text-warning' }}">{{ number_format($g['avg_gpa'], 2) }}</strong></td>
                            <td>{{ $g['students'] }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.ratings.group', ['group' => $g['id'], 'semester_id' => $semesterId]) }}"
                                    class="btn btn-sm btn-outline-primary">Дидан →</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Маълумот нест.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Топ-10 --}}
    <div class="col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i> Топ-10 донишҷӯён</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>GPA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topStudents as $item)
                        <tr>
                            <td>
                                @if($item['rank'] <= 3)
                                    <i class="bi bi-trophy-fill text-{{ $item['rank'] == 1 ? 'warning' : ($item['rank'] == 2 ? 'secondary' : 'danger') }}"></i>
                                    @else
                                    {{ $item['rank'] }}
                                    @endif
                            </td>
                            <td><small>{{ $item['student_name'] }}</small></td>
                            <td><span class="badge bg-info">{{ $item['group'] }}</span></td>
                            <td><strong class="text-success">{{ number_format($item['gpa'], 2) }}</strong></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-3">Маълумот нест.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="{{ route('admin.ratings.top-students', ['semester_id' => $semesterId]) }}" class="btn btn-sm btn-outline-warning">
                    Ҳамаро дидан →
                </a>
            </div>
        </div>
    </div>
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

            // If current selected semester is hidden, select first visible
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