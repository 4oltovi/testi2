@extends('layouts.app')
@section('title', 'Муҳосиб')
@section('page-header', 'Муҳосиб — Қарздории шартнома')
@section('page-description', 'Сабти пардохт ва қарздориҳои шартномавӣ')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Донишҷӯёни шартномавӣ</div>
                        <div class="fs-4 fw-bold">{{ $contractStudents }}</div>
                    </div>
                    <i class="bi bi-people fs-2 text-primary opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Маблағи шартнома</div>
                        <div class="fs-4 fw-bold">{{ number_format($totalExpected, 0) }}</div>
                    </div>
                    <i class="bi bi-cash fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Супорида шудааст</div>
                        <div class="fs-4 fw-bold text-success">{{ number_format($totalCollected, 0) }}</div>
                    </div>
                    <i class="bi bi-check-circle fs-2 text-success opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Монд</div>
                        <div class="fs-4 fw-bold text-danger">{{ number_format($totalRemaining, 0) }}</div>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-2 text-danger opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i> Пардохтҳои охирин</h6>
        <a href="{{ route('accountant.students') }}" class="text-decoration-none small">Ба ҳамаи донишҷувӣ</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Маблағ</th>
                        <th>Сана</th>
                        <th>Тарзи пардохт</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentPayments as $p)
                    <tr>
                        <td>{{ $p->student?->user?->full_name ?? '—' }}</td>
                        <td>{{ $p->student?->group?->full_name ?? '—' }}</td>
                        <td>{{ number_format($p->amount, 0) }}</td>
                        <td>{{ $p->payment_date?->format('d.m.Y') }}</td>
                        <td>{{ $p->payment_method ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Пардохт-е мавҷуд нест.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection