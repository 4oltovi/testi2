@extends('layouts.app')

@section('title', 'Сохтани ихтисоси нав')
@section('page-header', 'Сохтани ихтисоси нав')
@section('page-description', 'Илова кардани ихтисоси нав ба система')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.specialties.store') }}">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label for="department_id" class="form-label">Кафедра <span class="text-danger">*</span></label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Номи ихтисос <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Рамзи ихтисос <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code') }}" required maxlength="20" placeholder="мис: 1-79 01 01">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="education_level" class="form-label">Сатҳи таҳсил <span class="text-danger">*</span></label>
                            <select class="form-select @error('education_level') is-invalid @enderror" id="education_level" name="education_level" required>
                                <option value="bachelor" {{ old('education_level') == 'bachelor' ? 'selected' : '' }}>Бакалавр</option>
                                <option value="master" {{ old('education_level') == 'master' ? 'selected' : '' }}>Магистр</option>
                                <option value="specialist" {{ old('education_level') == 'specialist' ? 'selected' : '' }}>Мутахассис</option>
                            </select>
                            @error('education_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="study_years" class="form-label">Муддати таҳсил (сол) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('study_years') is-invalid @enderror"
                                   id="study_years" name="study_years" value="{{ old('study_years') }}" required min="1" max="7">
                            @error('study_years') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="total_credits" class="form-label">Маҷмӯи кредитҳо <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('total_credits') is-invalid @enderror"
                                   id="total_credits" name="total_credits" value="{{ old('total_credits') }}" required min="60" max="500">
                            @error('total_credits') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="study_form" class="form-label">Шакли таҳсил <span class="text-danger">*</span></label>
                            <select class="form-select @error('study_form') is-invalid @enderror" id="study_form" name="study_form" required>
                                <option value="full_time" {{ old('study_form') == 'full_time' ? 'selected' : '' }}>Рӯзона</option>
                                <option value="part_time" {{ old('study_form') == 'part_time' ? 'selected' : '' }}>Ғоибона</option>
                                <option value="evening" {{ old('study_form') == 'evening' ? 'selected' : '' }}>Шабона</option>
                            </select>
                            @error('study_form') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.specialties.index') }}" class="btn btn-outline-secondary">
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
