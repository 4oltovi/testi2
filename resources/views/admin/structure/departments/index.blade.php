@extends('layouts.app')

@section('title', 'Кафедраҳо')
@section('page-header', 'Кафедраҳо')
@section('page-description', 'Идоракунии кафедраҳо')

@section('page-actions')
    <a href="{{ route('admin.structure.departments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Кафедраи нав
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Ном ё рамзи кафедра..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="faculty_id" class="form-select">
                        <option value="">Ҳама факултетҳо</option>
                        @foreach($faculties as $faculty)
                            <option value="{{ $faculty->id }}" {{ request('faculty_id') == $faculty->id ? 'selected' : '' }}>
                                {{ $faculty->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Ҷустуҷӯ</button>
                    <a href="{{ route('admin.structure.departments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                            <th>Кафедра</th>
                            <th>Рамз</th>
                            <th>Факултет</th>
                            <th>Мудир</th>
                            <th>Ихтисосҳо</th>
                            <th>Омӯзгорон</th>
                            <th>Фанҳо</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.structure.departments.show', $dept) }}" class="fw-semibold text-decoration-none">
                                        {{ $dept->name }}
                                    </a>
                                </td>
                                <td><code>{{ $dept->code }}</code></td>
                                <td><small>{{ $dept->faculty?->short_name }}</small></td>
                                <td><small>{{ $dept->head?->short_name ?? '—' }}</small></td>
                                <td><span class="badge bg-info">{{ $dept->specialties_count }}</span></td>
                                <td><span class="badge bg-success">{{ $dept->teachers_count }}</span></td>
                                <td><span class="badge bg-primary">{{ $dept->subjects_count }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.departments.edit', $dept) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.structure.departments.destroy', $dept) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" data-confirm="Нест кардан?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Кафедрае ёфт нашуд.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($departments->hasPages())
            <div class="card-footer bg-white">{{ $departments->links() }}</div>
        @endif
    </div>
@endsection
