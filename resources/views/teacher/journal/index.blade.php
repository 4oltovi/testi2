@extends('layouts.app')

@section('title', 'Журнали ман')
@section('page-header', 'Журнали электронӣ')
@section('page-description')
    Фанҳои шумо дар {{ $currentSemester?->name ?? 'семестри ҷорӣ' }}
@endsection

@section('content')
    @if($assignments->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="bi bi-journal-x fs-1 d-block mb-3"></i>
            <h5>Дар ин семестр фане таъин нашудааст.</h5>
            <p>Лутфан бо мудири кафедра тамос гиред.</p>
        </div>
    @else
        <div class="row g-3">
            @foreach($assignments as $assignment)
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-primary bg-opacity-10 border-0">
                            <h6 class="mb-0 text-primary">{{ $assignment->curriculum?->subject?->name }}</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-1">
                                <i class="bi bi-people me-1"></i>
                                <strong>Гурӯҳ:</strong> {{ $assignment->group?->name }}
                            </p>
                            <p class="mb-1">
                                <i class="bi bi-bookmark me-1"></i>
                                <strong>Навъ:</strong>
                                {{ match($assignment->lesson_type) { 'lecture' => 'Лексия', 'practice' => 'Амалӣ', 'lab' => 'Лабораторӣ', default => $assignment->lesson_type } }}
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-clock me-1"></i>
                                <strong>{{ $assignment->hours_per_week }} соат/ҳафта</strong>
                            </p>
                        </div>
                        <div class="card-footer bg-white border-0">
                            <div class="btn-group w-100">
                                <a href="{{ route('teacher.journal.category-scores', $assignment) }}" class="btn btn-sm btn-outline-info">
                                    <i class="bi bi-grid-3x3"></i> Категория
                                </a>
                                <a href="{{ route('teacher.journal.semester-grades', $assignment) }}" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-trophy"></i> Рейтинг
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
@endsection
