@extends('layouts.app')
@section('title', 'Муҳосиб — ' . ($student->user?->full_name ?? ''))
@section('page-header', 'Муҳосиб')
@section('page-description', $student->user?->full_name)

@section('content')
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i> Маълумоти донишҷувӣ</h6>
                <a href="{{ route('accountant.students') }}" class="btn btn-sm btn-outline-secondary">Бозгашт</a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="text-muted small">Ном</div><div class="fw-bold">{{ $student->user?->full_name }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">ID донишҷувӣ</div><div>{{ $student->student_id_number }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Гурӯҳ</div><div>{{ $student->group?->full_name ?? '—' }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Маблағи шартнома</div><div class="fw-bold">{{ number_format($student->contract_amount ?? 0, 0) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Супорида шудааст</div><div class="fw-bold text-success">{{ number_format($student->contract_paid, 0) }}</div></div>
                    <div class="col-md-6"><div class="text-muted small">Монд</div><div class="fw-bold text-danger">{{ number_format($student->contract_remaining, 0) }}</div></div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mt-3" style="border-radius:16px;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-receipt me-2"></i> Иловаи пардохт</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('accountant.payments.store', $student) }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Маблағ <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сана <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Тарзи пардохт</label>
                            <input type="text" class="form-control" name="payment_method" placeholder="Масалан: нақд, бонк">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сол</label>
                            <select class="form-select" name="academic_year_id">
                                <option value="">— Ҳама —</option>
                                @foreach($years as $y)
                                    <option value="{{ $y->id }}">{{ $y->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Эзоҳ</label>
                            <input type="text" class="form-control" name="note" placeholder="Эзоҳ (маълумоти иловагӣ)">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Иловаи пардохт</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-list-ul me-2"></i> Таърихи пардохт</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Сана</th><th>Маблағ</th><th>Тарзи пардохт</th></tr>
                        </thead>
                        <tbody>
                            @forelse($student->contractPayments->sortByDesc('payment_date') as $p)
                            <tr>
                                <td>{{ $p->payment_date?->format('d.m.Y') }}</td>
                                <td class="text-success">{{ number_format($p->amount, 0) }}</td>
                                <td>{{ $p->payment_method ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Пардохт-е мавҷуд нест.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection