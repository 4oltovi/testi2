@extends('layouts.app')

@section('title', 'Сохтани фан')
@section('page-header', 'Сохтани фани нав')
@section('page-description', 'Илова кардани фан ба система')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-book me-2"></i> Маълумоти асосӣ</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.structure.subjects.store') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Номи фан <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                class="form-control @error('name') is-invalid @enderror" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Рамз <span class="text-danger">*</span></label>
                            <input type="text" name="code" value="{{ old('code') }}"
                                class="form-control @error('code') is-invalid @enderror" required>
                            @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ихтисор</label>
                            <input type="text" name="short_name" value="{{ old('short_name') }}" class="form-control">
                        </div>
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
                        <div class="col-12">
                            <label class="form-label">Тавсиф</label>
                            <textarea name="description" rows="3" class="form-control">{{ old('description') }}</textarea>
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
                        <a href="{{ route('admin.structure.subjects.index') }}" class="btn btn-outline-secondary">← Бозгашт</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Сабт кардан</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection