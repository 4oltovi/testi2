@extends('layouts.app')

@section('title', 'Дастрасӣ мањдуд аст — Донишёр')

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height: 60vh;">
    <div class="card shadow-sm border-0" style="max-width: 480px; width: 100%;">
        <div class="card-body text-center p-5">
            <div class="display-1 mb-3">🔒</div>
            <h4 class="fw-bold mb-3">Шумо ҳамчун {{ $user->first_name ?? $user->login }} ({{ $user->roles->first()?->name ?? 'корбар' }}) ворид шудаед</h4>
            <p class="text-muted mb-4">{!! $message ?? 'Ва ба ин саҳифа <strong>дастрасӣ надоред</strong>.' !!}</p>

            <div class="d-grid gap-2">
                <a href="{{ $dashboardUrl ?? url('/') }}" class="btn btn-primary btn-lg">
                    <i class="bi bi-speedometer2 me-1"></i> Ба панели ман
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="bi bi-box-arrow-right me-1"></i> Баромад ва бо ҳисоби дигар ворид шудан
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
