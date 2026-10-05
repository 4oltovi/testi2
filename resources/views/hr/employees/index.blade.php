@extends('layouts.app')

@section('title', 'Корманд')
@section('page-header', 'Корманд')
@section('page-description', 'Корбарони корӣ (омӯзгорон ва дигар корманд)')

@section('page-actions')
    <a href="{{ route('hr.employees.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Корманд нав
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('hr.employees.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control"
                           placeholder="Ҷумҳа: ном, логин, телефон..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="role" class="form-select">
                        <option value="">Ҳамаи нақшҳо</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                                {{ $role->display_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Ҳамаи ҳолатҳо</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Фаъол</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Гайрифаъол</option>
                        <option value="blocked" {{ request('status') === 'blocked' ? 'selected' : '' }}>Блокшуда</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary me-1">
                        <i class="bi bi-search me-1"></i> Ҷумҳа
                    </button>
                    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Рӯйхат</h5>
            <span class="text-muted small">{{ $employees->total() }} корманд</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ном</th>
                        <th>Логин</th>
                        <th>Нақш</th>
                        <th>Телефон</th>
                        <th>Ҳолат</th>
                        <th class="text-end">Амал</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td>
                                <a href="{{ route('hr.employees.show', $employee) }}"
                                   class="text-decoration-none fw-semibold">
                                    {{ $employee->full_name }}
                                </a>
                            </td>
                            <td><code>{{ $employee->login }}</code></td>
                            <td>
                                @foreach($employee->roles as $role)
                                    <span class="badge bg-secondary">{{ $role->display_name }}</span>
                                @endforeach
                            </td>
                            <td>{{ $employee->phone ?: '—' }}</td>
                            <td>
                                @if($employee->status === 'active')
                                    <span class="badge bg-success">Фаъол</span>
                                @elseif($employee->status === 'blocked')
                                    <span class="badge bg-danger">Блокшуда</span>
                                @else
                                    <span class="badge bg-warning text-dark">Гайрифаъол</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('manageView', $employee)
                                    <a href="{{ route('hr.employees.show', $employee) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Корманд ёфт нашуд.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="card-footer bg-white">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
@endsection