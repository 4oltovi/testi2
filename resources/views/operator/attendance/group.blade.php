@extends('layouts.app')

@section('title', 'Давомот — ' . $group->name)
@section('page-header', 'ДАВОМОТИ ГУРӮҲ')
@section('page-description', $group->name . ' — ' . ($group->specialty?->name ?? '') . ' — ' . $students->count() . ' донишҷӯ')

@section('content')
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb" style="background: transparent; padding: 0; margin: 0;">
            <li class="breadcrumb-item">
                <a href="{{ route('operator.attendance.index') }}" style="text-decoration: none; color: var(--primary-color, #4f46e5);">
                    <i class="bi bi-arrow-left me-1"></i>Гурӯҳҳо
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">{{ $group->name }}</li>
        </ol>
    </nav>

    <form method="POST" action="{{ route('operator.attendance.store', $group) }}" id="attendanceForm">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}" id="attendanceDate">

        {{-- Date Selector --}}
        <div class="card border-0 shadow-sm mb-3" style="border-radius: 16px; border: 1px solid #e8edf5;">
            <div class="card-body py-3">
                <div class="row align-items-center">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Санаи давомот</label>
                        <input type="date" name="date_picker" class="form-control" value="{{ $date }}" id="datePicker" style="height: 48px; border-radius: 12px; font-size: 1.1rem; font-weight: 600; text-align: center; border: 2px solid #e8edf5;">
                    </div>
                    <div class="col-md-8 mt-3 mt-md-0">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-outline-success btn-sm" id="markAllPresent" style="height: 40px; border-radius: 10px; font-weight: 600;">
                                <i class="bi bi-check-all me-1"></i> Ҳамаро ҳозир
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="markAllAbsent" style="height: 40px; border-radius: 10px; font-weight: 600;">
                                <i class="bi bi-x-lg me-1"></i> Ҳамаро ғоиб
                            </button>
                            <a href="{{ route('operator.attendance.export.excel') }}?start_date={{ $date }}&end_date={{ $date }}&group_id={{ $group->id }}" class="btn btn-outline-success btn-sm" style="height: 40px; border-radius: 10px;" target="_blank">
                                <i class="bi bi-file-earmark-excel me-1"></i> Excel
                            </a>
                            <a href="{{ route('operator.attendance.export.pdf') }}?start_date={{ $date }}&end_date={{ $date }}&group_id={{ $group->id }}" class="btn btn-outline-danger btn-sm" style="height: 40px; border-radius: 10px;" target="_blank">
                                <i class="bi bi-file-earmark-pdf me-1"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="row g-3 mb-3">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #e8edf5;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-primary">{{ $summary['total'] }}</div>
                        <small class="text-muted">Ҳама</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #e8edf5;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-success">{{ $summary['present'] }}</div>
                        <small class="text-muted">Ҳозир</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #e8edf5;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-danger">{{ $summary['absent'] }}</div>
                        <small class="text-muted">Ғоиб</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #e8edf5;">
                    <div class="card-body text-center py-3">
                        <div class="fw-bold fs-4 text-info">{{ $summary['percentage'] }}%</div>
                        <small class="text-muted">Давомот</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Students List --}}
        <div class="card border-0 shadow-sm" style="border-radius: 16px; border: 1px solid #e8edf5;">
            <div class="card-header bg-white" style="border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-people me-2 text-primary"></i>
                    Рӯйхати донишҷӯён
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px; padding: 0.75rem 0.5rem;">№</th>
                                <th style="padding: 0.75rem 0.5rem;">Донишҷӯ</th>
                                <th class="text-center" style="width: 280px; padding: 0.75rem 0.5rem;">Ҳолат</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                @php
                                    $currentStatus = $dailyAttendance[$student->id] ?? 'present';
                                @endphp
                                <tr data-student-id="{{ $student->id }}" data-status="{{ $currentStatus }}">
                                    <td style="padding: 0.75rem 0.5rem; color: var(--text-muted);">{{ $index + 1 }}</td>
                                    <td style="padding: 0.75rem 0.5rem;">
                                        <div class="fw-semibold">{{ $student->user?->last_name }} {{ $student->user?->first_name }}</div>
                                        <small class="text-muted" style="font-size: 0.8rem;">{{ $student->student_id_number ?? '—' }}</small>
                                    </td>
                                    <td class="text-center" style="padding: 0.75rem 0.5rem;">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" value="present" id="present_{{ $student->id }}" {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-success px-4 py-2" for="present_{{ $student->id }}" style="border-radius: 10px; font-weight: 600; min-width: 100px;">
                                                <i class="bi bi-check-lg me-1"></i> Ҳозир
                                            </label>

                                            <input type="radio" class="btn-check attendance-radio" name="attendance[{{ $student->id }}]" value="absent" id="absent_{{ $student->id }}" {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                            <label class="btn btn-outline-danger px-4 py-2" for="absent_{{ $student->id }}" style="border-radius: 10px; font-weight: 600; min-width: 100px;">
                                                <i class="bi bi-x-lg me-1"></i> Ғоиб
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                        Донишҷӯён ёфт нашданд.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 text-center py-3" style="border-radius: 0 0 16px 16px;">
                <button type="submit" class="btn btn-primary px-5 py-2" style="border-radius: 10px; font-weight: 600; font-size: 1rem; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);">
                    <i class="bi bi-check-lg me-1"></i> Сабти давомот
                </button>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const datePicker = document.getElementById('datePicker');
    const attendanceDate = document.getElementById('attendanceDate');
    const form = document.getElementById('attendanceForm');

    datePicker.addEventListener('change', function() {
        attendanceDate.value = this.value;
        form.submit();
    });

    const radios = document.querySelectorAll('.attendance-radio');
    const totalEl = document.querySelector('.fw-bold.fs-4.text-primary');
    const presentEl = document.querySelector('.fw-bold.fs-4.text-success');
    const absentEl = document.querySelector('.fw-bold.fs-4.text-danger');
    const percentageEl = document.querySelector('.fw-bold.fs-4.text-info');

    function updateSummary() {
        const total = radios.length;
        let present = 0;
        let absent = 0;

        radios.forEach(radio => {
            if (radio.checked) {
                if (radio.value === 'present') present++;
                if (radio.value === 'absent') absent++;
            }
        });

        const percentage = total > 0 ? Math.round((present / total) * 100) : 0;

        if (totalEl) totalEl.textContent = total;
        if (presentEl) presentEl.textContent = present;
        if (absentEl) absentEl.textContent = absent;
        if (percentageEl) percentageEl.textContent = percentage + '%';
    }

    radios.forEach(radio => {
        radio.addEventListener('change', updateSummary);
    });

    document.getElementById('markAllPresent')?.addEventListener('click', function() {
        radios.forEach(r => {
            if (r.value === 'present') r.checked = true;
        });
        updateSummary();
    });

    document.getElementById('markAllAbsent')?.addEventListener('click', function() {
        radios.forEach(r => {
            if (r.value === 'absent') r.checked = true;
        });
        updateSummary();
    });
});
</script>
@endpush
