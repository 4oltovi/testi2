@extends('layouts.app')

@section('title', 'Иваз кардани парол')
@section('page-header', 'Иваз кардани парол')
@section('page-description', 'Барои амният пароли худро иваз кунед')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock fs-1 text-warning"></i>
                    <h5 class="mt-2">Пароли худро иваз кунед</h5>
                    <p class="text-muted small">Парол пешфарз (12345678) аст. Барои амният иваз кунед.</p>
                </div>

                <form method="POST" action="{{ route('password.force-change.update') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Пароли нав <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                               required autofocus placeholder="Ҳадди ақал 4 рамз">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Тасдиқи парол <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control"
                               required placeholder="Паролро такрор кунед">
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i> Иваз кардан
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
