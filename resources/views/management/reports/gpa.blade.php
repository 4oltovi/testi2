@extends('layouts.app')

@section('title', 'Ҳисоботи GPA')
@section('page-header', 'Ҳисоботи GPA')
@section('page-description', 'Рейтинги баландтарин донишҷӯён')

@section('content')
<form method="GET" class="row g-3 mb-4">
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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i> Баландтарин GPA</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th class="text-end">GPA</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($gpaData as $student)
                    @php
                        $gpa = $student->cumulative_gpa ?? 0;
                        $color = $gpa >= 3.5 ? 'success' : ($gpa >= 2.5 ? 'info' : ($gpa >= 2.0 ? 'warning' : 'danger'));
                    @endphp
                    <tr>
                        <td>{{ $gpaData->firstItem() + $loop->index }}</td>
                        <td>{{ $student->user?->full_name ?? '—' }}</td>
                        <td>{{ $student->group?->name ?? '—' }}</td>
                        <td class="text-end">
                            <span class="badge bg-{{ $color }}">{{ number_format($gpa, 2) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $gpaData->links() }}
    </div>
</div>

<div class="mt-4 d-flex justify-content-between">
    <a href="{{ route('management.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
    <a href="{{ route('management.reports.export', 'gpa') }}" class="btn btn-success"><i class="bi bi-download me-1"></i> Экспорт Excel</a>
</div>
@endsection