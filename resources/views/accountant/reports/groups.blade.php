@extends('layouts.app')
@section('title', 'Муҳосиб — Қарздории гурӯҳҳо')
@section('page-header', 'Муҳосиб — Қарздории гурӯҳҳо')
@section('page-description', 'Ба ҳар гурӯҳ: чӣ қадар супорид, чӣ қадар монд')

@section('content')
<div class="card border-0 shadow-sm mb-3" style="border-radius:16px;">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Факултет</label>
                <select class="form-select form-select-sm" name="faculty_id" onchange="this.form.submit()">
                    <option value="">— Ҳамаи факультетҳо —</option>
                    @foreach($faculties as $f)
                        <option value="{{ $f->id }}" {{ (string) request('faculty_id') === (string) $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Ихтисос</label>
                <select class="form-select form-select-sm" name="specialty_id" onchange="this.form.submit()">
                    <option value="">— Ҳамаи ихтисосҳо —</option>
                    @foreach($specialties as $sp)
                        <option value="{{ $sp->id }}" {{ (string) request('specialty_id') === (string) $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Курс</label>
                <select class="form-select form-select-sm" name="course_id" onchange="this.form.submit()">
                    <option value="">— Ҳама —</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ (string) request('course_id') === (string) $c->id ? 'selected' : '' }}>Курси {{ $c->number }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Гурӯҳ</label>
                <select class="form-select form-select-sm" name="group_id" onchange="this.form.submit()">
                    <option value="">— Ҳамаи гурӯҳҳо —</option>
                    @foreach($filterGroups as $fg)
                        <option value="{{ $fg->id }}" {{ (string) request('group_id') === (string) $fg->id ? 'selected' : '' }}>{{ $fg->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <a href="{{ route('accountant.reports.groups') }}" class="btn btn-sm btn-outline-secondary w-100" title="Тоза кардан">
                    <i class="bi bi-x-lg"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <div class="text-muted small">Маблағи шартномаи Ҳама</div>
                <div class="fs-5 fw-bold">{{ number_format($totalExpected, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <div class="text-muted small">Супорида шудааст</div>
                <div class="fs-5 fw-bold text-success">{{ number_format($totalPaid, 0) }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-4">
        <div class="card border-0 shadow-sm" style="border-radius:16px;">
            <div class="card-body">
                <div class="text-muted small">Монд</div>
                <div class="fs-5 fw-bold text-danger">{{ number_format($totalRemaining, 0) }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Қарздории гурӯҳҳо</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Гурӯҳ</th>
                        <th>Донишҷӯёни шартномавӣ</th>
                        <th>Маблағи шартнома</th>
                        <th>Супорида шудааст</th>
                        <th>Монд</th>
                        <th>Фоиз</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report as $r)
                    <tr>
                        <td>{{ $r->group?->full_name ?? '—' }}</td>
                        <td>{{ $r->contract_students }}</td>
                        <td>{{ number_format($r->total_expected, 0) }}</td>
                        <td class="text-success">{{ number_format($r->total_paid, 0) }}</td>
                        <td class="text-danger">{{ number_format($r->total_remaining, 0) }}</td>
                        <td>{{ $r->total_expected > 0 ? round(($r->total_paid / $r->total_expected) * 100, 1) : 0 }}%</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Донишҷӯи шартномавӣ мавҷуд нест.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection