@extends('layouts.app')

@section('title', 'Қарздориҳо')
@section('page-header', 'Қарздориҳо')
@section('page-description')
    Факултет: {{ $faculty?->name ?? '—' }}
@endsection

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary mb-0">{{ $stats['total_open'] }}</h3>
                <small class="text-muted">Корҳои боз</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success mb-0">{{ $stats['active'] }}</h3>
                <small class="text-muted">Фаъол</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">{{ $stats['retake_scheduled'] }}</h3>
                <small class="text-muted">Барои перенасите</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-danger mb-0">{{ $stats['overdue'] }}</h3>
                <small class="text-muted">Узоф шуда</small>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳо</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Фан</th>
                        <th>Сабаб</th>
                        <th class="text-center">Баланд</th>
                        <th>Сана</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debts as $debt)
                    <tr>
                        <td>{{ $debts->firstItem() + $loop->index }}</td>
                        <td>{{ $debt->student?->user?->full_name ?? '—' }}</td>
                        <td>{{ $debt->student?->group?->name ?? '—' }}</td>
                        <td>{{ $debt->subject?->name ?? '—' }}</td>
                        <td>{{ $debt->reason ?? '—' }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ match($debt->status) {
                                'active' => 'warning', 'retake_scheduled' => 'info',
                                'escalated' => 'danger', 'resolved' => 'success',
                                default => 'secondary'
                            } }}">{{ $debt->status }}</span>
                        </td>
                        <td><small>{{ $debt->debt_date?->format('d.m.Y') }}</small></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Қарздорӣ нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $debts->links() }}
    </div>
</div>
@endsection