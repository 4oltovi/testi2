@extends('layouts.app')

@section('title', $role?->label() ?? 'Дашбоард')
@section('page-header', ($role?->label() ?? 'Дашбоард') . ' — ' . ($user?->full_name ?? ''))
@section('page-description', 'Хулосаи умумӣ')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary mb-0">{{ $stats['total_students'] }}</h3>
                <small class="text-muted">Донишҷӯён</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success mb-0">{{ $stats['total_teachers'] }}</h3>
                <small class="text-muted">Омӯзгорон</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">{{ $stats['total_groups'] }}</h3>
                <small class="text-muted">Гурӯҳҳо</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning mb-0">{{ $stats['active_debts'] }}</h3>
                <small class="text-muted">Қарздорӣ</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i> Донишҷӯёни охид</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ном</th>
                                <th>Гурӯҳ</th>
                                <th>Санаи қайд</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_students'] as $student)
                            <tr>
                                <td>{{ $student->user?->full_name ?? '—' }}</td>
                                <td>{{ $student->group?->name ?? '—' }}</td>
                                <td><small>{{ $student->created_at?->format('d.m.Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳои кушод</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Донишҷӯ</th>
                                <th>Сабаб</th>
                                <th>Сана</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_debts'] as $debt)
                            <tr>
                                <td>{{ $debt->student?->user?->full_name ?? '—' }}</td>
                                <td>{{ $debt->reason ?? '—' }}</td>
                                <td><small>{{ $debt->debt_date?->format('d.m.Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Қарздорӣ нест</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
