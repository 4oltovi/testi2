@extends('layouts.app')

@section('title', $faculty->name)
@section('page-header', $faculty->name)
@section('page-description', 'Маълумот дар бораи факултет')

@section('page-actions')
    <a href="{{ route('admin.structure.faculties.edit', $faculty) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil me-1"></i> Таҳрир
    </a>
@endsection

@section('content')
    <div class="row g-4">
        {{-- Маълумоти умумӣ --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr><td class="text-muted">Рамз:</td><td><code>{{ $faculty->code }}</code></td></tr>
                        <tr><td class="text-muted">Ихтисор:</td><td>{{ $faculty->short_name ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Декан:</td><td>{{ $faculty->dean?->full_name ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Телефон:</td><td>{{ $faculty->phone ?? '—' }}</td></tr>
                        <tr><td class="text-muted">Email:</td><td>{{ $faculty->email ?? '—' }}</td></tr>
                        <tr>
                            <td class="text-muted">Ҳолат:</td>
                            <td>
                                @if($faculty->is_active)
                                    <span class="badge bg-success">Фаъол</span>
                                @else
                                    <span class="badge bg-secondary">Ғайрифаъол</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Кафедраҳо --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-diagram-3 me-2"></i> Кафедраҳо ({{ $faculty->departments->count() }})</h6>
                    <a href="{{ route('admin.structure.departments.create') }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Иловагӣ
                    </a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Кафедра</th>
                                <th>Рамз</th>
                                <th>Мудир</th>
                                <th>Ихтисосҳо</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($faculty->departments as $dept)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.structure.departments.show', $dept) }}">{{ $dept->name }}</a>
                                    </td>
                                    <td><code>{{ $dept->code }}</code></td>
                                    <td>{{ $dept->head?->full_name ?? '—' }}</td>
                                    <td><span class="badge bg-info">{{ $dept->specialties->count() }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">Кафедрае мавҷуд нест.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
