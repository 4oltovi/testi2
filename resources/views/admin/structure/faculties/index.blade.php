@extends('layouts.app')

@section('title', 'Факултетҳо')
@section('page-header', 'Факултетҳо')
@section('page-description', 'Идоракунии факултетҳои муассиса')

@section('page-actions')
    <a href="{{ route('admin.structure.faculties.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Факултети нав
    </a>
@endsection

@section('content')
    {{-- Ҷустуҷӯ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" placeholder="Ҷустуҷӯ: ном, рамз..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Ҷустуҷӯ
                    </button>
                    <a href="{{ route('admin.structure.faculties.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвал --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Номи факултет</th>
                            <th>Рамз</th>
                            <th>Декан</th>
                            <th>Кафедраҳо</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($faculties as $faculty)
                            <tr>
                                <td>{{ $faculty->id }}</td>
                                <td>
                                    <a href="{{ route('admin.structure.faculties.show', $faculty) }}" class="text-decoration-none fw-semibold">
                                        {{ $faculty->name }}
                                    </a>
                                    @if($faculty->short_name)
                                        <br><small class="text-muted">{{ $faculty->short_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $faculty->code }}</code></td>
                                <td>{{ $faculty->dean?->full_name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-info">{{ $faculty->departments_count }}</span>
                                </td>
                                <td>
                                    @if($faculty->is_active)
                                        <span class="badge bg-success">Фаъол</span>
                                    @else
                                        <span class="badge bg-secondary">Ғайрифаъол</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.faculties.edit', $faculty) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.structure.faculties.destroy', $faculty) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                data-confirm="Оё мутмаин ҳастед, ки мехоҳед ин факултетро нест кунед?">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-building fs-1 d-block mb-2"></i>
                                    Факултете ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($faculties->hasPages())
            <div class="card-footer bg-white">{{ $faculties->links() }}</div>
        @endif
    </div>
@endsection
