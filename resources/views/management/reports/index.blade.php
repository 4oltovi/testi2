@extends('layouts.app')

@section('title', 'Ҳисоботҳо')
@section('page-header', 'Ҳисоботҳо')
@section('page-description', 'Ҳисоботи умумии факултет')

@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-text fs-1 text-primary mb-2"></i>
                <h5 class="mb-2">Ҳисоботи донишҷӯён</h5>
                <p class="text-muted small">Список донишҷӯёни актив</p>
                <a href="{{ route('management.reports.students') }}" class="btn btn-primary btn-sm">Кушад</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-exclamation-triangle fs-1 text-warning mb-2"></i>
                <h5 class="mb-2">Қарздориҳо</h5>
                <p class="text-muted small">Донишҷӯёни қарздор</p>
                <a href="{{ route('management.reports.debtors') }}" class="btn btn-primary btn-sm">Кушад</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-bar-chart-line fs-1 text-success mb-2"></i>
                <h5 class="mb-2">GPA</h5>
                <p class="text-muted small">Баландтарин GPA</p>
                <a href="{{ route('management.reports.gpa') }}" class="btn btn-primary btn-sm">Кушад</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check fs-1 text-info mb-2"></i>
                <h5 class="mb-2">Давомот</h5>
                <p class="text-muted small">Омораи ҳозирӣ</p>
                <a href="{{ route('management.reports.attendance') }}" class="btn btn-primary btn-sm">Кушад</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center">
                <i class="bi bi-pencil-square fs-1 text-danger mb-2"></i>
                <h5 class="mb-2">Имтиҳонҳо</h5>
                <p class="text-muted small">Натиҷаҳои имтиҳон</p>
                <a href="{{ route('management.reports.exam-results') }}" class="btn btn-primary btn-sm">Кушад</a>
            </div>
        </div>
    </div>
</div>

@if($currentSemester)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-speedometer2 me-2"></i> Хулосаи ҷорӣ</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th class="text-muted">Донишҷӯён:</th>
                <td>{{ $stats['total_students'] }}</td>
                <th class="text-muted ms-4">Омӯзгорон:</th>
                <td>{{ $stats['total_teachers'] }}</td>
            </tr>
            <tr>
                <th class="text-muted">Гурӯҳҳо:</th>
                <td>{{ $stats['total_groups'] }}</td>
                <th class="text-muted ms-4">Қорздор:</th>
                <td>{{ $stats['active_debts'] }}</td>
            </tr>
            <tr>
                <th class="text-muted">Донишҷӯ бо қарз:</th>
                <td>{{ $stats['total_debtors'] }}</td>
                <th class="text-muted ms-4">&nbsp;</th>
                <td>&nbsp;</td>
            </tr>
        </table>
    </div>
</div>
@endif
@endsection