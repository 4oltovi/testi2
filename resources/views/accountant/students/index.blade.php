@extends('layouts.app')
@section('title', 'Муҳосиб — Донишҷӯён')
@section('page-header', 'Муҳосиб — Донишҷӯён')
@section('page-description', 'Рӯйхати донишҷӯёни шартномавӣ')

@section('content')
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-header bg-white">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Ҷустуҷӯ">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="group_id">
                    <option value="">— Ҳамаи гурӯҳҳо —</option>
                    @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->full_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="academic_year_id">
                    <option value="">— Ҳамаи солҳо —</option>
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" {{ request('academic_year_id') == $y->id ? 'selected' : '' }}>{{ $y->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Тафтиш</button>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Маблағи шартнома</th>
                        <th>Супорида шудааст</th>
                        <th>Монд</th>
                        <th>Амал</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $s)
                    <tr>
                        <td>{{ $s->user?->full_name ?? '—' }}</td>
                        <td>{{ $s->group?->full_name ?? '—' }}</td>
                        <td>{{ number_format($s->contract_amount ?? 0, 0) }}</td>
                        <td class="text-success">{{ number_format($s->contract_paid, 0) }}</td>
                        <td class="text-danger">{{ number_format($s->contract_remaining, 0) }}</td>
                        <td>
                            <a href="{{ route('accountant.students.show', $s) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">Донишҷӯи шартномавӣ мавҷуд нест.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($students->hasPages())
    <div class="card-footer bg-white">{{ $students->links() }}</div>
    @endif
</div>
@endsection
