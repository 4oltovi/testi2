@extends('layouts.app')

@section('title', 'Таҳрири фан')
@section('page-header', 'Таҳрири фан')
@section('page-description', 'Тағйир додани маълумоти фан')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.subjects.update', $subject) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-book me-2"></i> Маълумоти асосӣ</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label for="name" class="form-label">Номи фан <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $subject->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label for="code" class="form-label">Рамз <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code', $subject->code) }}" required placeholder="ANAT101">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="short_name" class="form-label">Ихтисор</label>
                            <input type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name', $subject->short_name) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Кафедра <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $subject->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="credits" class="form-label">Кредитҳо <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('credits') is-invalid @enderror"
                                   id="credits" name="credits" value="{{ old('credits', $subject->credits) }}" required min="1" max="30">
                            @error('credits') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="exam_type" class="form-label">Навъи санҷиш <span class="text-danger">*</span></label>
                            <select class="form-select @error('exam_type') is-invalid @enderror" id="exam_type" name="exam_type" required>
                                <option value="exam" {{ old('exam_type', $subject->exam_type) == 'exam' ? 'selected' : '' }}>Имтиҳон</option>
                                <option value="credit" {{ old('exam_type', $subject->exam_type) == 'credit' ? 'selected' : '' }}>Синҷиш</option>
                                <option value="diff_credit" {{ old('exam_type', $subject->exam_type) == 'diff_credit' ? 'selected' : '' }}>Синҷиши бо баҳо</option>
                            </select>
                            @error('exam_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label for="total_hours" class="form-label">Соатҳои умумӣ <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('total_hours') is-invalid @enderror"
                                   id="total_hours" name="total_hours" value="{{ old('total_hours', $subject->total_hours) }}" required min="10">
                            @error('total_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Тавсиф</label>
                        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $subject->description) }}</textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $subject->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.subjects.index') }}" class="btn btn-outline-secondary">
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
