@extends('layouts.app')

@section('title', 'Ҳисоботи донишҷӯён')
@section('page-header', 'Ҳисоботи донишҷӯён')
@section('page-description', 'Рӯййати донишҷӯёни актив')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-filter me-2"></i> Филтр</h6>
            <form method="GET" class="d-flex gap-2">
                <select name="group_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Ҳамаи гурӯҳҳо</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->full_name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Ихтисос</th>
                        <th>Курс</th>
                        <th class="text-end">GPA</th>
                        <th class="text-center">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                    <tr>
                        <td>{{ $students->firstItem() + $loop->index }}</td>
                        <td>{{ $student->user?->full_name ?? '—' }}</td>
                        <td>{{ $student->group?->name ?? '—' }}</td>
                        <td>{{ $student->specialty?->name ?? '—' }}</td>
                        <td>{{ $student->course?->number ?? '—' }}</td>
                        <td class="text-end">{{ number_format($student->cumulative_gpa ?? 0, 2) }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $student->has_debts ? 'danger' : 'success' }}">
                                {{ $student->has_debts ? 'Қарздор' : 'Ок' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $students->links() }}
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('management.reports.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
    <a href="{{ route('management.reports.export', 'students') }}" class="btn btn-success"><i class="bi bi-download me-1"></i> Экспорт Excel</a>
</div>
@endsection