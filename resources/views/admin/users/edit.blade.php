@extends('layouts.app')

@section('title', 'Таҳрири корбар')
@section('page-header', 'Таҳрири корбар')
@section('page-description', $user->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="text-muted border-bottom pb-2 mb-3">
                        <i class="bi bi-person me-2"></i> Маълумоти шахсӣ
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="last_name" class="form-label">Насаб <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required>
                            @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="first_name" class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required>
                            @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label for="middle_name" class="form-label">Номи падар</label>
                            <input type="text" class="form-control @error('middle_name') is-invalid @enderror"
                                   id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror"
                                   id="email" name="email" value="{{ old('email', $user->email) }}">
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">Телефон</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                   id="phone" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-shield-lock me-2"></i> Маълумоти воридшавӣ
                    </h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="login" class="form-label">Логин <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('login') is-invalid @enderror"
                                   id="login" name="login" value="{{ old('login', $user->login) }}" required>
                            @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label">Ҳолат <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                <option value="active" {{ old('status', $user->status) == 'active' ? 'selected' : '' }}>Фаъол</option>
                                <option value="inactive" {{ old('status', $user->status) == 'inactive' ? 'selected' : '' }}>Ғайрифаъол</option>
                                <option value="blocked" {{ old('status', $user->status) == 'blocked' ? 'selected' : '' }}>Блокшуда</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="password" class="form-label">Пароли нав <small class="text-muted">(холӣ = тағйир намедиҳад)</small></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" minlength="8">
                            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="password_confirmation" class="form-label">Тасдиқи парол</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3 mt-4">
                        <i class="bi bi-key me-2"></i> Нақшҳо
                    </h6>

                    <div class="row g-2 mb-4">
                        @foreach($roles as $role)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="roles[]"
                                           value="{{ $role->id }}" id="role_{{ $role->id }}"
                                           {{ in_array($role->id, old('roles', $userRoles)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="role_{{ $role->id }}">
                                        {{ $role->display_name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <div>
                            @if($user->id !== auth()->id())
                                <button type="button" class="btn btn-outline-danger me-2"
                                        onclick="if(confirm('Оё мутмаин ҳастед?')) document.getElementById('delete-form').submit()">
                                    <i class="bi bi-trash me-1"></i> Нест кардан
                                </button>
                            @endif
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Навсозӣ кардан
                            </button>
                        </div>
                    </div>
                </form>

                @if($user->id !== auth()->id())
                    <form id="delete-form" action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-none">
                        @csrf @method('DELETE')
                    </form>
                @endif
            </div>
        </div>

        {{-- Маълумоти иловагӣ --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i> Маълумоти иловагӣ</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <small class="text-muted">Сабт шуд:</small>
                        <p>{{ $user->created_at?->format('d.m.Y H:i') ?? '—' }}</p>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Воридшавии охирин:</small>
                        <p>{{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Ҳеҷ вақт' }}
                            @if($user->last_login_ip)
                                <br><small class="text-muted">IP: {{ $user->last_login_ip }}</small>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
