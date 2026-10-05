@extends('layouts.app')

@section('title', 'Профили ман')
@section('page-header', 'Профили ман')
@section('page-description', 'Маълумоти шахсии донишҷӯ')

@section('content')
<style>
    /* Partial-и умумӣ дар 128px аксро медиҳад; дар экрани хурд хамин
       нисбати пеш аз ҳол (104px) нигоҳ дошта мешавад. Чун inline-style
       аз CSS сурфет хок мекунад, !important лозим аст. */
    .profile-photo img,
    .profile-photo .rounded-circle {
        border-radius: 50%;
        object-fit: cover;
    }

    @media (max-width: 576px) {
        .profile-photo img,
        .profile-photo .rounded-circle {
            width: 104px !important;
            height: 104px !important;
        }
    }
</style>

<div class="row g-4">
    {{-- Акси профил --}}
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <div class="profile-photo mb-3">
                    @include('partials.avatar', ['user' => $user, 'size' => 128])
                </div>

                <form method="POST" action="{{ route('student.profile.photo.update') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-2">
                        <input type="file" name="photo" id="student-photo"
                               class="form-control form-control-sm @error('photo') is-invalid @enderror"
                               accept="image/jpeg,image/png,image/webp" required>
                        @error('photo')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-camera me-1"></i> Акси нав интихоб кардан
                    </button>
                </form>

                @if($user->avatar)
                <form method="POST" action="{{ route('student.profile.photo.destroy') }}" class="mt-2"
                      onsubmit="return confirm('Акси профил нест карда шавад?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                        <i class="bi bi-trash me-1"></i> Нест кардани акс
                    </button>
                </form>
                @endif

                <small class="text-muted d-block mt-3">
                    Форматҳо: JPG, PNG, WEBP — то 2 МБ.<br>
                    Акс ба 512×512 коркард мешавад.
                </small>
            </div>
        </div>
    </div>

    {{-- Маълумоти шахсӣ --}}
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('student.profile.update') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Ном *</label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $user->first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Насаб *</label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $user->last_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Номи падари</label>
                            <input type="text" name="middle_name" class="form-control" value="{{ old('middle_name', $user->middle_name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Телефон</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}">
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-1"></i> Сабт кардан
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
