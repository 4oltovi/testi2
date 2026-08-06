@extends('layouts.app')

@section('title', 'Сабти донишҷӯи нав')
@section('page-header', 'Сабти донишҷӯи нав')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form method="POST" action="{{ route('admin.students.store') }}">
            @csrf

            {{-- Маълумоти воридшавӣ --}}
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
                        {{-- Логин = ID донишҷӯӣ (автоматикӣ) --}}
                        <div class="col-md-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info py-2 mb-0 small">
                                <i class="bi bi-info-circle me-1"></i>
                                Парол автоматикӣ: <strong>12345678</strong>
                                <br>Донишҷӯ дар логини аввал паролро иваз мекунад.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Маълумоти таълимӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Маълумоти таълимӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">ID донишҷӯӣ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('student_id_number') is-invalid @enderror" name="student_id_number" value="{{ old('student_id_number') }}" required placeholder="Логин ҳам ин мешавад">
                            @error('student_id_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Донишҷӯ бо ин ID ворид мешавад</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Рақами зачётка</label>
                            <input type="text" class="form-control" name="record_book_number" value="{{ old('record_book_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Шакли таъмин <span class="text-danger">*</span></label>
                            <select class="form-select @error('education_form') is-invalid @enderror" name="education_form" required>
                                <option value="budget" {{ old('education_form') == 'budget' ? 'selected' : '' }}>Буҷетӣ</option>
                                <option value="contract" {{ old('education_form') == 'contract' ? 'selected' : '' }}>Шартномавӣ</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Шакли таҳсил <span class="text-danger">*</span></label>
                            <select class="form-select" name="study_form" required>
                                <option value="full_time" {{ old('study_form') == 'full_time' ? 'selected' : '' }}>Рӯзона</option>
                                <option value="part_time" {{ old('study_form') == 'part_time' ? 'selected' : '' }}>Ғоибона</option>
                                <option value="evening" {{ old('study_form') == 'evening' ? 'selected' : '' }}>Шабона</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ихтисос <span class="text-danger">*</span></label>
                            <select class="form-select @error('specialty_id') is-invalid @enderror" name="specialty_id" required>
                                <option value="">— Интихоб —</option>
                                @foreach($specialties as $spec)
                                    <option value="{{ $spec->id }}" {{ old('specialty_id') == $spec->id ? 'selected' : '' }}>{{ $spec->name }}</option>
                                @endforeach
                            </select>
                            @error('specialty_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Гурӯҳ <span class="text-danger">*</span></label>
                            <select class="form-select @error('group_id') is-invalid @enderror" name="group_id" required>
                                <option value="">— Интихоб —</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ old('group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }} ({{ $group->specialty?->name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Курс <span class="text-danger">*</span></label>
                            <select class="form-select @error('course_id') is-invalid @enderror" name="course_id" required>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Санаи қабул <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('enrollment_date') is-invalid @enderror" name="enrollment_date" value="{{ old('enrollment_date') }}" required>
                            @error('enrollment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Маълумоти шахсӣ --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-person me-2"></i> Маълумоти шахсӣ</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Санаи таваллуд</label>
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
                            <label class="form-label">Миллат</label>
                            <input type="text" class="form-control" name="nationality" value="{{ old('nationality', 'Тоҷик') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Паспорт (серия)</label>
                            <input type="text" class="form-control" name="passport_series" value="{{ old('passport_series') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Паспорт (рақам)</label>
                            <input type="text" class="form-control" name="passport_number" value="{{ old('passport_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефон</label>
                            <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Волидон (ном)</label>
                            <input type="text" class="form-control" name="parent_name" value="{{ old('parent_name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Телефони волидон</label>
                            <input type="text" class="form-control" name="parent_phone" value="{{ old('parent_phone') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Суроғаи доимӣ</label>
                            <input type="text" class="form-control" name="address_permanent" value="{{ old('address_permanent') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Суроғаи ҳозира</label>
                            <input type="text" class="form-control" name="address_current" value="{{ old('address_current') }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Тугмаҳо --}}
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">
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
