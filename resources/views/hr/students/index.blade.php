@extends('layouts.app')

@section('title', 'Донишҷӯён')
@section('page-header', 'Донишҷӯён')
@section('page-description', 'Танҳо барои тасдиқи шахсияти донишҷӯ (маълумотнома)')

@section('content')
    <div class="alert alert-info d-flex align-items-start">
        <i class="bi bi-info-circle me-2 fs-5"></i>
        <div>
            Ин рӯйхат танҳо барои тасдиқи шахсият аст. Баҳо, GPA, кредит,
            қарздорӣ, ҳазфи ишқтибоӣ ва маълумоти пулӣ дар ин ҷо
            намоён намешаванд.
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('hr.students.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control"
                           placeholder="Ҷумҳа: ном, рақами донишҷӯ ё гурӯҳ..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-search me-1"></i> Ҷумҳа
                    </button>
                    <a href="{{ route('hr.students.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Рӯйхат</h5>
            <span class="text-muted small">{{ $students->total() }} донишҷӯ</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Фото</th>
                        <th>ФИО</th>
                        <th>Рақами донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Хулоса</th>
                        <th>Курс</th>
                        <th>Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $student)
                        <tr>
                            <td>
                                @if($student->user?->avatar && file_exists(public_path('storage/' . $student->user->avatar)))
                                    <img src="{{ asset('storage/' . $student->user->avatar) }}"
                                         alt="{{ $student->full_name }}"
                                         class="rounded-circle"
                                         style="width: 36px; height: 36px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center"
                                         style="width: 36px; height: 36px;">
                                        {{ mb_substr($student->user?->first_name ?? '?', 0, 1) }}
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.students.show', $student) }}"
                                   class="text-decoration-none fw-semibold">
                                    {{ $student->full_name }}
                                </a>
                            </td>
                            <td><code>{{ $student->student_id_number }}</code></td>
                            <td>{{ $student->group?->name ?? '—' }}</td>
                            <td>{{ $student->specialty?->name ?? '—' }}</td>
                            <td>{{ $student->course?->name ?? '—' }}</td>
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
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Донишҷӯ ёфт нашуд.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="card-footer bg-white">
                {{ $students->links() }}
            </div>
        @endif
    </div>
@endsection