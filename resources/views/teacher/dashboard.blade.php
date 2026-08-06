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
                                <h6 class="mb-2">{{ $a->curriculum?->subject?->name }}</h6>
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
@endsection
