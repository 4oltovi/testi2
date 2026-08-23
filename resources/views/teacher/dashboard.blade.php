@extends('layouts.app')

@section('title', 'Панели омӯзгор')
@section('page-header', 'Панели асосӣ')
@section('page-description')
Хуш омадед, {{ auth()->user()->first_name }}!
@if($semester)
| {{ $semester->name }} — {{ $semester->academicYear?->name ?? '' }}
@endif
@endsection

@section('content')
{{-- Омор --}}
<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                    <i class="bi bi-book fs-4 text-primary"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ $assignments->count() }}</h3>
                    <small class="text-muted">Фан</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                    <i class="bi bi-people fs-4 text-success"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ $groups_count }}</h3>
                    <small class="text-muted">Гурӯҳ</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3">
                    <i class="bi bi-clock fs-4 text-warning"></i>
                </div>
                <div>
                    <h3 class="mb-0">{{ $weekly_hours }}</h3>
                    <small class="text-muted">Соат/ҳафта</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Фанҳо --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Фанҳои шумо</h6>
    </div>
    <div class="card-body">
        @if($assignments->isEmpty())
        <p class="text-muted text-center py-3">Дар ин семестр фане таъин нашудааст.</p>
        @else
        <div class="row g-3">
            @foreach($assignments as $a)
            <div class="col-md-6 col-lg-4">
                <div class="border rounded p-3 h-100">
                    <h6 class="mb-2">{{ $a->subjectAssignment?->subject?->name }}</h6>
                    <p class="mb-1"><span class="badge bg-info">{{ $a->group?->name }}</span>
                        <small class="text-muted ms-1">{{ match($a->lesson_type) { 'lecture' => 'Лексия', 'practice' => 'Амалӣ', 'lab' => 'Лаб.', default => '' } }}</small>
                    </p>
                    <div class="btn-group btn-group-sm w-100 mt-2">
                        <a href="{{ route('teacher.journal.attendance', $a) }}" class="btn btn-outline-success">Давомот</a>
                        <a href="{{ route('teacher.journal.grades', $a) }}" class="btn btn-outline-primary">Баҳоҳо</a>
                        <a href="{{ route('teacher.journal.semester-grades', $a) }}" class="btn btn-outline-warning">Рейтинг</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Огоҳиҳо дар бораи тағйироти охирин --}}
@if($recentChanges->isNotEmpty())
<div class="card border-0 shadow-sm mt-3">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-bell me-2"></i> Тағйироти охирин (7 рӯзи охир)</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Сана</th>
                        <th>Фан</th>
                        <th>Донишҷӯ</th>
                        <th>Категория</th>
                        <th>Бал</th>
                        <th>Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentChanges as $change)
                    <tr>
                        <td><small>{{ $change->locked_at?->format('d.m.Y H:i') }}</small></td>
                        <td>{{ $change->subjectAssignment?->subject?->name }}</td>
                        <td>{{ $change->student?->user?->full_name }}</td>
                        <td>
                            <span class="badge bg-{{ $change->category->colorClass() }}">
                                {{ $change->category->shortLabel() }}
                            </span>
                        </td>
                        <td><strong>{{ $change->score }}/{{ $change->max_score }}</strong></td>
                        <td>
                            @if($change->is_locked)
                                <span class="badge bg-success">Қулф шуд</span>
                            @else
                                <span class="badge bg-secondary">Озод</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection