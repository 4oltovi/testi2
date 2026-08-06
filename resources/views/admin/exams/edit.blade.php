@extends('layouts.app')

@section('title', 'Таҳрири имтиҳон')
@section('page-header', 'Таҳрири имтиҳон')
@section('page-description', 'Тағйир додани вақт, навъ, ва шумораи саволҳо')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.exams.update', $exam) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Номи имтиҳон <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $exam->title) }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Навъи имтиҳон <span class="text-danger">*</span></label>
                    <select name="exam_type" class="form-select" required>
                        <option value="main" {{ $exam->exam_type == 'main' ? 'selected' : '' }}>Имтиҳони асосӣ</option>
                        <option value="rating1" {{ $exam->exam_type == 'rating1' ? 'selected' : '' }}>Рейтинги 1</option>
                        <option value="rating2" {{ $exam->exam_type == 'rating2' ? 'selected' : '' }}>Рейтинги 2</option>
                        <option value="midterm" {{ $exam->exam_type == 'midterm' ? 'selected' : '' }}>Миёнасеместрӣ</option>
                        <option value="quiz" {{ $exam->exam_type == 'quiz' ? 'selected' : '' }}>Тести кӯтоҳ</option>
                        <option value="retake" {{ $exam->exam_type == 'retake' ? 'selected' : '' }}>Такрорсупорӣ</option>
                        <option value="retake_commission" {{ $exam->exam_type == 'retake_commission' ? 'selected' : '' }}>Комиссионӣ</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Формат <span class="text-danger">*</span></label>
                    <select name="format" class="form-select" required>
                        <option value="online_test" {{ $exam->format == 'online_test' ? 'selected' : '' }}>Тести онлайн</option>
                        <option value="written" {{ $exam->format == 'written' ? 'selected' : '' }}>Хаттӣ</option>
                        <option value="oral" {{ $exam->format == 'oral' ? 'selected' : '' }}>Даҳонӣ</option>
                        <option value="mixed" {{ $exam->format == 'mixed' ? 'selected' : '' }}>Омехта</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Тавсиф</label>
                    <textarea name="description" class="form-control" rows="3">{{ old('description', $exam->description) }}</textarea>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Вақт (дақиқа)</label>
                    <input type="number" name="duration_minutes" class="form-control" value="{{ old('duration_minutes', $exam->duration_minutes) }}" min="5" max="180" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Шумораи саволҳо</label>
                    <input type="number" name="total_questions_count" class="form-control" value="{{ old('total_questions_count', $exam->total_questions_count) }}" min="1" max="100" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Ҳадди гузариш (%)</label>
                    <input type="number" name="passing_score" class="form-control" value="{{ old('passing_score', $exam->passing_score) }}" min="0" max="100" step="0.01" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Шумораи кӯшишҳо</label>
                    <input type="number" name="max_attempts" class="form-control" value="{{ old('max_attempts', $exam->max_attempts) }}" min="1" max="5" required>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="shuffle_questions" value="1" id="shuffle_q" {{ $exam->shuffle_questions ? 'checked' : '' }}>
                        <label class="form-check-label" for="shuffle_q">Тасодуфӣ кардани саволҳо</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="shuffle_answers" value="1" id="shuffle_a" {{ $exam->shuffle_answers ? 'checked' : '' }}>
                        <label class="form-check-label" for="shuffle_a">Тасодуфӣ кардани ҷавобҳо</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="show_results_immediately" value="1" id="show_res" {{ $exam->show_results_immediately ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_res">Натиҷаро фавран нишон деҳ</label>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="allow_back_navigation" value="1" id="back_nav" {{ $exam->allow_back_navigation ? 'checked' : '' }}>
                        <label class="form-check-label" for="back_nav">Бозгашт ба саволи пеш</label>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Санаи оғоз</label>
                    <input type="datetime-local" name="starts_at" class="form-control" value="{{ old('starts_at', optional($exam->starts_at)->format('Y-m-d\TH:i')) }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Санаи анҷом</label>
                    <input type="datetime-local" name="ends_at" class="form-control" value="{{ old('ends_at', optional($exam->ends_at)->format('Y-m-d\TH:i')) }}">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.exams.show', $exam) }}" class="btn btn-outline-secondary">Бозгашт</a>
                <button type="submit" class="btn btn-primary">Сабти тағйирот</button>
            </div>
        </form>
    </div>
</div>
@endsection
