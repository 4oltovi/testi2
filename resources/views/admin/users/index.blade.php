@extends('layouts.app')

@section('title', 'Корбарон')
@section('page-header', 'Идоракунии корбарон')
@section('page-description', 'Рӯйхати ҳамаи корбарони система')

@section('page-actions')
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Корбари нав
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Ҷустуҷӯ: ном, логин, email..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">Ҳама нақшҳо</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Ҳама</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Ғайрифаъол</option>
                        <option value="blocked" {{ request('status') == 'blocked' ? 'selected' : '' }}>Блокшуда</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary me-1">
                        <i class="bi bi-search me-1"></i> Ҷустуҷӯ
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвали корбарон --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Корбар</th>
                            <th>Логин</th>
                            <th>Нақш</th>
                            <th>Ҳолат</th>
                            <th>Воридшавии охирин</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2"
                                             style="width: 36px; height: 36px; font-size: 0.8rem; font-weight: 600;">
                                            {{ mb_substr($user->first_name, 0, 1) }}{{ mb_substr($user->last_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <strong>{{ $user->full_name }}</strong>
                                            @if($user->email)
                                                <br><small class="text-muted">{{ $user->email }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $user->login }}</code></td>
                                <td>
                                    @foreach($user->roles as $role)
                                        <span class="badge bg-primary">{{ $role->display_name }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    @php
                                        $statusBadge = match($user->status) {
                                            'active' => 'bg-success',
                                            'inactive' => 'bg-secondary',
                                            'blocked' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        $statusLabel = match($user->status) {
                                            'active' => 'Фаъол',
                                            'inactive' => 'Ғайрифаъол',
                                            'blocked' => 'Блокшуда',
                                            default => $user->status
                                        };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    @if($user->last_login_at)
                                        <small>{{ $user->last_login_at->format('d.m.Y H:i') }}</small>
                                    @else
                                        <small class="text-muted">Ҳеҷ вақт</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary" title="Таҳрир">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @if($user->status === 'active' && $user->id !== auth()->id())
                                        <form action="{{ route('admin.users.block', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Блок"
                                                    data-confirm="Оё мехоҳед ин корбарро блок кунед?">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        </form>
                                    @elseif($user->status === 'blocked')
                                        <form action="{{ route('admin.users.activate', $user) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Фаъол кардан">
                                                <i class="bi bi-unlock"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    Корбаре ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
