@extends('layouts.app')

@section('title', 'Аудиторияҳо')
@section('page-header', 'Аудиторияҳо')
@section('page-description', 'Идоракунии аудиторияҳо ва хонаҳои дарсӣ')

@section('page-actions')
    <a href="{{ route('admin.structure.classrooms.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Аудиторияи нав
    </a>
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Рақам ё бино..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="type" class="form-select">
                        <option value="">Ҳама навъҳо</option>
                        <option value="lecture" {{ request('type') == 'lecture' ? 'selected' : '' }}>Лексионӣ</option>
                        <option value="practice" {{ request('type') == 'practice' ? 'selected' : '' }}>Амалӣ</option>
                        <option value="lab" {{ request('type') == 'lab' ? 'selected' : '' }}>Лабораторӣ</option>
                        <option value="computer" {{ request('type') == 'computer' ? 'selected' : '' }}>Компютерӣ</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="building" class="form-select">
                        <option value="">Ҳама биноҳо</option>
                        @foreach($buildings as $bld)
                            <option value="{{ $bld }}" {{ request('building') == $bld ? 'selected' : '' }}>{{ $bld }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.structure.classrooms.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                            <th>Аудитория</th>
                            <th>Бино</th>
                            <th>Ошёна</th>
                            <th>Навъ</th>
                            <th>Ҷойгоҳ</th>
                            <th>Тачҳизот</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($classrooms as $room)
                            <tr>
                                <td class="fw-semibold">{{ $room->name }}</td>
                                <td>{{ $room->building ?? '—' }}</td>
                                <td>{{ $room->floor ?? '—' }}</td>
                                <td>
                                    @php
                                        $typeLabel = match($room->type) {
                                            'lecture' => 'Лексионӣ',
                                            'practice' => 'Амалӣ',
                                            'lab' => 'Лабораторӣ',
                                            'computer' => 'Компютерӣ',
                                            'gym' => 'Варзишгоҳ',
                                            default => 'Дигар',
                                        };
                                    @endphp
                                    {{ $typeLabel }}
                                </td>
                                <td><span class="badge bg-info">{{ $room->capacity }}</span></td>
                                <td>
                                    @if($room->has_projector) <i class="bi bi-projector text-primary" title="Проектор"></i> @endif
                                    @if($room->has_computers) <i class="bi bi-pc-display text-success" title="Компютер"></i> @endif
                                </td>
                                <td>
                                    @if($room->is_active)
                                        <span class="badge bg-success">Фаъол</span>
                                    @else
                                        <span class="badge bg-secondary">Ғайрифаъол</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.classrooms.edit', $room) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.structure.classrooms.destroy', $room) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" data-confirm="Нест кардан?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">Аудиторияе ёфт нашуд.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($classrooms->hasPages())
            <div class="card-footer bg-white">{{ $classrooms->links() }}</div>
        @endif
    </div>
@endsection
