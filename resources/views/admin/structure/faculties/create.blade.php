@extends('layouts.app')

@section('title', 'Сохтани факултет')
@section('page-header', 'Сохтани факултети нав')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.faculties.store') }}">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label for="name" class="form-label">Номи факултет <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="code" class="form-label">Рамз <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code') }}" required placeholder="мис: FT">
                            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="dean_id" class="form-label">Декан</label>
                            <select class="form-select" id="dean_id" name="dean_id">
                                <option value="">— Интихоб кунед —</option>
                                @foreach($deans as $dean)
                                    <option value="{{ $dean->id }}" {{ old('dean_id') == $dean->id ? 'selected' : '' }}>
                                        {{ $dean->full_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                        </div>
                    </div>

                    {{-- Муассиса автоматикӣ (пинҳон) --}}
                    <input type="hidden" name="institution_id" value="{{ $institutions->first()?->id }}">

                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Фаъол</label>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.faculties.index') }}" class="btn btn-outline-secondary">
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
