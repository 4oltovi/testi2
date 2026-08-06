@extends('layouts.app')

@section('title', 'Сохтани гурӯҳ')
@section('page-header', 'Сохтани гурӯҳи нав')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.groups.store') }}">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Номи гурӯҳ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required placeholder="мис: ТИ-1-24">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="code" class="form-label">Рамз <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code') }}" required placeholder="мис: TI-1-24">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="specialty_id" class="form-label">Ихтисос <span class="text-danger">*</span></label>
                            <select class="form-select @error('specialty_id') is-invalid @enderror" id="specialty_id" name="specialty_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($specialties as $specialty)
                                    <option value="{{ $specialty->id }}" {{ old('specialty_id') == $specialty->id ? 'selected' : '' }}>
                                        {{ $specialty->name }} ({{ $specialty->department?->faculty?->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('specialty_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="course_id" class="form-label">Курс <span class="text-danger">*</span></label>
                            <select class="form-select @error('course_id') is-invalid @enderror" id="course_id" name="course_id" required>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                        {{ $course->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('course_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3">
                            <label for="academic_year_id" class="form-label">Соли таҳсилӣ <span class="text-danger">*</span></label>
                            <select class="form-select @error('academic_year_id') is-invalid @enderror" id="academic_year_id" name="academic_year_id" required>
                                @foreach($academicYears as $year)
                                    <option value="{{ $year->id }}" {{ old('academic_year_id') == $year->id ? 'selected' : '' }}
                                            {{ $year->is_current ? 'selected' : '' }}>
                                        {{ $year->name }} {{ $year->is_current ? '(ҷорӣ)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('academic_year_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="curator_id" class="form-label">Куратор</label>
                            <select class="form-select" id="curator_id" name="curator_id">
                                <option value="">— Интихоб кунед —</option>
                                @foreach($curators as $curator)
                                    <option value="{{ $curator->id }}" {{ old('curator_id') == $curator->id ? 'selected' : '' }}>
                                        {{ $curator->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="max_students" class="form-label">Ҳадди аксари донишҷӯён</label>
                            <input type="number" class="form-control" id="max_students" name="max_students"
                                   value="{{ old('max_students', 25) }}" min="5" max="50">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Фаъол</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.structure.groups.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Сабт кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
