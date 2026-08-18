@extends('layouts.app')

@section('title', 'Журнали электронӣ')
@section('page-header', 'Журнали электронӣ')
@section('page-description', 'Давомот, баҳоҳои ҷорӣ ва семестрӣ')

@section('content')
{{-- Филтр --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Семестр</label>
                <select name="semester_id" class="form-select">
                    @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ $semesterId == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} — {{ $sem->academicYear?->name }}
                        {{ $sem->is_current ? '(ҷорӣ)' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Гурӯҳ</label>
                <select name="group_id" class="form-select">
                    <option value="">Ҳама гурӯҳҳо</option>
                    @foreach($groups as $group)
                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-filter me-1"></i> Филтр</button>
            </div>
        </form>
    </div>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="{{ route('admin.journal.assignments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Илова кардан
    </a>
</div>

{{-- Рӯйхати таъинотҳо --}}
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Фан</th>
                        <th class="text-center">Кредит</th>
                        <th>Гурӯҳ</th>
                        <th>Омӯзгор</th>
                        <th>Навъ</th>
                        <th>Семестр</th>
                        <th class="text-center">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assignments as $assignment)
                    <tr>
                        <td class="fw-semibold">{{ $assignment->subject?->name }}</td>
                        <td class="text-center"><span class="badge bg-primary">{{ $assignment->credits }}</span></td>
                        <td><span class="badge bg-info">{{ $assignment->group?->name }}</span></td>
                        <td>{{ $assignment->teacher?->short_name ?? '—' }}</td>
                        <td>
                            @php
                            $typeLabel = match($assignment->lesson_type) {
                            'lecture' => 'Лексия',
                            'practice' => 'Амалӣ',
                            'lab' => 'Лабор.',
                            default => $assignment->lesson_type,
                            };
                            @endphp
                            <small>{{ $typeLabel }}</small>
                        </td>
                        <td><small>{{ $assignment->semester?->name }}</small></td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.journal.category-scores', $assignment) }}"
                                    class="btn btn-outline-info" title="Категорияҳо (5)">
                                    <i class="bi bi-grid-3x3"></i> Категория
                                </a>
                                <a href="{{ route('admin.journal.semester-grades', $assignment) }}"
                                    class="btn btn-outline-warning" title="Рейтинг/Имтиҳон">
                                    <i class="bi bi-trophy"></i> Рейтинг
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                            Таъинот ёфт нашуд.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($assignments->hasPages())
    <div class="card-footer bg-white">{{ $assignments->links() }}</div>
    @endif
</div>
@endsection