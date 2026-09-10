@extends('layouts.app')

@section('title', 'Давомот')
@section('page-header', 'Давомот')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }}
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th class="text-muted">Фан:</th>
                <td>{{ $subjectAssignment->subject?->name }}</td>
                <th class="text-muted ms-4">Гурӯҳ:</th>
                <td>{{ $subjectAssignment->group?->name }}</td>
            </tr>
            <tr>
                <th class="text-muted">Омӯзгор:</th>
                <td>{{ $subjectAssignment->teacher?->first_name }} {{ $subjectAssignment->teacher?->last_name }}</td>
                <th class="text-muted ms-4">Семестр:</th>
                <td>{{ $semester->name }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Давомот — {{ $date }} (Дарси {{ $lessonNumber }})</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th class="text-center">Статус</th>
                        <th class="text-end">Умумӣ</th>
                        <th class="text-end">Ҳаҷми ҳафиз</th>
                        <th class="text-end">Ғайб</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                    @php
                        $status = $existingAttendance[$student->id] ?? 'absent';
                        $statusLabel = match($status) {
                            'present' => ['Ҳаҷм', 'success'],
                            'absent' => ['Ғайб', 'danger'],
                            'late' => ['Тамъоза', 'warning'],
                            'excused' => ['Освобожден', 'info'],
                            'sick' => ['Бемор', 'secondary'],
                            default => ['—', 'light'],
                        };
                        $stats = $attendanceStats[$student->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $student->user?->last_name }} {{ $student->user?->first_name }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                        </td>
                        <td class="text-end">{{ $stats->total ?? 0 }}</td>
                        <td class="text-end">{{ $stats->present_count ?? 0 }}</td>
                        <td class="text-end">{{ $stats->absent_count ?? 0 }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection