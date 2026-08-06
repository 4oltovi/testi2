@extends('layouts.app')

@section('title', 'Омӯзгорон')
@section('page-header', 'Омӯзгорон')
@section('page-description', 'Идоракунии омӯзгорон')

@section('page-actions')
    <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Омӯзгори нав
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Ном, насаб ё рақами корм..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">Ҳама кафедраҳо</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Ҳама</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                        <option value="on_leave" {{ request('status') == 'on_leave' ? 'selected' : '' }}>Рухсатӣ</option>
                        <option value="dismissed" {{ request('status') == 'dismissed' ? 'selected' : '' }}>Рафта</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Омӯзгор</th>
                            <th>Рақами корм.</th>
                            <th>Кафедра</th>
                            <th>Вазифа</th>
                            <th>Дараҷа</th>
                            <th>Ставка</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($teachers as $teacher)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="text-decoration-none fw-semibold">
                                        {{ $teacher->user?->full_name }}
                                    </a>
                                </td>
                                <td><code>{{ $teacher->employee_id }}</code></td>
                                <td><small>{{ $teacher->department?->short_name ?? $teacher->department?->name }}</small></td>
                                <td>{{ $teacher->position }}</td>
                                <td><small>{{ $teacher->academic_degree ?? '—' }}</small></td>
                                <td>{{ $teacher->rate }}</td>
                                <td>
                                    @php
                                        $statusBadge = match($teacher->status) {
                                            'active' => 'bg-success',
                                            'on_leave' => 'bg-warning',
                                            'dismissed' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                        $statusLabel = match($teacher->status) {
                                            'active' => 'Фаъол',
                                            'on_leave' => 'Рухсатӣ',
                                            'dismissed' => 'Рафта',
                                            default => $teacher->status,
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Омӯзгоре ёфт нашуд.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($teachers->hasPages())
            <div class="card-footer bg-white">{{ $teachers->links() }}</div>
        @endif
    </div>
@endsection
