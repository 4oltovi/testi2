@extends('layouts.app')

@section('title', 'Журнали аудит')
@section('page-header', 'Журнали аудит')
@section('page-description', 'Сабти тамоми амалҳои система')

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="user_id" class="form-label">Корбар</label>
                <select name="user_id" id="user_id" class="form-select">
                    <option value="">Ҳамаи корбарон</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="action" class="form-label">Амал</label>
                <select name="action" id="action" class="form-select">
                    <option value="">Ҳама</option>
                    <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Сохтан</option>
                    <option value="update" {{ request('action') == 'update' ? 'selected' : '' }}>Тағйир</option>
                    <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Нест кардан</option>
                    <option value="login" {{ request('action') == 'login' ? 'selected' : '' }}>Вуруд</option>
                    <option value="logout" {{ request('action') == 'logout' ? 'selected' : '' }}>Хуруҷ</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">Аз сана</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">То сана</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Филтр
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        @if($logs->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Сана</th>
                            <th>Корбар</th>
                            <th>Амал</th>
                            <th>Тавсиф</th>
                            <th>IP</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $index => $log)
                            <tr>
                                <td>{{ $logs->firstItem() + $index }}</td>
                                <td><small>{{ $log->created_at->format('d.m.Y H:i:s') }}</small></td>
                                <td>{{ $log->user->short_name ?? $log->user->name ?? '—' }}</td>
                                <td>
                                    @php
                                        $actionColors = [
                                            'create' => 'success',
                                            'update' => 'info',
                                            'delete' => 'danger',
                                            'login' => 'primary',
                                            'logout' => 'secondary',
                                        ];
                                        $actionColor = $actionColors[$log->action] ?? 'dark';
                                    @endphp
                                    <span class="badge bg-{{ $actionColor }}">{{ $log->action }}</span>
                                </td>
                                <td>{{ $log->description }}</td>
                                <td><small class="text-muted">{{ $log->ip_address }}</small></td>
                                <td><small class="text-muted" title="{{ $log->url }}">{{ Str::limit($log->url, 30) }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center mt-3">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-journal-text fs-1"></i>
                <p class="mt-2">Сабтҳо вуҷуд надоранд</p>
            </div>
        @endif
    </div>
</div>

<div class="mt-3">
    <a href="{{ url('/admin') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Бозгашт
    </a>
</div>
@endsection
