@extends('layouts.app')

@section('title', $employee->full_name)
@section('page-header', $employee->full_name)
@section('page-description', 'Профили корманд')

@section('page-actions')
    @can('manageUpdate', $employee)
        <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i> Таҳрир
        </a>
    @endcan
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Бозгашт
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    @if($employee->avatar && file_exists(public_path('storage/' . $employee->avatar)))
                        <img src="{{ asset('storage/' . $employee->avatar) }}"
                             alt="{{ $employee->full_name }}"
                             class="rounded-circle mb-3"
                             style="width: 110px; height: 110px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 110px; height: 110px; font-size: 2.2rem;">
                            {{ mb_substr($employee->first_name, 0, 1) }}
                        </div>
                    @endif

                    <h5 class="mb-1">{{ $employee->full_name }}</h5>
                    <code class="text-muted">{{ $employee->login }}</code>

                    <div class="mt-3">
                        @if($employee->status === 'active')
                            <span class="badge bg-success">Фаъол</span>
                        @elseif($employee->status === 'blocked')
                            <span class="badge bg-danger">Блокшуда</span>
                        @else
                            <span class="badge bg-warning text-dark">Гайрифаъол</span>
                        @endif
                    </div>

                    @foreach($employee->roles as $role)
                        <span class="badge bg-secondary mt-1">{{ $role->display_name }}</span>
                    @endforeach
                </div>
            </div>

            @can('manageToggleStatus', $employee)
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-body">
                        <form method="POST"
                              action="{{ route('hr.employees.status', $employee) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="activate" value="{{ $employee->status === 'active' ? 0 : 1 }}">
                            <button class="btn btn-outline-{{ $employee->status === 'active' ? 'warning' : 'success' }} w-100">
                                @if($employee->status === 'active')
                                    <i class="bi bi-pause-circle me-1"></i> Ғайрифаъол кардан
                                @else
                                    <i class="bi bi-play-circle me-1"></i> Фаъол кардан
                                @endif
                            </button>
                        </form>
                        <div class="form-text mt-2">
                            Нест кардан дар ин панел нест — ғайрифаъол кардан бехатартар аст.
                        </div>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Маълумот</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th style="width: 220px;">Логин</th>
                            <td><code>{{ $employee->login }}</code></td>
                        </tr>
                        <tr>
                            <th>Номи падар</th>
                            <td>{{ $employee->middle_name ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $employee->email ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Телефон</th>
                            <td>{{ $employee->phone ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Ҳолат</th>
                            <td>
                                @if($employee->status === 'active')
                                    <span class="badge bg-success">Фаъол</span>
                                @elseif($employee->status === 'blocked')
                                    <span class="badge bg-danger">Блокшуда</span>
                                @else
                                    <span class="badge bg-warning text-dark">Гайрифаъол</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Нақш</th>
                            <td>
                                @forelse($employee->roles as $role)
                                    <span class="badge bg-secondary">{{ $role->display_name }}</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                        </tr>
                        <tr>
                            <th>Азои таърих</th>
                            <td>{{ $employee->created_at?->format('d.m.Y H:i') ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th>Охирин ворид</th>
                            <td>{{ $employee->last_login_at?->format('d.m.Y H:i') ?: '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($employee->teacher)
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Профили омӯзгор</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm mb-0">
                            <tr>
                                <th style="width: 220px;">Шеъраи корӣ</th>
                                <td>{{ $employee->teacher->position ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th>Кафедра</th>
                                <td>{{ $employee->teacher->department?->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Рои муаллими умумӣ</th>
                                <td>{{ $employee->teacher->academic_degree ?: '—' }}</td>
                            </tr>
                            <tr>
                                <th>Унвон</th>
                                <td>{{ $employee->teacher->academic_title ?: '—' }}</td>
                            </tr>
                        </table>
                        <div class="form-text">
                            Таҳрири ин профил дар панели «Омӯзгорон» иҷро мешавад.
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection