@extends('layouts.app')

@section('title', 'Панели омӯзгор')

@section('page-header', 'Панели асосӣ')
@section('page-description')
<div class="d-flex align-items-center gap-2 flex-wrap">
    <span class="fw-semibold text-dark">Хуш омадед, {{ auth()->user()->first_name }}!</span>
    @if($semester)
    <span class="text-muted">|</span>
    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
        <i class="bi bi-calendar-event me-1"></i> {{ $semester->name }}
        @if($semester->academicYear)
        — {{ $semester->academicYear->name }}
        @endif
    </span>
    @endif
</div>
@endsection

@section('content')

{{-- Иловагиҳои CSS барои зебогии бештар --}}
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .08) !important;
    }

    .subject-card {
        transition: all 0.25s ease;
        border: 1px solid transparent;
    }

    .subject-card:hover {
        transform: translateY(-3px);
        border-color: #e2e8f0;
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .06) !important;
    }

    .subject-icon {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }

    .action-btn {
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .action-btn:hover {
        transform: scale(1.02);
    }

    .table-custom th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e9ecef;
        background-color: #f8f9fa;
    }

    .table-custom td {
        vertical-align: middle;
        padding: 1rem 0.75rem;
    }
</style>

{{-- Омор (Статистика) --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="subject-icon bg-primary bg-opacity-10 text-primary">
                    <i class="bi bi-book-half fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">{{ $assignments->count() }}</h3>
                    <small class="text-muted fw-medium">Фанҳои таълимӣ</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="subject-icon bg-success bg-opacity-10 text-success">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">{{ $groups_count }}</h3>
                    <small class="text-muted fw-medium">Гурӯҳҳои таълимӣ</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-12 col-lg-4">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="subject-icon bg-warning bg-opacity-10 text-warning">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">{{ $weekly_hours }}</h3>
                    <small class="text-muted fw-medium">Соатҳои ҳафтаина</small>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Фанҳои шумо --}}
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-journal-bookmark-fill me-2 text-primary"></i> Фанҳои таъиншуда
        </h6>
        <span class="badge bg-light text-dark border">
            {{ $assignments->count() }} ҷой
        </span>
    </div>
    <div class="card-body p-4">
        @if($assignments->isEmpty())
        <div class="text-center py-5">
            <div class="bg-light rounded-circle d-inline-flex p-4 mb-3">
                <i class="bi bi-inbox fs-1 text-muted"></i>
            </div>
            <h5 class="text-muted fw-normal">Дар ин семестр ба шумо фане таъин нашудааст.</h5>
        </div>
        @else
        <div class="row g-4">
            @foreach($assignments as $a)
            @php
            $lessonType = match($a->lesson_type) {
            'lecture' => 'Лексия',
            'practice' => 'Амалӣ',
            'lab' => 'Лабораторӣ',
            default => 'Дигар'
            };
            $typeColor = match($a->lesson_type) {
            'lecture' => 'primary',
            'practice' => 'success',
            'lab' => 'warning',
            default => 'secondary'
            };
            @endphp
            <div class="col-md-6 col-xl-4">
                <div class="card subject-card border-0 shadow-sm h-100 rounded-4">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <!-- Сарлавҳа: Иконка ва намуди дарс -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="subject-icon bg-{{ $typeColor }} bg-opacity-10 text-{{ $typeColor }}">
                                <i class="bi bi-book fs-4"></i>
                            </div>
                            <span class="badge bg-{{ $typeColor }} bg-opacity-10 text-{{ $typeColor }} border border-{{ $typeColor }} border-opacity-25 px-3 py-2 rounded-pill">
                                {{ $lessonType }}
                            </span>
                        </div>

                        <!-- Номи фанн (калон ва равшан) -->
                        <h5 class="fw-bold text-dark mb-2 text-truncate" title="{{ $a->subjectAssignment?->subject?->name }}">
                            {{ $a->subject?->name ?? 'Номи фанн нест' }}
                        </h5>

                        <!-- Гурӯҳ -->
                        <div class="mb-4">
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                                <i class="bi bi-people-fill me-1 text-muted"></i>
                                <span class="fw-semibold">{{ $a->group?->name ?? 'Гурӯҳ нест' }}</span>
                            </span>
                        </div>

                        <!-- Тугмаҳои амал (Фақат Давомот ва Рейтинг) -->
                        <div class="mt-auto d-grid gap-2">
                            <a href="{{ route('teacher.journal.attendance', $a) }}" class="btn btn-outline-success action-btn rounded-3 py-2">
                                <i class="bi bi-calendar-check me-2"></i> Давомот
                            </a>
                            <a href="{{ route('teacher.journal.semester-grades', $a) }}" class="btn btn-outline-primary action-btn rounded-3 py-2">
                                <i class="bi bi-graph-up-arrow me-2"></i> Рейтинг
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Огоҳиҳо дар бораи тағйироти охирин --}}
@if(isset($recentChanges) && $recentChanges->isNotEmpty())
<div class="card border-0 shadow-sm mt-4 rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-bell-fill me-2 text-warning"></i> Тағйироти охирин (7 рӯзи охир)
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-hover mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Сана ва вақт</th>
                        <th>Фан</th>
                        <th>Донишҷӯ</th>
                        <th>Категория</th>
                        <th class="text-center">Баҳо</th>
                        <th class="text-end pe-4">Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentChanges as $change)
                    <tr>
                        <td class="ps-4 text-nowrap text-muted small">
                            <i class="bi bi-clock me-1"></i>
                            {{ $change->locked_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td>
                            <span class="fw-medium text-dark">{{ $change->subjectAssignment?->subject?->name ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 600;">
                                    {{ substr($change->student?->user?->full_name ?? '?', 0, 1) }}
                                </div>
                                <span class="small">{{ $change->student?->user?->full_name ?? 'Номаълум' }}</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $change->category->colorClass() ?? 'secondary' }} bg-opacity-10 text-{{ $change->category->colorClass() ?? 'secondary' }} border border-{{ $change->category->colorClass() ?? 'secondary' }} border-opacity-25">
                                {{ $change->category->shortLabel() ?? '—' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold text-dark">{{ $change->score }}</span>
                            <span class="text-muted small">/ {{ $change->max_score }}</span>
                        </td>
                        <td class="text-end pe-4">
                            @if($change->is_locked)
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                <i class="bi bi-lock-fill me-1"></i> Қулф шуд
                            </span>
                            @else
                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-3 py-2 rounded-pill">
                                <i class="bi bi-unlock me-1"></i> Озод
                            </span>
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