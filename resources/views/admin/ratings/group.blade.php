@extends('layouts.app')

@section('title', 'Рейтинги гурӯҳ: ' . $group->name)
@section('page-header', 'Рейтинги гурӯҳ: ' . $group->name)
@section('page-description')
    {{ $group->specialty->name ?? '' }} | Курси {{ $group->course }} | {{ $group->specialty->faculty->name ?? '' }}
@endsection

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="semester_id" class="form-label">Семестр</label>
                <select name="semester_id" id="semester_id" class="form-select">
                    <option value="">Ҳамаи семестрҳо</option>
                    @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>
                            {{ $semester->name }}
                        </option>
                    @endforeach
                </select>
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
        @if($groupRating->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>GPA</th>
                            <th>Кумулятивӣ</th>
                            <th>Кредитҳо</th>
                            <th>Гузашт</th>
                            <th>Нагузашт</th>
                            <th>Қарз</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupRating as $index => $rating)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $rating['student_name'] }}</td>
                                <td>
                                    <span class="fw-bold {{ $rating['gpa'] >= 4.0 ? 'text-success' : ($rating['gpa'] >= 3.0 ? 'text-primary' : ($rating['gpa'] >= 2.0 ? 'text-warning' : 'text-danger')) }}">
                                        {{ number_format($rating['gpa'], 2) }}
                                    </span>
                                </td>
                                <td>{{ number_format($rating['cumulative_gpa'], 2) }}</td>
                                <td>{{ $rating['credits_earned'] }}</td>
                                <td><span class="text-success">{{ $rating['subjects_passed'] }}</span></td>
                                <td><span class="text-danger">{{ $rating['subjects_failed'] }}</span></td>
                                <td>
                                    @if($rating['has_debts'])
                                        <span class="badge bg-danger">Ҳа</span>
                                    @else
                                        <span class="badge bg-success">Не</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-4 text-muted">
                <i class="bi bi-bar-chart fs-1"></i>
                <p class="mt-2">Маълумот вуҷуд надорад</p>
            </div>
        @endif
    </div>
</div>

<div class="mt-3">
    <a href="{{ url('/admin/ratings') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Бозгашт
    </a>
</div>
@endsection
