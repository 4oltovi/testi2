@extends('layouts.app')

@section('title', 'Давомоти ман')
@section('page-header', 'Давомоти ман')
@section('page-description', 'Ҳолати ҳозир будан барои семестри ҷорӣ')

@section('content')
<div class="row g-4">
    @if($summary)
    <div class="col-12">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ $summary->total ?? 0 }}</h3>
                        <small class="text-muted">Ҳамагӣ дарс</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ $summary->present ?? 0 }}</h3>
                        <small class="text-muted">Ҳозир</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h3 class="text-danger">{{ $summary->absent ?? 0 }}</h3>
                        <small class="text-muted">Ғоиб</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Рӯйхати давомот</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Сана</th>
                                <th>Фан</th>
                                <th>Омӯзгор</th>
                                <th>Ҳолат</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                            <tr>
                                <td>{{ $record->attendance_date?->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $record->subjectAssignment?->subject?->name ?? '-' }}</td>
                                <td>{{ $record->subjectAssignment?->teacher?->user?->full_name ?? '-' }}</td>
                                <td>
                                    @php
                                        $statusLabel = match($record->status) {
                                            'present' => 'Ҳозир',
                                            'absent' => 'Ғоиб',
                                            'late' => 'Дироз',
                                            'excused' => 'Баҳонавӣ',
                                            'sick' => 'Бемор',
                                            default => $record->status,
                                        };
                                        $statusClass = match($record->status) {
                                            'present', 'late', 'excused', 'sick' => 'text-success',
                                            'absent' => 'text-danger',
                                            default => 'text-muted',
                                        };
                                    @endphp
                                    <span class="{{ $statusClass }} fw-bold">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Ҳанӯз маълумот дастрас нест.
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
