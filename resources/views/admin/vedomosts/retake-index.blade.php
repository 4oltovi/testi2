@extends('layouts.app')

@section('title', 'Ведомостҳои такрорӣ')
@section('page-header', 'Ведомостҳои такрорӣ')
@section('page-description', 'Рӯйхати ведомостҳои такрорӣ')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Ведомостҳои такрорӣ</h6>
            </div>
            <div class="card-body">
                {{-- Filters --}}
                <form method="GET" class="row g-2 align-items-end mb-3">
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Соли хониш</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">Ҳама солҳо</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" {{ $academicYearId == $year->id ? 'selected' : '' }}>
                                    {{ $year->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Семестр</label>
                        <select name="semester_id" class="form-select">
                            <option value="">Ҳама семестрҳо</option>
                            @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>
                                    {{ $semester->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Гурӯҳ</label>
                        <select name="group_id" class="form-select">
                            <option value="">Ҳама гурӯҳҳо</option>
                            @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>
                                    {{ $group->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i> Филтр
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">№</th>
                                <th>Гурӯҳ</th>
                                <th>Фан</th>
                                <th>Семестр</th>
                                <th>Имтиҳон</th>
                                <th>Донишҷӯён</th>
                                <th class="text-center">Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($retakeVedomosts as $index => $rv)
                            <tr>
                                <td style="color: var(--text-muted);">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $rv->group->name ?? '-' }}</strong>
                                </td>
                                <td>{{ $rv->retakeExam->subject->name ?? '-' }}</td>
                                <td>{{ $rv->retakeExam->semester->name ?? '-' }}</td>
                                <td>{{ $rv->exam_date?->format('d.m.Y') ?? '-' }}</td>
                                <td>
                                    {{ $studentCounts[$rv->retake_exam_id] ?? 0 }} нафар
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.retake-exams.vedomost.show', $rv) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Дидан
                                    </a>
                                    <a href="{{ route('admin.retake-exams.vedomost.pdf', $rv) }}" class="btn btn-sm btn-outline-danger" target="_blank">
                                        <i class="bi bi-printer me-1"></i> PDF
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Ведомостҳои такрорӣ ёфт нашданд.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
