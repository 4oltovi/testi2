@extends('layouts.app')

@section('title', 'Таҳрири омӯзгор')
@section('page-header', 'Таҳрири омӯзгор')
@section('page-description', $teacher->user?->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('admin.teachers.update', $teacher) }}">
            @csrf @method('PUT')

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i> Маълумоти шахсӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Насаб <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name"
                                   value="{{ old('last_name', $teacher->user?->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name"
                                   value="{{ old('first_name', $teacher->user?->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Номи падар</label>
                            <input type="text" class="form-control" name="middle_name"
                                   value="{{ old('middle_name', $teacher->user?->middle_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $teacher->user?->email) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $teacher->user?->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Таваллуд</label>
                            <input type="date" class="form-control" name="birth_date"
                                   value="{{ old('birth_date', $teacher->birth_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҷинс</label>
                            <select class="form-select" name="gender">
                                <option value="">—</option>
                                <option value="male" {{ old('gender', $teacher->gender) == 'male' ? 'selected' : '' }}>Мард</option>
                                <option value="female" {{ old('gender', $teacher->gender) == 'female' ? 'selected' : '' }}>Зан</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i> Маълумоти касбӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Рақами кормандӣ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id"
                                   value="{{ old('employee_id', $teacher->employee_id) }}" required>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Кафедра <span class="text-danger">*</span></label>
                            <select class="form-select" name="department_id" required>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id', $teacher->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Вазифа <span class="text-danger">*</span></label>
                            <select class="form-select" name="position" required>
                                <option value="Ассистент" {{ old('position', $teacher->position) == 'Ассистент' ? 'selected' : '' }}>Ассистент</option>
                                <option value="Муаллими калон" {{ old('position', $teacher->position) == 'Муаллими калон' ? 'selected' : '' }}>Муаллими калон</option>
                                <option value="Доцент" {{ old('position', $teacher->position) == 'Доцент' ? 'selected' : '' }}>Доцент</option>
                                <option value="Профессор" {{ old('position', $teacher->position) == 'Профессор' ? 'selected' : '' }}>Профессор</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Дараҷаи илмӣ</label>
                            <input type="text" class="form-control" name="academic_degree"
                                   value="{{ old('academic_degree', $teacher->academic_degree) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Унвони илмӣ</label>
                            <input type="text" class="form-control" name="academic_title"
                                   value="{{ old('academic_title', $teacher->academic_title) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Навъ</label>
                            <select class="form-select" name="employment_type" required>
                                <option value="full_time" {{ old('employment_type', $teacher->employment_type) == 'full_time' ? 'selected' : '' }}>Доимӣ</option>
                                <option value="part_time" {{ old('employment_type', $teacher->employment_type) == 'part_time' ? 'selected' : '' }}>Нимшатота</option>
                                <option value="hourly" {{ old('employment_type', $teacher->employment_type) == 'hourly' ? 'selected' : '' }}>Соатбайъ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ставка</label>
                            <input type="number" class="form-control" name="rate"
                                   value="{{ old('rate', $teacher->rate) }}" step="0.25" min="0.25" max="2.0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Санаи қабул</label>
                            <input type="date" class="form-control" name="hire_date"
                                   value="{{ old('hire_date', $teacher->hire_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷоми шартнома</label>
                            <input type="date" class="form-control" name="contract_end_date"
                                   value="{{ old('contract_end_date', $teacher->contract_end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҳадди аксар соат/ҳафта</label>
                            <input type="number" class="form-control" name="max_hours_per_week"
                                   value="{{ old('max_hours_per_week', $teacher->max_hours_per_week) }}" min="4" max="72">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҳолат <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="active" {{ old('status', $teacher->status) == 'active' ? 'selected' : '' }}>Фаъол</option>
                                <option value="on_leave" {{ old('status', $teacher->status) == 'on_leave' ? 'selected' : '' }}>Рухсатӣ</option>
                                <option value="dismissed" {{ old('status', $teacher->status) == 'dismissed' ? 'selected' : '' }}>Рафта</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.teachers.show', $teacher) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Навсозӣ кардан
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
