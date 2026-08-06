@extends('layouts.app')

@section('title', 'Таҳрири аудитория')
@section('page-header', 'Таҳрири аудитория')
@section('page-description', 'Тағйир додани маълумоти аудитория')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.classrooms.update', $classroom) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Рақами аудитория <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $classroom->name) }}" required maxlength="20" placeholder="мис: 301">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="building" class="form-label">Бино</label>
                            <input type="text" class="form-control @error('building') is-invalid @enderror"
                                   id="building" name="building" value="{{ old('building', $classroom->building) }}" maxlength="50" placeholder="мис: Бинои 1">
                            @error('building') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="floor" class="form-label">Ошёна</label>
                            <input type="number" class="form-control @error('floor') is-invalid @enderror"
                                   id="floor" name="floor" value="{{ old('floor', $classroom->floor) }}" min="0" max="10">
                            @error('floor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="capacity" class="form-label">Ҷойгоҳ (нафар) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('capacity') is-invalid @enderror"
                                   id="capacity" name="capacity" value="{{ old('capacity', $classroom->capacity) }}" required min="5" max="500">
                            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="type" class="form-label">Навъ <span class="text-danger">*</span></label>
                            <select class="form-select @error('type') is-invalid @enderror" id="type" name="type" required>
                                <option value="lecture" {{ old('type', $classroom->type) == 'lecture' ? 'selected' : '' }}>Лексионӣ</option>
                                <option value="practice" {{ old('type', $classroom->type) == 'practice' ? 'selected' : '' }}>Амалӣ</option>
                                <option value="lab" {{ old('type', $classroom->type) == 'lab' ? 'selected' : '' }}>Лабораторӣ</option>
                                <option value="computer" {{ old('type', $classroom->type) == 'computer' ? 'selected' : '' }}>Компютерӣ</option>
                                <option value="gym" {{ old('type', $classroom->type) == 'gym' ? 'selected' : '' }}>Варзишгоҳ</option>
                                <option value="other" {{ old('type', $classroom->type) == 'other' ? 'selected' : '' }}>Дигар</option>
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="has_projector" name="has_projector" value="1" {{ old('has_projector', $classroom->has_projector) ? 'checked' : '' }}>
                            <label class="form-check-label" for="has_projector">Проектор дорад</label>
                        </div>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="has_computers" name="has_computers" value="1" {{ old('has_computers', $classroom->has_computers) ? 'checked' : '' }}>
                            <label class="form-check-label" for="has_computers">Компютер дорад</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $classroom->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.classrooms.index') }}" class="btn btn-outline-secondary">
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
