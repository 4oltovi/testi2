@extends('layouts.app')

@section('title', 'Таҳрири факултет')
@section('page-header', 'Таҳрири факултет')
@section('page-description', $faculty->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.faculties.update', $faculty) }}">
                    @csrf @method('PUT')
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Номи факултет <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $faculty->name) }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Рамз <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code', $faculty->code) }}" required>
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="short_name" class="form-label">Ихтисор</label>
                            <input type="text" class="form-control" id="short_name" name="short_name" value="{{ old('short_name', $faculty->short_name) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="institution_id" class="form-label">Муассиса <span class="text-danger">*</span></label>
                            <select class="form-select" id="institution_id" name="institution_id" required>
                                @foreach($institutions as $inst)
                                    <option value="{{ $inst->id }}" {{ old('institution_id', $faculty->institution_id) == $inst->id ? 'selected' : '' }}>
                                        {{ $inst->short_name ?? $inst->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="dean_id" class="form-label">Декан</label>
                            <select class="form-select" id="dean_id" name="dean_id">
                                <option value="">— Интихоб кунед —</option>
                                @foreach($deans as $dean)
                                    <option value="{{ $dean->id }}" {{ old('dean_id', $faculty->dean_id) == $dean->id ? 'selected' : '' }}>
                                        {{ $dean->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone', $faculty->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $faculty->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="sort_order" class="form-label">Тартиб</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $faculty->sort_order) }}" min="0">
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $faculty->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.faculties.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Навсозӣ кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
