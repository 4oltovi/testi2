@extends('layouts.app')

@section('title', 'Маълумоти кафедра: ' . $department->name)
@section('page-header', 'Маълумоти кафедра')
@section('page-description', $department->name)

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- Маълумоти асосӣ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумоти асосӣ</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Рамз</th>
                            <td>{{ $department->code }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ихтисор</th>
                            <td>{{ $department->short_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Факултет</th>
                            <td>{{ $department->faculty?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Мудир</th>
                            <td>{{ $department->head?->full_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Телефон</th>
                            <td>{{ $department->phone ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳолат</th>
                            <td>
                                @if($department->is_active)
                                    <span class="badge bg-success">Фаъол</span>
                                @else
                                    <span class="badge bg-secondary">Ғайрифаъол</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ихтисосҳо --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Ихтисосҳо</h6>
            </div>
            <div class="card-body">
                @if($department->specialties && $department->specialties->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ном</th>
                                <th>Рамз</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($department->specialties as $specialty)
                                <tr>
                                    <td>{{ $specialty->name }}</td>
                                    <td>{{ $specialty->code }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0">Ихтисосҳо ёфт нашуданд</p>
                @endif
            </div>
        </div>

        {{-- Муаллимон --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i> Муаллимон</h6>
            </div>
            <div class="card-body">
                @if($department->teachers && $department->teachers->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ному насаб</th>
                                <th>Вазифа</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($department->teachers as $teacher)
                                <tr>
                                    <td>{{ $teacher->user->full_name }}</td>
                                    <td>{{ $teacher->position }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0">Муаллимон ёфт нашуданд</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <a href="{{ route('admin.structure.departments.edit', $department) }}" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Таҳрир кардан
                </a>
                <a href="{{ route('admin.structure.departments.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
