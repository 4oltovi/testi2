@extends('layouts.app')

@section('title', 'Ҳисоботи давомот')
@section('page-header', 'Ҳисоботи давомот')
@section('page-description', 'Омораи ҳозирӣ бо зери донишҷӯ')

@section('content')
<form method="GET" class="row g-3 mb-4">
    <div class="col-md-4">
        <label class="form-label small">Гурӯҳ</label>
        <select name="group_id" class="form-select form-select-sm">
            <option value="">Интихоб кунед</option>
            @foreach($groups as $group)
                <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label small">Семестр</label>
        <select name="semester_id" class="form-select form-select-sm">
            @foreach($semesters as $semester)
                <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>{{ $semester->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary mb-0">Корбарӣ</button>
    </div>
</form>

@if($attendanceData->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Омораи давомот</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th class="text-end">Ҳамагӣ</th>
                        <th class="text-end">Ҳаҷм</th>
                        <th class="text-end">Ғайб</th>
                        <th class="text-end">Фоиз</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attendanceData as $i => $data)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $data['student_name'] ?? '—' }}</td>
                        <td class="text-end">{{ $data['total'] }}</td>
                        <td class="text-end">{{ $data['present'] }}</td>
                        <td class="text-end">{{ $data['absent'] }}</td>
                        <td class="text-end">{{ $data['percentage'] }}%</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Маълумот нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-info-circle fs-1 text-muted mb-3"></i>
        <p class="text-muted">Гурӯҳ ва семестрро интихоб кунед</p>
    </div>
</div>
@endif

<div class="mt-4">
    <a href="{{ route('management.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
</div>
@endsection