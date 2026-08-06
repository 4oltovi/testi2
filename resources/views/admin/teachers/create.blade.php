@extends('layouts.app')

@section('title', 'Сабти омӯзгори нав')
@section('page-header', 'Сабти омӯзгори нав')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('admin.teachers.store') }}">
            @csrf

            {{-- Воридшавӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Маълумоти воридшавӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Насаб <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Номи падар</label>
                            <input type="text" class="form-control" name="middle_name" value="{{ old('middle_name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Логин <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('login') is-invalid @enderror" name="login" value="{{ old('login') }}" required>
                            @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Парол <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Тасдиқ <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Касбӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-briefcase me-2"></i> Маълумоти касбӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Рақами кормандӣ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('employee_id') is-invalid @enderror" name="employee_id" value="{{ old('employee_id') }}" required>
                            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Кафедра <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" name="department_id" required>
                                <option value="">— Интихоб —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Вазифа <span class="text-danger">*</span></label>
                            <select class="form-select @error('position') is-invalid @enderror" name="position" required>
                                <option value="">— Интихоб —</option>
                                <option value="Ассистент" {{ old('position') == 'Ассистент' ? 'selected' : '' }}>Ассистент</option>
                                <option value="Муаллими калон" {{ old('position') == 'Муаллими калон' ? 'selected' : '' }}>Муаллими калон</option>
                                <option value="Доцент" {{ old('position') == 'Доцент' ? 'selected' : '' }}>Доцент</option>
                                <option value="Профессор" {{ old('position') == 'Профессор' ? 'selected' : '' }}>Профессор</option>
                            </select>
                            @error('position') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Дараҷаи илмӣ</label>
                            <input type="text" class="form-control" name="academic_degree" value="{{ old('academic_degree') }}" placeholder="к.и.т., д.и.т.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Унвони илмӣ</label>
                            <input type="text" class="form-control" name="academic_title" value="{{ old('academic_title') }}" placeholder="Доцент, Профессор">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Навъи кор <span class="text-danger">*</span></label>
                            <select class="form-select" name="employment_type" required>
                                <option value="full_time" {{ old('employment_type') == 'full_time' ? 'selected' : '' }}>Доимӣ</option>
                                <option value="part_time" {{ old('employment_type') == 'part_time' ? 'selected' : '' }}>Нимшатота</option>
                                <option value="hourly" {{ old('employment_type') == 'hourly' ? 'selected' : '' }}>Соатбайъ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ставка <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="rate" value="{{ old('rate', '1.00') }}" step="0.25" min="0.25" max="2.0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Санаи қабул <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('hire_date') is-invalid @enderror" name="hire_date" value="{{ old('hire_date') }}" required>
                            @error('hire_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷоми шартнома</label>
                            <input type="date" class="form-control" name="contract_end_date" value="{{ old('contract_end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Таваллуд</label>
                            <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Ҷинс</label>
                            <select class="form-select" name="gender">
                                <option value="">—</option>
                                <option value="male" {{ old('gender') == 'male' ? 'selected' : '' }}>Мард</option>
                                <option value="female" {{ old('gender') == 'female' ? 'selected' : '' }}>Зан</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефон (кор)</label>
                            <input type="text" class="form-control" name="phone_work" value="{{ old('phone_work') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефон (шахсӣ)</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардан
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
