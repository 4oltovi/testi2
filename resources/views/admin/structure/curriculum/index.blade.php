@extends('layouts.app')

@section('title', 'Нақшаи таълимӣ')
@section('page-header', 'Нақшаи таълимӣ (Учебный план)')
@section('page-description', 'Тақсимоти фанҳо аз рӯйи ихтисос, курс ва семестр')

@section('page-actions')
    <a href="{{ route('admin.structure.curriculum.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Илова кардан
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <select name="specialty_id" class="form-select">
                        <option value="">Ҳама ихтисосҳо</option>
                        @foreach($specialties as $spec)
                            <option value="{{ $spec->id }}" {{ request('specialty_id') == $spec->id ? 'selected' : '' }}>
                                {{ $spec->name }}
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
                <div class="col-md-3">
                    <select name="semester_id" class="form-select">
                        <option value="">Ҳама семестрҳо</option>
                        @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>
                                {{ $sem->name }} — {{ $sem->academicYear?->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-filter"></i></button>
                    <a href="{{ route('admin.structure.curriculum.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвал --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Фан</th>
                            <th>Ихтисос</th>
                            <th>Курс</th>
                            <th>Семестр</th>
                            <th>Кредит</th>
                            <th>Соатҳо</th>
                            <th>Навъ</th>
                            <th>Интихобӣ</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($curriculum as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item->subject?->name }}</td>
                                <td><small>{{ $item->specialty?->name }}</small></td>
                                <td>{{ $item->course?->name }}</td>
                                <td>{{ $item->semester?->name }}</td>
                                <td><span class="badge bg-primary">{{ $item->credits }}</span></td>
                                <td><small>{{ $item->total_hours }} (Л:{{ $item->lecture_hours }} А:{{ $item->practice_hours }} М:{{ $item->independent_hours }})</small></td>
                                <td>
                                    @php
                                        $examLabel = match($item->exam_type) {
                                            'exam' => 'Имт.',
                                            'credit' => 'Синҷ.',
                                            'diff_credit' => 'Синҷ.Б',
                                            default => $item->exam_type,
                                        };
                                    @endphp
                                    <small>{{ $examLabel }}</small>
                                </td>
                                <td>
                                    @if($item->is_elective)
                                        <span class="badge bg-warning">Интихобӣ</span>
                                    @else
                                        <span class="badge bg-secondary">Ҳатмӣ</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.curriculum.edit', $item) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('admin.structure.curriculum.destroy', $item) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" data-confirm="Аз нақша хориҷ кардан?"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Нақшаи таълимӣ холӣ аст.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($curriculum->hasPages())
            <div class="card-footer bg-white">{{ $curriculum->links() }}</div>
        @endif
    </div>
@endsection
