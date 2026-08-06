@extends('layouts.app')

@section('title', $teacher->user?->full_name)
@section('page-header', $teacher->user?->full_name)
@section('page-description')
    {{ $teacher->position }} | {{ $teacher->department?->name }}
@endsection

@section('page-actions')
    <a href="{{ route('admin.teachers.edit', $teacher) }}" class="btn btn-outline-primary">
        <i class="bi bi-pencil me-1"></i> Таҳрир
    </a>
@endsection

@section('content')
<div class="row g-4">
    {{-- Маълумоти омӯзгор --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 80px; height: 80px; font-size: 2rem; font-weight: 600;">
                    {{ mb_substr($teacher->user?->first_name, 0, 1) }}{{ mb_substr($teacher->user?->last_name, 0, 1) }}
                </div>
                <h5 class="mb-1">{{ $teacher->user?->full_name }}</h5>
                <p class="text-muted mb-1">{{ $teacher->position }}</p>
                @if($teacher->academic_degree)
                    <span class="badge bg-info">{{ $teacher->academic_degree }}</span>
                @endif
                @if($teacher->academic_title)
                    <span class="badge bg-primary">{{ $teacher->academic_title }}</span>
                @endif
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Рақам:</td><td><code>{{ $teacher->employee_id }}</code></td></tr>
                    <tr><td class="text-muted">Кафедра:</td><td>{{ $teacher->department?->name }}</td></tr>
                    <tr><td class="text-muted">Факултет:</td><td>{{ $teacher->department?->faculty?->name }}</td></tr>
                    <tr><td class="text-muted">Навъ:</td><td>
                        {{ match($teacher->employment_type) { 'full_time' => 'Доимӣ', 'part_time' => 'Нимшатота', 'hourly' => 'Соатбайъ', default => $teacher->employment_type } }}
                    </td></tr>
                    <tr><td class="text-muted">Ставка:</td><td>{{ $teacher->rate }}</td></tr>
                    <tr><td class="text-muted">Санаи қабул:</td><td>{{ $teacher->hire_date?->format('d.m.Y') }}</td></tr>
                    <tr><td class="text-muted">Таваллуд:</td><td>{{ $teacher->birth_date?->format('d.m.Y') ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Телефон:</td><td>{{ $teacher->user?->phone ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Email:</td><td>{{ $teacher->user?->email ?? '—' }}</td></tr>
                    <tr>
                        <td class="text-muted">Ҳолат:</td>
                        <td>
                            @php
                                $statusBadge = match($teacher->status) {
                                    'active' => 'bg-success',
                                    'on_leave' => 'bg-warning',
                                    'dismissed' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $statusBadge }}">
                                {{ match($teacher->status) { 'active' => 'Фаъол', 'on_leave' => 'Рухсатӣ', 'dismissed' => 'Рафта', default => $teacher->status } }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        @if($teacher->biography)
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0">Биография</h6></div>
            <div class="card-body"><p class="mb-0">{{ $teacher->biography }}</p></div>
        </div>
        @endif
    </div>

    {{-- Фанҳо ва борбандӣ --}}
    <div class="col-lg-8">
        {{-- Борбандии ин семестр --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white d-flex justify-content-between">
                <h6 class="mb-0"><i class="bi bi-book me-2"></i> Фанҳо дар семестри ҷорӣ</h6>
                @if($currentSemester)
                    <span class="badge bg-info">{{ $currentSemester->name }}</span>
                @endif
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Фан</th>
                            <th>Гурӯҳ</th>
                            <th>Навъ</th>
                            <th>Соат/ҳафта</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($currentAssignments as $assignment)
                            <tr>
                                <td>{{ $assignment->curriculum?->subject?->name }}</td>
                                <td><span class="badge bg-primary">{{ $assignment->group?->name }}</span></td>
                                <td>
                                    @php
                                        $typeLabel = match($assignment->lesson_type) {
                                            'lecture' => 'Лексия',
                                            'practice' => 'Амалӣ',
                                            'lab' => 'Лабораторӣ',
                                            default => $assignment->lesson_type,
                                        };
                                    @endphp
                                    {{ $typeLabel }}
                                </td>
                                <td>{{ $assignment->hours_per_week }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-3">Таъинот мавҷуд нест.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($currentAssignments->isNotEmpty())
                <div class="card-footer bg-white">
                    <strong>Маҷмӯи ҳафтаина: {{ $currentAssignments->sum('hours_per_week') }} соат</strong>
                    | Гурӯҳҳо: {{ $currentAssignments->pluck('group_id')->unique()->count() }}
                    | Фанҳо: {{ $currentAssignments->pluck('curriculum.subject.name')->unique()->count() }}
                </div>
            @endif
        </div>

        {{-- Таърихи фаъолият --}}
        @if($teacher->activityLog->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i> Таърихи фаъолият</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Сана</th><th>Навъ</th><th>Тавсиф</th></tr>
                    </thead>
                    <tbody>
                        @foreach($teacher->activityLog->sortByDesc('activity_date')->take(10) as $log)
                            <tr>
                                <td><small>{{ $log->activity_date->format('d.m.Y') }}</small></td>
                                <td>
                                    @php
                                        $actLabel = match($log->activity_type) {
                                            'hired' => ['Қабул', 'bg-success'],
                                            'promotion' => ['Тарақии', 'bg-primary'],
                                            'department_change' => ['Кафедра', 'bg-info'],
                                            'degree_obtained' => ['Дараҷа', 'bg-warning'],
                                            default => [$log->activity_type, 'bg-secondary'],
                                        };
                                    @endphp
                                    <span class="badge {{ $actLabel[1] }}">{{ $actLabel[0] }}</span>
                                </td>
                                <td>{{ $log->description }}</td>
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
