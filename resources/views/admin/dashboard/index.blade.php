@extends('layouts.app')

@section('title', 'Панели асосӣ')
@section('page-header', 'Хуш омадед, ' . (auth()->user()->first_name ?? 'Корбар') . '!')
@section('page-description', 'Панели идоракунии системаи «Донишёр»')

@section('page-actions')
    <a href="/admin/students/create" class="btn btn-primary btn-sm me-1"><i class="bi bi-plus me-1"></i>Донишҷӯи нав</a>
    <a href="/admin/structure/academic-years/create" class="btn btn-warning btn-sm"><i class="bi bi-calendar-plus me-1"></i>Соли нав</a>
@endsection

@section('content')
    {{-- Статистика --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h3>
                        <small class="text-muted">Донишҷӯён</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                        <i class="bi bi-person-workspace fs-4 text-success"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['total_teachers'] ?? 0 }}</h3>
                        <small class="text-muted">Омӯзгорон</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                        <i class="bi bi-collection fs-4 text-info"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['total_groups'] ?? 0 }}</h3>
                        <small class="text-muted">Гурӯҳҳо</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                        <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $stats['students_with_debts'] ?? 0 }}</h3>
                        <small class="text-muted">Қарздорон</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Маълумот --}}
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Маълумоти система</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Система:</td><td>Донишёр v1.0</td></tr>
                        <tr><td class="text-muted">Корбарон:</td><td>{{ $stats['total_users'] ?? 0 }}</td></tr>
                        <tr><td class="text-muted">Низоми баҳо:</td><td>Кредитии Тоҷикистон (A-F)</td></tr>
                        <tr><td class="text-muted">Формула:</td><td>R1×0.15 + R2×0.15 + КМ×0.30 + Имт.×0.40</td></tr>
                        <tr><td class="text-muted">Факултетҳо:</td><td>{{ $stats['total_faculties'] ?? 0 }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-lightning me-2"></i>Амалҳои зуд</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="/admin/students/create" class="btn btn-primary btn-sm"><i class="bi bi-plus me-1"></i>Донишҷӯи нав</a>
                        <a href="/admin/teachers/create" class="btn btn-success btn-sm"><i class="bi bi-plus me-1"></i>Омӯзгори нав</a>
                        <a href="/admin/structure/groups/create" class="btn btn-info btn-sm text-white"><i class="bi bi-plus me-1"></i>Гурӯҳи нав</a>
                        <a href="/admin/structure/academic-years/create" class="btn btn-warning btn-sm"><i class="bi bi-calendar-plus me-1"></i>Соли нав</a>
                        <a href="/admin/reports/debtors" class="btn btn-danger btn-sm"><i class="bi bi-exclamation-triangle me-1"></i>Қарздорон</a>
                        <a href="/admin/reports/gpa" class="btn btn-outline-primary btn-sm"><i class="bi bi-trophy me-1"></i>GPA рейтинг</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
