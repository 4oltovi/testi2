@extends('layouts.app')

@section('title', 'Таҳрири донишҷӯ')
@section('page-header', 'Таҳрири донишҷӯ')
@section('page-description', $student->user?->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('admin.students.update', $student) }}">
            @csrf @method('PUT')

            {{-- Маълумоти шахсӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i> Маълумоти шахсӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Насаб <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name"
                                   value="{{ old('last_name', $student->user?->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name"
                                   value="{{ old('first_name', $student->user?->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Номи падар</label>
                            <input type="text" class="form-control" name="middle_name"
                                   value="{{ old('middle_name', $student->user?->middle_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="{{ old('email', $student->user?->email) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone', $student->user?->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Таваллуд</label>
                            <input type="date" class="form-control" name="birth_date" value="{{ old('birth_date', $student->birth_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҷинс</label>
                            <select class="form-select" name="gender">
                                <option value="">—</option>
                                <option value="male" {{ old('gender', $student->gender) == 'male' ? 'selected' : '' }}>Мард</option>
                                <option value="female" {{ old('gender', $student->gender) == 'female' ? 'selected' : '' }}>Зан</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Таълимӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Маълумоти таълимӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Рақами донишҷӯӣ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('student_id_number') is-invalid @enderror" name="student_id_number"
                                   value="{{ old('student_id_number', $student->student_id_number) }}" required>
                            @error('student_id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Зачётка</label>
                            <input type="text" class="form-control" name="record_book_number"
                                   value="{{ old('record_book_number', $student->record_book_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Шакли таъмин</label>
                            <select class="form-select" name="education_form" required>
                                <option value="budget" {{ old('education_form', $student->education_form) == 'budget' ? 'selected' : '' }}>Буҷетӣ</option>
                                <option value="contract" {{ old('education_form', $student->education_form) == 'contract' ? 'selected' : '' }}>Шартномавӣ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Шакли таҳсил</label>
                            <select class="form-select" name="study_form" required>
                                <option value="full_time" {{ old('study_form', $student->study_form) == 'full_time' ? 'selected' : '' }}>Рӯзона</option>
                                <option value="part_time" {{ old('study_form', $student->study_form) == 'part_time' ? 'selected' : '' }}>Ғоибона</option>
                                <option value="evening" {{ old('study_form', $student->study_form) == 'evening' ? 'selected' : '' }}>Шабона</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ихтисос <span class="text-danger">*</span></label>
                            <select class="form-select" name="specialty_id" required>
                                @foreach($specialties as $spec)
                                    <option value="{{ $spec->id }}" {{ old('specialty_id', $student->specialty_id) == $spec->id ? 'selected' : '' }}>{{ $spec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Гурӯҳ <span class="text-danger">*</span></label>
                            <select class="form-select" name="group_id" required>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id', $student->group_id) == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Курс</label>
                            <select class="form-select" name="course_id" required>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id', $student->course_id) == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Санаи қабул</label>
                            <input type="date" class="form-control" name="enrollment_date"
                                   value="{{ old('enrollment_date', $student->enrollment_date?->format('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Паспорт ва суроға --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-card-text me-2"></i> Ҳуҷҷатҳо ва суроға</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Серия</label>
                            <input type="text" class="form-control" name="passport_series" value="{{ old('passport_series', $student->passport_series) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Рақами паспорт</label>
                            <input type="text" class="form-control" name="passport_number" value="{{ old('passport_number', $student->passport_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Миллат</label>
                            <input type="text" class="form-control" name="nationality" value="{{ old('nationality', $student->nationality) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Волидон</label>
                            <input type="text" class="form-control" name="parent_name" value="{{ old('parent_name', $student->parent_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефони волидон</label>
                            <input type="text" class="form-control" name="parent_phone" value="{{ old('parent_phone', $student->parent_phone) }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Суроғаи доимӣ</label>
                            <input type="text" class="form-control" name="address_permanent" value="{{ old('address_permanent', $student->address_permanent) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Суроғаи ҳозира</label>
                            <input type="text" class="form-control" name="address_current" value="{{ old('address_current', $student->address_current) }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.students.show', $student) }}" class="btn btn-outline-secondary">
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
