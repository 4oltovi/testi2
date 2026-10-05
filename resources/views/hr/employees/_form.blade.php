{{-- Формаи ягонаи корманд — ҳам барои сохтан, ҳам барои таҳрир. --}}
@php
    $editing = $employee->exists;
    $canChangeRoles ??= true;
@endphp

<form method="POST"
      action="{{ $editing ? route('hr.employees.update', $employee) : route('hr.employees.store') }}">
    @csrf
    @if($editing)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Маълумоти асосӣ</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Ном <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" required maxlength="100"
                           class="form-control @error('first_name') is-invalid @enderror"
                           value="{{ old('first_name', $employee->first_name) }}">
                    @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Насаб <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" required maxlength="100"
                           class="form-control @error('last_name') is-invalid @enderror"
                           value="{{ old('last_name', $employee->last_name) }}">
                    @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Номи падар</label>
                    <input type="text" name="middle_name" maxlength="100"
                           class="form-control @error('middle_name') is-invalid @enderror"
                           value="{{ old('middle_name', $employee->middle_name) }}">
                    @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Логин <span class="text-danger">*</span></label>
                    <input type="text" name="login" required maxlength="50"
                           class="form-control @error('login') is-invalid @enderror"
                           value="{{ old('login', $employee->login) }}">
                    @error('login') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Танҳо ҳарфҳои инсонии, рақам ва _ . -</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" maxlength="100"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email', $employee->email) }}">
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Телефон</label>
                    <input type="text" name="phone" maxlength="20"
                           class="form-control @error('phone') is-invalid @enderror"
                           value="{{ old('phone', $employee->phone) }}">
                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Ҳолат</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        @foreach(['active' => 'Фаъол', 'inactive' => 'Гайрифаъол', 'blocked' => 'Блокшуда'] as $value => $label)
                            <option value="{{ $value }}" {{ old('status', $employee->status) === $value ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        {{ $editing ? 'Гуфуши нави (бозод кардани парол)' : 'Гуфуши парол' }}
                        @if(! $editing)<span class="text-danger">*</span>@endif
                    </label>
                    <input type="password" name="password" @if(! $editing) required @endif minlength="8"
                           autocomplete="new-password"
                           class="form-control @error('password') is-invalid @enderror">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    @if($editing)
                        <div class="form-text">Холи пӯшида — парол намегузард.</div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label class="form-label">Тасдиқи парол</label>
                    <input type="password" name="password_confirmation" @if(! $editing) required @endif
                           minlength="8" autocomplete="new-password" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Нақш</h5>
        </div>
        <div class="card-body">
            @if($canChangeRoles)
                <p class="text-muted small">
                    Дар ин рӯйхат танҳо нақшҳои корманд ҳастанд. Админ, суперадмин ва
                    донишҷӯ аз рӯи Кадр дода намешаванд.
                </p>
                <div class="row g-2">
                    @foreach($roles as $role)
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input @error('roles') is-invalid @enderror"
                                       type="checkbox" name="roles[]" value="{{ $role->id }}"
                                       id="role_{{ $role->id }}"
                                       {{ in_array($role->id, old('roles', $selectedRoleIds ?? [])) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $role->id }}">
                                    {{ $role->display_name }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('roles') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
            @else
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Нақши худро иваз кардан мумкин нест.
                    Нақшҳои кунунӣ:
                    <strong>
                        {{ $employee->roles->pluck('display_name')->join(', ') ?: '—' }}
                    </strong>
                </div>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            {{ $editing ? 'Сабти таҳрир' : 'Сохтани корманд' }}
        </button>
        <a href="{{ $editing ? route('hr.employees.show', $employee) : route('hr.employees.index') }}"
           class="btn btn-outline-secondary">Баргашт</a>
    </div>
</form>