@extends('layouts.app')

@section('title', 'Сохтани имтиҳони нав')
@section('page-header', 'Сохтани имтиҳони нав')
@section('page-description', 'Фанро интихоб кунед ва гурӯҳҳоро чекбокс кунед')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form action="{{ route('admin.exams.store') }}" method="POST">
            @csrf

            <div class="row g-3">
                {{-- Фан --}}
                <div class="col-12 col-md-6">
                    <label class="form-label">Фан <span class="text-danger">*</span></label>
                    <select name="subject_id" id="subjectSelect" class="form-select @error('subject_id') is-invalid @enderror" required>
                        <option value="">— Фанро интихоб кунед —</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Навъи имтиҳон --}}
                <div class="col-6 col-md-3">
                    <label class="form-label">Навъи имтиҳон <span class="text-danger">*</span></label>
                    <select name="exam_type" class="form-select" required>
                        <option value="main" {{ old('exam_type') == 'main' ? 'selected' : '' }}>Имтиҳони асосӣ</option>
                        <option value="rating1" {{ old('exam_type') == 'rating1' ? 'selected' : '' }}>Рейтинги 1</option>
                        <option value="rating2" {{ old('exam_type') == 'rating2' ? 'selected' : '' }}>Рейтинги 2</option>
                        <option value="quiz" {{ old('exam_type') == 'quiz' ? 'selected' : '' }}>Тести кӯтоҳ</option>
                        <option value="retake" {{ old('exam_type') == 'retake' ? 'selected' : '' }}>Такрорсупорӣ</option>
                    </select>
                </div>

                {{-- Формат --}}
                <div class="col-6 col-md-3">
                    <label class="form-label">Формат <span class="text-danger">*</span></label>
                    <select name="format" class="form-select" required>
                        <option value="online_test" selected>Тести онлайн</option>
                        <option value="written">Хаттӣ</option>
                        <option value="oral">Даҳонӣ</option>
                    </select>
                </div>

                {{-- Гурӯҳҳо (чекбокс) --}}
                <div class="col-12">
                    <label class="form-label">Гурӯҳҳо <span class="text-danger">*</span></label>
                    <p class="text-muted small mb-2">Гурӯҳҳоеро ки дар ин имтиҳон иштирок мекунанд интихоб кунед (якбора чанд гурӯҳ мумкин)</p>
                    <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto;">
                        <div class="row g-2">
                            @foreach($groups as $group)
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="group_ids[]"
                                               value="{{ $group->id }}" id="group_{{ $group->id }}"
                                               {{ in_array($group->id, old('group_ids', [])) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="group_{{ $group->id }}">
                                            {{ $group->name }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @error('group_ids') <small class="text-danger">{{ $message }}</small> @enderror
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Танзимот --}}
                <div class="col-6 col-md-3">
                    <label class="form-label">Давомнокӣ (дақиқа)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', 25) }}" min="5" max="180">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Ҳадди гузариш (%)</label>
                    <input type="number" name="passing_score" class="form-control" value="{{ old('passing_score', 50) }}" min="0" max="100">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Ҳадди кӯшишҳо</label>
                    <input type="number" name="max_attempts" class="form-control" value="{{ old('max_attempts', 1) }}" min="1" max="5">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label">Шумораи саволҳо</label>
                    <input type="number" name="total_questions_count" class="form-control" value="{{ old('total_questions_count', 25) }}" min="5" max="100">
                </div>

                {{-- Checkboxes --}}
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input type="hidden" name="shuffle_questions" value="0">
                            <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" id="shQ" {{ old('shuffle_questions', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="shQ">Омехтакунии саволҳо</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="shuffle_answers" value="0">
                            <input class="form-check-input" type="checkbox" name="shuffle_answers" value="1" id="shA" {{ old('shuffle_answers', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="shA">Омехтакунии ҷавобҳо</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="show_results_immediately" value="0">
                            <input class="form-check-input" type="checkbox" name="show_results_immediately" value="1" id="shR" {{ old('show_results_immediately') ? 'checked' : '' }}>
                            <label class="form-check-label" for="shR">Натиҷа фавран</label>
                        </div>
                        <div class="form-check">
                            <input type="hidden" name="allow_back_navigation" value="0">
                            <input class="form-check-input" type="checkbox" name="allow_back_navigation" value="1" id="bN" {{ old('allow_back_navigation', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="bN">Бозгашт ба саволи пеш</label>
                        </div>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Вақт --}}
                <div class="col-12 col-md-6">
                    <label class="form-label">Санаи оғоз</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Санаи анҷом</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at') }}">
                </div>
            </div>

            <hr>
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <a href="/admin/exams" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Сохтан
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
