@extends('layouts.app')

@section('title', 'Таҳрири нақшаи таълимӣ')
@section('page-header', 'Таҳрири нақшаи таълимӣ')
@section('page-description', 'Тағйир додани маълумоти нақшаи таълимӣ')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.curriculum.update', $curriculum) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-link-45deg me-2"></i> Пайвастшавӣ</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="specialty_id" class="form-label">Ихтисос <span class="text-danger">*</span></label>
                            <select class="form-select @error('specialty_id') is-invalid @enderror" id="specialty_id" name="specialty_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($specialties as $specialty)
                                    <option value="{{ $specialty->id }}" {{ old('specialty_id', $curriculum->specialty_id) == $specialty->id ? 'selected' : '' }}>
                                        {{ $specialty->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('specialty_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="subject_id" class="form-label">Фан <span class="text-danger">*</span></label>
                            <select class="form-select @error('subject_id') is-invalid @enderror" id="subject_id" name="subject_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ old('subject_id', $curriculum->subject_id) == $subject->id ? 'selected' : '' }}>
                                        {{ $subject->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('subject_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="course_id" class="form-label">Курс <span class="text-danger">*</span></label>
                            <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id', $curriculum->course_id) == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label for="semester_id" class="form-label">Семестр <span class="text-danger">*</span></label>
                            <select class="form-select @error('semester_id') is-invalid @enderror" id="semester_id" name="semester_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($semesters as $sem)
                                    <option value="{{ $sem->id }}" {{ old('semester_id', $curriculum->semester_id) == $sem->id ? 'selected' : '' }}>
                                        {{ $sem->name }} — {{ $sem->academicYear?->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('semester_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4"><i class="bi bi-clock me-2"></i> Соатҳо ва кредитҳо</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="credits" class="form-label">Кредитҳо <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('credits') is-invalid @enderror"
                                   id="credits" name="credits" value="{{ old('credits', $curriculum->credits) }}" required min="1" max="30">
                            @error('credits') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="total_hours" class="form-label">Соатҳои умумӣ <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('total_hours') is-invalid @enderror"
                                   id="total_hours" name="total_hours" value="{{ old('total_hours', $curriculum->total_hours) }}" required min="10" max="500">
                            @error('total_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="lecture_hours" class="form-label">Лексия</label>
                            <input type="number" class="form-control @error('lecture_hours') is-invalid @enderror"
                                   id="lecture_hours" name="lecture_hours" value="{{ old('lecture_hours', $curriculum->lecture_hours) }}" min="0">
                            @error('lecture_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="practice_hours" class="form-label">Амалӣ</label>
                            <input type="number" class="form-control @error('practice_hours') is-invalid @enderror"
                                   id="practice_hours" name="practice_hours" value="{{ old('practice_hours', $curriculum->practice_hours) }}" min="0">
                            @error('practice_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="lab_hours" class="form-label">Лабораторӣ</label>
                            <input type="number" class="form-control @error('lab_hours') is-invalid @enderror"
                                   id="lab_hours" name="lab_hours" value="{{ old('lab_hours', $curriculum->lab_hours) }}" min="0">
                            @error('lab_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="independent_hours" class="form-label">Мустақилона</label>
                            <input type="number" class="form-control @error('independent_hours') is-invalid @enderror"
                                   id="independent_hours" name="independent_hours" value="{{ old('independent_hours', $curriculum->independent_hours) }}" min="0">
                            @error('independent_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4"><i class="bi bi-gear me-2"></i> Танзимот</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="exam_type" class="form-label">Навъи санҷиш <span class="text-danger">*</span></label>
                            <select class="form-select @error('exam_type') is-invalid @enderror" id="exam_type" name="exam_type" required>
                                <option value="exam" {{ old('exam_type', $curriculum->exam_type) == 'exam' ? 'selected' : '' }}>Имтиҳон</option>
                                <option value="credit" {{ old('exam_type', $curriculum->exam_type) == 'credit' ? 'selected' : '' }}>Синҷиш</option>
                                <option value="diff_credit" {{ old('exam_type', $curriculum->exam_type) == 'diff_credit' ? 'selected' : '' }}>Синҷиши бо баҳо</option>
                            </select>
                            @error('exam_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="control_type" class="form-label">Навъи назорат <span class="text-danger">*</span></label>
                            <select class="form-select @error('control_type') is-invalid @enderror" id="control_type" name="control_type" required>
                                <option value="rating_exam" {{ old('control_type', $curriculum->control_type) == 'rating_exam' ? 'selected' : '' }}>Рейтинг+Имтиҳон</option>
                                <option value="rating_only" {{ old('control_type', $curriculum->control_type) == 'rating_only' ? 'selected' : '' }}>Танҳо рейтинг</option>
                                <option value="project" {{ old('control_type', $curriculum->control_type) == 'project' ? 'selected' : '' }}>Лоиҳа</option>
                                <option value="coursework" {{ old('control_type', $curriculum->control_type) == 'coursework' ? 'selected' : '' }}>Курсавӣ</option>
                            </select>
                            @error('control_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="is_elective" name="is_elective" value="1" {{ old('is_elective', $curriculum->is_elective) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_elective">Фани интихобӣ</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $curriculum->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.curriculum.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Нав кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
