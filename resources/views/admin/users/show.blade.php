@extends('layouts.app')

@section('title', 'Корбар — ' . $user->full_name)
@section('page-header', 'Корбар')
@section('page-description', $user->full_name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="text-muted border-bottom pb-2 mb-3">
                    <i class="bi bi-person me-2"></i> Маълумоти шахсӣ
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label class="form-label text-muted">Насаб</label>
                        <div class="fw-semibold">{{ $user->last_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Ном</label>
                        <div class="fw-semibold">{{ $user->first_name }}</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted">Номи падар</label>
                        <div class="fw-semibold">{{ $user->middle_name ?? '—' }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Email</label>
                        <div class="fw-semibold">{{ $user->email ?? '—' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Телефон</label>
                        <div class="fw-semibold">{{ $user->phone ?? '—' }}</div>
                    </div>
                </div>

                <h6 class="text-muted border-bottom pb-2 mb-3 mt-4">
                    <i class="bi bi-shield-lock me-2"></i> Маълумоти воридшавӣ
                </h6>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Логин</label>
                        <div class="fw-semibold">{{ $user->login }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Ролҳо</label>
                        <div>
                            @foreach($user->roles as $role)
                                <span class="badge bg-primary me-1">{{ $role->name }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт ба рӯйхат
                    </a>
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary ms-2">
                        <i class="bi bi-pencil me-1"></i> Таҳрир
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
