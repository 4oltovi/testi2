@extends('layouts.app')

@section('title', 'Саволномаҳои рейтинг')
@section('page-header', 'Саволномаҳои рейтинг')
@section('page-description', 'Саволҳои алоҳида барои рейтингҳои онлайн (R1/R2)')

@section('content')
<div class="row g-4">
    {{-- ==================== МЕНЮИ ФАНҲО ==================== --}}
    <div class="col-lg-3">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-book me-2"></i>Фанҳо</h6>
                <a href="{{ route('admin.rating-questions.import-form') }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-upload"></i> Импорт
                </a>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.rating-questions.index') }}"
                   class="list-group-item list-group-item-action {{ !$subjectId ? 'active' : '' }}">
                    <i class="bi bi-grid me-2"></i>Ҳамаи фанҳо
                </a>
                @foreach($subjects as $s)
                <a href="{{ route('admin.rating-questions.index', ['subject_id' => $s->id]) }}"
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $subjectId == $s->id ? 'active' : '' }}">
                    <span><i class="bi bi-journal-text me-2"></i>{{ $s->name }}</span>
                    <span class="badge bg-{{ $subjectId == $s->id ? 'light text-dark' : 'secondary' }} rounded-pill">
                        {{ $counts[$s->id] ?? 0 }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ==================== МАЪЛУМОТИ ФАН ИНТИХОБШУДА ==================== --}}
    <div class="col-lg-9">
        @if($subjectId)
            @php
                $selectedSubject = $subjects->firstWhere('id', $subjectId);
            @endphp

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>{{ $selectedSubject->name }}</h6>
                        <small class="text-muted">Саволҳои рейтинг</small>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('admin.rating-questions.export', ['subject_id' => $subjectId]) }}" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-excel"></i> Excel
                        </a>
                        <a href="{{ route('admin.rating-questions.import-form') }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-upload"></i> Импорт
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.rating-questions.store') }}" class="row g-3">
                        @csrf
                        <input type="hidden" name="subject_id" value="{{ $subjectId }}">

                        <div class="col-md-12">
                            <label class="form-label">Матни савол <span class="text-danger">*</span></label>
                            <textarea name="question_text" rows="2" class="form-control" required>{{ old('question_text') }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">4 вариант <span class="text-danger">*</span> <small class="text-muted">(радиои дурустро интихоб кунед)</small></label>
                            @for($i = 0; $i < 4; $i++)
                                <div class="input-group mb-2">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="radio" name="correct" value="{{ $i }}"
                                            {{ old('correct') == $i ? 'checked' : '' }} required>
                                    </div>
                                    <input type="text" name="options[{{ $i }}]" class="form-control"
                                        placeholder="Варианти {{ $i + 1 }}" required value="{{ old("options.$i") }}">
                                </div>
                            @endfor
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Душворӣ</label>
                            <select name="difficulty_level" class="form-select">
                                <option value="1">Осон</option>
                                <option value="2">Миёна</option>
                                <option value="3">Душвор</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Илова кардан
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Саволҳои фан</h6>
                    <span class="badge bg-primary">{{ $counts[$subjectId] ?? 0 }} савол</span>
                </div>
                <div class="card-body p-0">
                    @forelse($questions as $q)
                    <div class="p-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <p class="mb-1">{{ $q->question_text }}</p>
                                <small class="text-muted">
                                    @foreach($q->answerOptions as $opt)
                                        <span class="me-3 {{ $opt->is_correct ? 'text-success fw-bold' : 'text-muted' }}">
                                            {{ $opt->sort_order }}. {{ $opt->option_text }}
                                        </span>
                                    @endforeach
                                </small>
                            </div>
                            <form method="POST" action="{{ route('admin.rating-questions.destroy', $q) }}"
                                onsubmit="return confirm('Савол нест шавад?')" class="ms-2">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    @empty
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-hand-index fs-1 d-block mb-2"></i>
                        Ҳанӯз савол нест.
                    </div>
                    @endforelse
                </div>
            </div>
        @else
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-journal-text fs-1 text-muted d-block mb-3"></i>
                    <h5 class="text-muted">Фанро интихоб кунед</h5>
                    <p class="text-muted">Барои дидани саволҳои рейтинг, фанро аз менюи чап интихоб кунед.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
