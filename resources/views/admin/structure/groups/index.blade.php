@extends('layouts.app')

@section('title', 'Гурӯҳҳо')
@section('page-header', 'Гурӯҳҳо')
@section('page-description', 'Идоракунии гурӯҳҳои донишҷӯён')

@section('page-actions')
    <a href="{{ route('admin.structure.groups.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Гурӯҳи нав
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Ном ё рамзи гурӯҳ..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="specialty_id" class="form-select">
                        <option value="">Ҳама ихтисосҳо</option>
                        @foreach($specialties as $specialty)
                            <option value="{{ $specialty->id }}" {{ request('specialty_id') == $specialty->id ? 'selected' : '' }}>
                                {{ $specialty->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-select">
                        <option value="">Ҳама курсҳо</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="academic_year_id" class="form-select">
                        <option value="">Ҳама солҳо</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.structure.groups.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
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
                            <th>Гурӯҳ</th>
                            <th>Ихтисос</th>
                            <th>Курс</th>
                            <th>Соли таҳсилӣ</th>
                            <th>Донишҷӯён</th>
                            <th>Куратор</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.structure.groups.show', $group) }}" class="fw-semibold text-decoration-none">
                                        {{ $group->name }}
                                    </a>
                                    <br><small class="text-muted">{{ $group->code }}</small>
                                </td>
                                <td><small>{{ $group->specialty?->name }}</small></td>
                                <td>{{ $group->course?->name }}</td>
                                <td>{{ $group->academicYear?->name }}</td>
                                <td>
                                    <span class="badge bg-primary">{{ $group->active_students_count }}/{{ $group->max_students }}</span>
                                </td>
                                <td><small>{{ $group->curator?->short_name ?? '—' }}</small></td>
                                <td>
                                    @if($group->is_active)
                                        <span class="badge bg-success">Фаъол</span>
                                    @else
                                        <span class="badge bg-secondary">Ғайрифаъол</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" title="Таҳрир">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-people fs-1 d-block mb-2"></i>
                                    Гурӯҳе ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($groups->hasPages())
            <div class="card-footer bg-white">{{ $groups->links() }}</div>
        @endif
    </div>
@endsection
