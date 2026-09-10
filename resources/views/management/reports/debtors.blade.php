@extends('layouts.app')

@section('title', 'Ҳисоботи қарздориҳо')
@section('page-header', 'Ҳисоботи қарздориҳо')
@section('page-description', 'Донишҷӯёни қарздори ихтиёрӣ')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Томони дагъхи қарздор</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
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

@if($debtorsByGroup->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-bar-chart-line me-2"></i> Қарздориҳо бо зери гурӯҳ</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Гурӯҳ</th>
                        <th class="text-end">Тадриб</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debtorsByGroup as $row)
                    <tr>
                        <td>{{ $row->group_name }}</td>
                        <td class="text-end">{{ $row->debtors_count }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="2" class="text-center text-muted py-3">Маълумот нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="mt-4 d-flex justify-content-between">
    <a href="{{ route('management.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
    <a href="{{ route('management.reports.export', 'debtors') }}" class="btn btn-success"><i class="bi bi-download me-1"></i> Экспорт Excel</a>
</div>
@endsection