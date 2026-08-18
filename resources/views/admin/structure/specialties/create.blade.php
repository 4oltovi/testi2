@extends('layouts.app')

@section('title', 'Сохтани ихтисос')
@section('page-header', 'Сохтани ихтисоси нав')
@section('page-description', 'Илова кардани ихтисоси нав ба система')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Маълумоти ихтисос</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.structure.specialties.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Кафедра <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                                <option value="">— Интихоб кунед —</option>
                                @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected' : '' }}>
                                    {{ $d->name }} ({{ $d->faculty?->name ?? '-' }})
                                </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Номи ихтисос <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Рамзи ихтисос <span class="text-danger">*</span></label>
                            <input type="text" name="code" value="{{ old('code') }}" placeholder="мис: 1-79 01 01"
                                class="form-control @error('code') is-invalid @enderror" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Сатҳи таҳсил <span class="text-danger">*</span></label>
                            <select name="education_level" class="form-select" required>
                                <option value="bachelor" {{ old('education_level') == 'bachelor' ? 'selected' : '' }}>Бакалавр</option>
                                <option value="master" {{ old('education_level') == 'master' ? 'selected' : '' }}>Магистр</option>
                                <option value="specialist" {{ old('education_level') == 'specialist' ? 'selected' : '' }}>Мутахассис</option>
                                <option value="secondary" {{ old('education_level') == 'secondary' ? 'selected' : '' }}>Миёна</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Муддати таҳсил (сол) <span class="text-danger">*</span></label>
                            <input type="number" name="study_years" value="{{ old('study_years', 2) }}" min="1" max="7"
                                class="form-control @error('study_years') is-invalid @enderror" required>
                            @error('study_years')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Шакли таҳсил <span class="text-danger">*</span></label>
                            <select name="study_form" class="form-select" required>
                                <option value="full_time" {{ old('study_form') == 'full_time' ? 'selected' : '' }}>Рӯзона</option>
                                <option value="part_time" {{ old('study_form') == 'part_time' ? 'selected' : '' }}>Ғоибона</option>
                                <option value="evening" {{ old('study_form') == 'evening' ? 'selected' : '' }}>Шомина</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                                    {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Фаъол</label>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.structure.specialties.index') }}" class="btn btn-outline-secondary">← Бозгашт</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Сабт кардан</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection