@extends('layouts.app')

@section('title', 'Сохтани корбари нав')
@section('page-header', 'Сохтани корбари нав')
@section('page-description', 'Сабти корбари нав дар система')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <h6 class="text-muted border-bottom pb-2 mb-3">
                        <i class="bi bi-person me-2"></i> Маълумоти шахсӣ
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Насаб <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="middle_name" class="form-label">Номи падар</label>
                            <input type="text" class="form-control @error('middle_name') is-invalid @enderror"
                                   id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                            @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email') }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone') }}" placeholder="+992 XXX XX XX XX">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-shield-lock me-2"></i> Маълумоти воридшавӣ
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="login" class="form-label">Логин <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('login') is-invalid @enderror"
                                   id="login" name="login" value="{{ old('login') }}" required
                                   placeholder="Танҳо ҳарфҳо, рақамҳо ва тире">
                            @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Ҳолат <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Ғайрифаъол</option>
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Парол <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" required minlength="8">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Ҳадди ақал 8 рамз</div>
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Тасдиқи парол <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirmation"
                                   name="password_confirmation" required>
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-key me-2"></i> Нақшҳо
                    </h6>

                    <div class="row g-2 mb-4">
                        @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input @error('roles') is-invalid @enderror"
                                           type="checkbox" name="roles[]" value="{{ $role->id }}"
                                           id="role_{{ $role->id }}"
                                           {{ in_array($role->id, old('roles', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->display_name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                        @error('roles') <div class="text-danger small">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
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
