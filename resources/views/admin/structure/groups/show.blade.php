@extends('layouts.app')

@section('title', 'Гурӯҳ: ' . $group->name)
@section('page-header', 'Гурӯҳ: ' . $group->name)
@section('page-description')
    {{ $group->specialty?->name }} | {{ $group->course?->name }} | {{ $group->academicYear?->name }}
@endsection

@section('page-actions')
    <a href="{{ route('admin.structure.groups.edit', $group) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil me-1"></i> Таҳрир
    </a>
@endsection

@section('content')
    <div class="row g-4">
        {{-- Маълумот --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Рамз:</td><td><code>{{ $group->code }}</code></td></tr>
                        <tr><td class="text-muted">Ихтисос:</td><td>{{ $group->specialty?->name }}</td></tr>
                        <tr><td class="text-muted">Факултет:</td><td>{{ $group->specialty?->department?->faculty?->name }}</td></tr>
                        <tr><td class="text-muted">Курс:</td><td>{{ $group->course?->name }}</td></tr>
                        <tr><td class="text-muted">Сол:</td><td>{{ $group->academicYear?->name }}</td></tr>
                        <tr><td class="text-muted">Куратор:</td><td>{{ $group->curator?->full_name ?? '—' }}</td></tr>
                        <tr>
                            <td class="text-muted">Донишҷӯён:</td>
                            <td><span class="badge bg-primary">{{ $group->activeStudents->count() }}/{{ $group->max_students }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Рӯйхати донишҷӯён --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-people me-2"></i> Донишҷӯён ({{ $group->activeStudents->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Насаб ва ном</th>
                                <th>Рақами дониш.</th>
                                <th>GPA</th>
                                <th>Ҳолат</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($group->activeStudents->sortBy('user.last_name') as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <a href="{{ route('admin.students.show', $student) }}">
                                            {{ $student->user?->full_name }}
                                        </a>
                                    </td>
                                    <td><code>{{ $student->student_id_number }}</code></td>
                                    <td>
                                        <strong class="{{ $student->cumulative_gpa >= 3.0 ? 'text-success' : ($student->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                                            {{ number_format($student->cumulative_gpa, 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($student->has_debts)
                                            <span class="badge bg-danger">Қарздор</span>
                                        @else
                                            <span class="badge bg-success">Тоза</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">Донишҷӯе сабт нашудааст.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Фанҳо --}}
            @if($group->subjectAssignments->isNotEmpty())
            <div class="card border-0 shadow-sm mt-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-book me-2"></i> Фанҳои ин семестр</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Фан</th>
                                <th>Омӯзгор</th>
                                <th>Навъ</th>
                                <th>Соат/ҳафта</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($group->subjectAssignments as $assignment)
                                <tr>
                                    <td>{{ $assignment->curriculum?->subject?->name }}</td>
                                    <td>{{ $assignment->teacher?->short_name ?? '—' }}</td>
                                    <td>
                                        @php
                                            $typeLabel = match($assignment->lesson_type) {
                                                'lecture' => 'Лексия',
                                                'practice' => 'Амалӣ',
                                                'lab' => 'Лабораторӣ',
                                                default => $assignment->lesson_type,
                                            };
                                        @endphp
                                        <span class="badge bg-secondary">{{ $typeLabel }}</span>
                                    </td>
                                    <td>{{ $assignment->hours_per_week }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection
