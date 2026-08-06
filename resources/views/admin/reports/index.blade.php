@extends('layouts.app')

@section('title', 'Ҳисоботҳо')
@section('page-header', 'Ҳисоботҳо')
@section('page-description', 'Системаи ҳисоботдиҳии таълимӣ')

@section('content')
    {{-- Омории кӯтоҳ --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-primary mb-0">{{ $stats['total_students'] }}</h3>
                <small class="text-muted">Донишҷӯён</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-success mb-0">{{ $stats['total_teachers'] }}</h3>
                <small class="text-muted">Омӯзгорон</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-info mb-0">{{ $stats['total_groups'] }}</h3>
                <small class="text-muted">Гурӯҳҳо</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-warning mb-0">{{ $stats['total_faculties'] }}</h3>
                <small class="text-muted">Факултетҳо</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-danger mb-0">{{ $stats['total_debtors'] }}</h3>
                <small class="text-muted">Қарздорон</small>
            </div>
        </div>
        <div class="col-sm-4 col-lg-2">
            <div class="card border-0 shadow-sm text-center p-3">
                <h3 class="text-dark mb-0">{{ $stats['active_debts'] }}</h3>
                <small class="text-muted">Қарзҳои кушод</small>
            </div>
        </div>
    </div>

    {{-- Навъҳои ҳисобот --}}
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-people fs-1 text-primary d-block mb-3"></i>
                    <h5>Рӯйхати донишҷӯён</h5>
                    <p class="text-muted small">Рӯйхати пурраи донишҷӯён бо филтр</p>
                    <a href="{{ route('admin.reports.students') }}" class="btn btn-outline-primary">Дидан</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-exclamation-triangle fs-1 text-danger d-block mb-3"></i>
                    <h5>Рӯйхати қарздорон</h5>
                    <p class="text-muted small">Донишҷӯёни дорои қарздории академӣ</p>
                    <a href="{{ route('admin.reports.debtors') }}" class="btn btn-outline-danger">Дидан</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-check2-square fs-1 text-success d-block mb-3"></i>
                    <h5>Давомот</h5>
                    <p class="text-muted small">Ҳисоботи давомот аз рӯйи гурӯҳ</p>
                    <a href="{{ route('admin.reports.attendance') }}" class="btn btn-outline-success">Дидан</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-bar-chart fs-1 text-info d-block mb-3"></i>
                    <h5>GPA</h5>
                    <p class="text-muted small">Рейтинги GPA донишҷӯён</p>
                    <a href="{{ route('admin.reports.gpa') }}" class="btn btn-outline-info">Дидан</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-journal-check fs-1 text-warning d-block mb-3"></i>
                    <h5>Натиҷаҳои имтиҳон</h5>
                    <p class="text-muted small">Баҳоҳои ниҳоии семестрӣ</p>
                    <a href="{{ route('admin.reports.exam-results') }}" class="btn btn-outline-warning">Дидан</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-file-earmark-arrow-down fs-1 text-secondary d-block mb-3"></i>
                    <h5>Содирот</h5>
                    <p class="text-muted small">PDF ва Excel</p>
                    <a href="{{ route('admin.reports.export', 'pdf') }}" class="btn btn-outline-secondary me-1">PDF</a>
                    <a href="{{ route('admin.reports.export', 'excel') }}" class="btn btn-outline-secondary">Excel</a>
                </div>
            </div>
        </div>
    </div>
@endsection
