@extends('layouts.app')

@section('title', 'Имтиҳони такрорӣ — нав')
@section('page-header', 'Имтиҳони такрорӣ')
@section('page-description', 'Сохтани имтиҳони такрорӣ барои донишҷӯёни қарздор')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Имтиҳони такрорӣ</h6>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.retake-exams.create') }}" class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Фан <span class="text-danger">*</span></label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Интихоб кунед...</option>
                            @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ $subjectId == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Семестр <span class="text-danger">*</span></label>
                        <select name="semester_id" class="form-select" required>
                            <option value="">Интихоб кунед...</option>
                            @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>
                                {{ $semester->name }} ({{ $semester->academicYear->name ?? '' }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i> Ҷустуҷӯ
                        </button>
                    </div>
                </form>

                @if($subjectId && $semesterId)
                <hr>
                @if($eligibleDebts->isNotEmpty())
                <form method="POST" action="{{ route('admin.retake-exams.store') }}">
                    @csrf
                    <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                    <input type="hidden" name="semester_id" value="{{ $semesterId }}">

                    <div class="row g-3 mb-4">
                        <div class="col-md-12">
                            <label class="form-label">Номи имтиҳон <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required
                                placeholder="Масалан: Имтиҳони такрорӣ — Информатика">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Формат</label>
                            <select name="format" class="form-select">
                                <option value="written">Хаттӣ</option>
                                <option value="oral">Даҳонӣ</option>
                                <option value="online_test" selected>Онлайн</option>
                                <option value="mixed">Омехта</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Даврият (дақ.)</label>
                            <input type="number" name="duration_minutes" class="form-control" value="60" min="1" max="300">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҳадди ақал (%)</label>
                            <input type="number" name="passing_score" class="form-control" value="50" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Шумораи кӯшиш</label>
                            <input type="number" name="max_attempts" class="form-control" value="1" min="1" max="5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи имтиҳон</label>
                            <input type="date" name="exam_date" class="form-control" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Тавзеҳот</label>
                            <textarea name="notes" class="form-control" rows="1"></textarea>
                        </div>
                    </div>

                    <h6 class="mb-3">Донишҷӯёни қарздор ({{ $eligibleDebts->count() }} нафар)</h6>

                    @foreach($eligibleDebts as $groupName => $students)
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <strong>{{ $groupName }}</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach($students as $debt)
                                @php
                                $student = $debt->student;
                                @endphp
                                <div class="col-md-4 col-lg-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="student_ids[]" value="{{ $student->id }}"
                                            id="student_{{ $student->id }}" checked>
                                        <label class="form-check-label" for="student_{{ $student->id }}">
                                            {{ $student->user?->short_name ?? 'Донишҷӯ #' . $student->id }}
                                        </label>
                                        <br>
                                        <small class="text-muted">
                                            Баҳо: {{ $debt->original_grade }} ({{ $debt->original_score }}%)
                                        </small>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('admin.retake-exams.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Имтиҳонро сохтан
                        </button>
                    </div>
                </form>
                @else
                <div class="alert alert-warning">
                    Барои ин фан ва семестр донишҷӯёни қарздор ёфт нашуданд.
                </div>
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
