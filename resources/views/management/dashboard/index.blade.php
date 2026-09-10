@extends('layouts.app')

@section('title', $role?->label() ?? 'Дашбоард')
@section('page-header', ($role?->label() ?? 'Дашбоард') . ' — ' . ($user?->full_name ?? ''))
@section('page-description', 'Хулосаи умумӣ')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-primary mb-0">{{ $stats['total_students'] }}</h3>
                <small class="text-muted">Донишҷӯён</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-success mb-0">{{ $stats['total_teachers'] }}</h3>
                <small class="text-muted">Омӯзгорон</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">{{ $stats['total_groups'] }}</h3>
                <small class="text-muted">Гурӯҳҳо</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-warning mb-0">{{ $stats['active_debts'] }}</h3>
                <small class="text-muted">Қарздорӣ</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-danger mb-0">{{ $stats['students_with_debts'] }}</h3>
                <small class="text-muted">Донишҷӯёни қарздор</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="text-secondary mb-0">{{ $stats['total_faculties'] }}</h3>
                <small class="text-muted">Факултетҳо</small>
            </div>
        </div>
    </div>
</div>

{{-- Навигатсионӣ карточаҳо барои Декан/Вице-Декан --}}
@php
$showNav = $user->hasRole(App\Enums\UserRole::DEAN) || $user->hasRole(App\Enums\UserRole::VICE_DEAN);
@endphp
<div class="row g-3 mb-4">
    <div class="col-12">
        <h6 class="text-muted text-uppercase fw-bold"><i class="bi bi-grid-3x3-gap me-2"></i> Сарфаҳои идоракуни</h6>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <a href="{{ route('management.students.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-person-badge fs-1 text-primary mb-2"></i>
                    <h6 class="mb-0">Донишҷӯён</h6>
                    <small class="text-muted">{{ $stats['total_students'] }}</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.teachers.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-person-workspace fs-1 text-success fs-1 mb-2"></i>
                    <h6 class="mb-0">Омӯзгорон</h6>
                    <small class="text-muted">{{ $stats['total_teachers'] }}</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.specialties.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-bookmark-star fs-1 text-info mb-2"></i>
                    <h6 class="mb-0">Ихтисосҳо</h6>
                    <small class="text-muted">Факултети {{ \Illuminate\Support\Str::of($stats['faculty_name'] ?? '')->limit(15) }}</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.groups.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-people-fill fs-1 text-warning mb-2"></i>
                    <h6 class="mb-0">Гурӯҳҳо</h6>
                    <small class="text-muted">{{ $stats['total_groups'] }}</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.subjects.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-book fs-1 text-danger mb-2"></i>
                    <h6 class="mb-0">Фанҳо</h6>
                    <small class="text-muted">Баҳодиҳӣ</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.journal.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-journal-text fs-1 text-secondary mb-2"></i>
                    <h6 class="mb-0">Журнал</h6>
                    <small class="text-muted">Дарсҳо</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.debts.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle fs-1 text-dark mb-2"></i>
                    <h6 class="mb-0">Қарздорӣ</h6>
                    <small class="text-muted">{{ $stats['active_debts'] }}</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3 col-6">
        <a href="{{ route('management.reports.index') }}" class="text-decoration-none">
            <div class="card border-0 shadow-sm h-100 hover-shadow">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-bar-graph fs-1 text-primary mb-2"></i>
                    <h6 class="mb-0">Гузоришҳо</h6>
                    <small class="text-muted">Аналитика</small>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i> Донишҷӯёни охид</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ном</th>
                                <th>Гурӯҳ</th>
                                <th>Санаи қайд</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_students'] as $student)
                            <tr>
                                <td>{{ $student->user?->full_name ?? '—' }}</td>
                                <td>{{ $student->group?->name ?? '—' }}</td>
                                <td><small>{{ $student->created_at?->format('d.m.Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳои кушод</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Донишҷӯ</th>
                                <th>Сабаб</th>
                                <th>Сана</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stats['recent_debts'] as $debt)
                            <tr>
                                <td>{{ $debt->student?->user?->full_name ?? '—' }}</td>
                                <td>{{ $debt->reason ?? '—' }}</td>
                                <td><small>{{ $debt->debt_date?->format('d.m.Y') }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">Қарздорӣ нест</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
