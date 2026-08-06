@extends('layouts.app')

@section('title', 'Эҷоди тести нав')
@section('page-header', 'Эҷоди тести нав')

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('teacher.exams.store') }}">
                @csrf

                <div class="row g-3">
                    {{-- Фан/Гурӯҳ --}}
                    <div class="col-md-6">
                        <label class="form-label">Фан ва гурӯҳ <span class="text-danger">*</span></label>
                        <select name="subject_assignment_id" class="form-select" required>
                            <option value="">— Интихоб кунед —</option>
                            @foreach($assignments as $a)
                                <option value="{{ $a->id }}" {{ old('subject_assignment_id') == $a->id ? 'selected' : '' }}>
                                    {{ $a->curriculum?->subject?->name }} — {{ $a->group?->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('subject_assignment_id') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Навъи имтиҳон --}}
                    <div class="col-md-3">
                        <label class="form-label">Навъи имтиҳон <span class="text-danger">*</span></label>
                        <select name="exam_type" class="form-select" required>
                            <option value="main" {{ old('exam_type') == 'main' ? 'selected' : '' }}>Имтиҳони асосӣ</option>
                            <option value="rating1" {{ old('exam_type') == 'rating1' ? 'selected' : '' }}>Рейтинги 1</option>
                            <option value="rating2" {{ old('exam_type') == 'rating2' ? 'selected' : '' }}>Рейтинги 2</option>
                            <option value="midterm" {{ old('exam_type') == 'midterm' ? 'selected' : '' }}>Миёнасеместрӣ</option>
                            <option value="quiz" {{ old('exam_type') == 'quiz' ? 'selected' : '' }}>Тести кӯтоҳ</option>
                            <option value="retake" {{ old('exam_type') == 'retake' ? 'selected' : '' }}>Такрорсупорӣ</option>
                            <option value="retake_commission" {{ old('exam_type') == 'retake_commission' ? 'selected' : '' }}>Комиссионӣ</option>
                        </select>
                    </div>

                    {{-- Формат --}}
                    <div class="col-md-3">
                        <label class="form-label">Формат <span class="text-danger">*</span></label>
                        <select name="format" class="form-select" required>
                            <option value="online_test" {{ old('format', 'online_test') == 'online_test' ? 'selected' : '' }}>Тести онлайн</option>
                            <option value="written" {{ old('format') == 'written' ? 'selected' : '' }}>Хаттӣ</option>
                            <option value="oral" {{ old('format') == 'oral' ? 'selected' : '' }}>Даҳонӣ</option>
                            <option value="mixed" {{ old('format') == 'mixed' ? 'selected' : '' }}>Омехта</option>
                        </select>
                    </div>

                    {{-- Номи тест --}}
                    <div class="col-md-12">
                        <label class="form-label">Номи тест <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Масалан: Тести ниҳоӣ аз фани Информатика" required>
                        @error('title') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    {{-- Тавсиф --}}
                    <div class="col-md-12">
                        <label class="form-label">Тавсиф</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Шарҳи иловагӣ (ихтиёрӣ)">{{ old('description') }}</textarea>
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    {{-- Танзимоти тест --}}
                    <div class="col-12">
                        <h6><i class="bi bi-sliders me-2"></i> Танзимоти тест</h6>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Вақт (дақиқа) <span class="text-danger">*</span></label>
                        <input type="number" name="duration_minutes" class="form-control"
                               value="{{ old('duration_minutes', $testSettings['total_time']) }}" min="1" max="300" required>
                        <small class="text-muted">Пешниҳод: {{ $testSettings['total_time'] }} дақиқа</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Шумораи саволҳо <span class="text-danger">*</span></label>
                        <input type="number" name="total_questions_count" class="form-control"
                               value="{{ old('total_questions_count', $testSettings['total_questions']) }}" min="1" max="100" required>
                        <small class="text-muted">Пешниҳод: {{ $testSettings['total_questions'] }}</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Ҳадди гузариш (%) <span class="text-danger">*</span></label>
                        <input type="number" name="passing_score" class="form-control"
                               value="{{ old('passing_score', 50) }}" min="0" max="100" step="0.01" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Шумораи кӯшишҳо <span class="text-danger">*</span></label>
                        <input type="number" name="max_attempts" class="form-control"
                               value="{{ old('max_attempts', 1) }}" min="1" max="5" required>
                    </div>

                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1"
                                   id="shuffle_q" {{ old('shuffle_questions', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="shuffle_q">Тасодуфӣ кардани саволҳо</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="shuffle_answers" value="1"
                                   id="shuffle_a" {{ old('shuffle_answers', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="shuffle_a">Тасодуфӣ кардани ҷавобҳо</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="show_results_immediately" value="1"
                                   id="show_res" {{ old('show_results_immediately') ? 'checked' : '' }}>
                            <label class="form-check-label" for="show_res">Натиҷаро фавран нишон деҳ</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="allow_back_navigation" value="1"
                                   id="back_nav" {{ old('allow_back_navigation', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="back_nav">Бозгашт ба саволи пеш</label>
                        </div>
                    </div>

                    <div class="col-12"><hr class="my-2"></div>

                    {{-- Вақти оғоз/анҷом --}}
                    <div class="col-md-6">
                        <label class="form-label">Оғоз (ихтиёрӣ)</label>
                        <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Анҷом (ихтиёрӣ)</label>
                        <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                    </div>
                </div>

                <div class="text-end mt-4">
                    <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary me-2">Бекор</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Сохтан
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
