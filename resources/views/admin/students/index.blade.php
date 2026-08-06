@extends('layouts.app')

@section('title', 'Донишҷӯён')
@section('page-header', 'Донишҷӯён')
@section('page-description', 'Идоракунии донишҷӯён')

@section('page-actions')
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Донишҷӯи нав
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Ном, насаб ё рақами дон..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="group_id" class="form-select">
                        <option value="">Ҳама гурӯҳҳо</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-select">
                        <option value="">Ҳама курсҳо</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Ҳама ҳолатҳо</option>
                        @foreach(\App\Enums\StudentStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg"></i></a>
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
                            <th>Донишҷӯ</th>
                            <th>Рақами дон.</th>
                            <th>Гурӯҳ</th>
                            <th>Курс</th>
                            <th>GPA</th>
                            <th>Шакл</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $student->id }}</td>
                                <td>
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-decoration-none fw-semibold">
                                        {{ $student->user?->full_name }}
                                    </a>
                                </td>
                                <td><code>{{ $student->student_id_number }}</code></td>
                                <td><span class="badge bg-info">{{ $student->group?->name }}</span></td>
                                <td>{{ $student->course?->number }}</td>
                                <td>
                                    <strong class="{{ $student->cumulative_gpa >= 3.0 ? 'text-success' : ($student->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                                        {{ number_format($student->cumulative_gpa, 2) }}
                                    </strong>
                                </td>
                                <td>
                                    <small>{{ $student->education_form === 'budget' ? 'Б' : 'Ш' }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $student->status->badgeClass() }}">
                                        {{ $student->status->label() }}
                                    </span>
                                    @if($student->has_debts)
                                        <span class="badge bg-danger">Қарздор</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-info" title="Дидан">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary" title="Таҳрир">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                    Донишҷӯе ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($students->hasPages())
            <div class="card-footer bg-white">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
