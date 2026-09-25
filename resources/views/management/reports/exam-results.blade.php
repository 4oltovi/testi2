@extends('layouts.app')

@section('title', 'Ҳисоботи имтиҳон')
@section('page-header', 'Ҳисоботи имтиҳон')
@section('page-description', 'Натиҷаҳои имтиҳонҳои семестрӣ')

@section('content')
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-3">
        <label class="form-label small">Соли таҳсилӣ</label>
        <select name="academic_year_id" class="form-select form-select-sm">
            @foreach($academicYears as $year)
                <option value="{{ $year->id }}" {{ $academicYearId == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Семестр</label>
        <select name="semester_id" class="form-select form-select-sm">
            <option value="">Ҳамаи семестрҳо</option>
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>{{ $semester->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label small">Гурӯҳ</label>
        <select name="group_id" class="form-select form-select-sm">
            <option value="">Ҳамаи гурӯҳҳо</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>{{ $group->full_name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 d-flex align-items-end">
        <button type="submit" class="btn btn-primary mb-0">Корбарӣ</button>
    </div>
</form>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Натиҷаҳои имтиҳон</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Фан</th>
                        <th class="text-center">Даража</th>
                        <th class="text-end">Рақам</th>
                        <th class="text-end">Балл</th>
                        <th>Семестр</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results as $result)
                    <tr>
                        <td>{{ $results->firstItem() + $loop->index }}</td>
                        <td>{{ $result->student?->user?->full_name ?? '—' }}</td>
                        <td>{{ $result->subjectAssignment?->subject?->name ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ match($result->letter_grade) {
                                'A' => 'success', 'B' => 'info', 'C' => 'primary',
                                'D' => 'warning', 'F' => 'danger', default => 'secondary'
                            } }}">{{ $result->letter_grade ?? '—' }}</span>
                        </td>
                        <td class="text-end">{{ $result->total_score ?? '—' }}</td>
                        <td class="text-end">{{ $result->grade_point ?? '—' }}</td>
                        <td>{{ $result->semester?->name ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Натиҷа нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $results->links() }}
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('management.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
    <a href="{{ route('management.reports.export', 'exam-results') }}" class="btn btn-success"><i class="bi bi-download me-1"></i> Экспорт Excel</a>
</div>
@endsection