@extends('layouts.app')

@section('title', $student->full_name)
@section('page-header', $student->full_name)
@section('page-description', 'Тасдиқи шахсияти донишҷӯ')

@section('page-actions')
    <a href="{{ route('hr.students.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Бозгашт
    </a>
@endsection

@section('content')
    <div class="alert alert-warning">
        <i class="bi bi-shield-lock me-1"></i>
        Ин саҳифа танҳо барои тасдиқи шахсият аст. Кадр баҳо, кредит, қарздорӣ,
        ҳазфи ишқтибоӣ ва маълумоти пулӣ донишҷӯро намебинад ва наметавонад таҳрир кунад.
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    @if($student->user?->avatar && file_exists(public_path('storage/' . $student->user->avatar)))
                        <img src="{{ asset('storage/' . $student->user->avatar) }}"
                             alt="{{ $student->full_name }}"
                             class="rounded-circle mb-3"
                             style="width: 130px; height: 130px; object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 130px; height: 130px; font-size: 2.5rem;">
                            {{ mb_substr($student->user?->first_name ?? '?', 0, 1) }}
                        </div>
                    @endif

                    <h5 class="mb-1">{{ $student->full_name }}</h5>
                    <code class="text-muted">{{ $student->student_id_number }}</code>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Маълумоти шахсӣ</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tr>
                            <th style="width: 240px;">ФИО</th>
                            <td class="fw-semibold">{{ $student->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Рақами донишҷӯ</th>
                            <td><code>{{ $student->student_id_number }}</code></td>
                        </tr>
                        <tr>
                            <th>Гурӯҳ</th>
                            <td>{{ $student->group?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Хулоса</th>
                            <td>{{ $student->specialty?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Курс</th>
                            <td>{{ $student->course?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Ҳолат</th>
                            <td>
                                @if($student->status === 'active')
                                    <span class="badge bg-success">Фаъол</span>
                                @elseif($student->status === 'graduated')
                                    <span class="badge bg-info text-dark">Хатм кардааст</span>
                                @else
                                    <span class="badge bg-warning text-dark">{{ $student->status }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Санаи сабт</th>
                            <td>{{ $student->enrollment_date?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>Санаи таваллуд</th>
                            <td>{{ $student->birth_date?->format('d.m.Y') ?? '—' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection