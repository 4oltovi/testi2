@extends('layouts.app')

@section('title', 'Панели асосӣ')
@section('page-header', 'Панели асосӣ')
@section('page-description', 'Кадр ходим — идоракунии корманд ва тасдиқи шахсияти донишҷӯён')

@section('content')
    {{-- Ҳисобҳо --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Корманд (ҳамагӣ)</div>
                    <div class="fs-3 fw-bold">{{ $stats['total'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Фаъол</div>
                    <div class="fs-3 fw-bold text-success">{{ $stats['active'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Гайрифаъол</div>
                    <div class="fs-3 fw-bold text-warning">{{ $stats['inactive'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Омӯзгорон</div>
                    <div class="fs-3 fw-bold text-primary">{{ $stats['teachers'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Ҷумҳаи корманд --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Ҷумҳаи корманд</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('hr.dashboard') }}" class="row g-2">
                        <div class="col-md-8">
                            <input type="text" name="q" class="form-control"
                                   placeholder="Ном, логин ё телефон..."
                                   value="{{ $searchTerm }}">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i> Ҷумҳа
                            </button>
                        </div>
                    </form>

                    @if($searchTerm !== '')
                        <hr class="my-3">

                        @if($quickResults->isEmpty())
                            <div class="text-muted">Ой намуда нашуд.</div>
                        @else
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @foreach($quickResults as $employee)
                                        <tr>
                                            <td>
                                                <a href="{{ route('hr.employees.show', $employee) }}"
                                                   class="text-decoration-none fw-semibold">
                                                    {{ $employee->full_name }}
                                                </a>
                                            </td>
                                            <td><code>{{ $employee->login }}</code></td>
                                            <td class="text-end">
                                @foreach($employee->roles as $role)
                                    <span class="badge bg-secondary">{{ $role->display_name }}</span>
                                @endforeach
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif
                </div>
            </div>

            {{-- Шумора аз ҳар як нақш --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Аз рӯи нақш</h5>
                </div>
                <div class="card-body">
                    @forelse($perRole as $slug => $count)
                        <a href="{{ route('hr.employees.index', ['role' => $slug]) }}"
                           class="d-flex justify-content-between align-items-center py-2 text-decoration-none">
                            <span class="text-body">{{ $roleLabels[$slug] ?? $slug }}</span>
                            <span class="badge bg-primary">{{ $count }}</span>
                        </a>
                    @empty
                        <div class="text-muted">Ҳеҷ як корманд нест.</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Тезитасдиқи шахсияти донишҷӯ --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Тезитасдиқи донишҷӯ</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Танҳо барои тасдиқи шахсият ваҳте ки донишҷӯ маълумотнома сӯҳбад мекунад.
                    </p>

                    <form method="GET" action="{{ route('hr.dashboard') }}" class="row g-2">
                        <div class="col-md-8">
                            <input type="text" name="student" class="form-control"
                                   placeholder="Ном ё рақами донишҷӯ..."
                                   value="{{ $studentTerm }}">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-primary w-100">
                                <i class="bi bi-search me-1"></i> Ҷумҳа
                            </button>
                        </div>
                    </form>

                    @if($studentTerm !== '')
                        <hr class="my-3">

                        @if($studentResults->isEmpty())
                            <div class="text-muted">Донишҷӯ ёфт нашуд.</div>
                        @else
                            <table class="table table-sm align-middle mb-0">
                                <tbody>
                                    @foreach($studentResults as $student)
                                        <tr>
                                            <td>
                                                <a href="{{ route('hr.students.show', $student) }}"
                                                   class="text-decoration-none fw-semibold">
                                                    {{ $student->full_name }}
                                                </a>
                                                <div class="small text-muted">
                                                    {{ $student->student_id_number }}
                                                    @if($student->group)
                                                        @endif
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-light text-dark">
                                                    {{ $student->group?->name ?? '—' }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection