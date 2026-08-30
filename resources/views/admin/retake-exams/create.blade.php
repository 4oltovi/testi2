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
                <form method="POST" action="{{ route('admin.retake-exams.store') }}">
                    @csrf
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Фан <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select" required id="subjectSelect">
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
                            <select name="semester_id" class="form-select" required id="semesterSelect">
                                <option value="">Интихоб кунед...</option>
                                @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" {{ $semesterId == $semester->id ? 'selected' : '' }}>
                                    {{ $semester->name }} ({{ $semester->academicYear->name ?? '' }})
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи имтиҳон <span class="text-danger">*</span></label>
                            <input type="date" name="exam_date" class="form-control" required value="{{ old('exam_date', date('Y-m-d')) }}">
                        </div>
                    </div>

                    <div id="mainExamInfo" class="alert alert-info d-none">
                        <h6 class="alert-heading"><i class="bi bi-info-circle me-1"></i> Маълумоти имтиҳони асосӣ</h6>
                        <div class="row g-2 mt-1">
                            <div class="col-md-3">
                                <strong>Формат:</strong> <span id="mainExamFormat">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Даврият:</strong> <span id="mainExamDuration">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Ҳадди ақал:</strong> <span id="mainExamPassingScore">-</span>
                            </div>
                            <div class="col-md-3">
                                <strong>Саволҳо:</strong> <span id="mainExamQuestions">-</span>
                            </div>
                        </div>
                        <p class="mt-2 mb-0 small text-muted">
                            Имтиҳони такрорӣ саволномаи имтиҳони асосиро истифода мебарад.
                        </p>
                    </div>

                    <div id="debtorInfo" class="alert alert-warning d-none">
                        <h6 class="alert-heading"><i class="bi bi-people me-1"></i> Донишҷӯёни қарздор</h6>
                        <p class="mb-0">Баъд аз сабт кардан <span id="debtorCount">0</span> донишҷӯ ба имтиҳон такрорӣ таъин мешаванд.</p>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <a href="{{ route('admin.retake-exams.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-success" id="submitBtn" disabled>
                            <i class="bi bi-check-lg me-1"></i> Имтиҳонро сохтан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const subjectSelect = document.getElementById('subjectSelect');
    const semesterSelect = document.getElementById('semesterSelect');
    const submitBtn = document.getElementById('submitBtn');
    const mainExamInfo = document.getElementById('mainExamInfo');
    const debtorInfo = document.getElementById('debtorInfo');
    
    function checkSelection() {
        if (subjectSelect.value && semesterSelect.value) {
            fetch(`{{ route('admin.retake-exams.check-main-exam') }}?subject_id=${subjectSelect.value}&semester_id=${semesterSelect.value}`)
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        document.getElementById('mainExamFormat').textContent = data.format;
                        document.getElementById('mainExamDuration').textContent = data.duration_minutes + ' дақиқа';
                        document.getElementById('mainExamPassingScore').textContent = data.passing_score + '%';
                        document.getElementById('mainExamQuestions').textContent = data.questions_count;
                        document.getElementById('debtorCount').textContent = data.eligible_debtors;
                        mainExamInfo.classList.remove('d-none');
                        debtorInfo.classList.remove('d-none');
                        submitBtn.disabled = false;
                    } else {
                        mainExamInfo.classList.add('d-none');
                        debtorInfo.classList.add('d-none');
                        submitBtn.disabled = true;
                        alert(data.message || 'Барои ин фан ва семестр имтиҳони асосӣ ёфт нашуд.');
                    }
                });
        } else {
            mainExamInfo.classList.add('d-none');
            debtorInfo.classList.add('d-none');
            submitBtn.disabled = true;
        }
    }
    
    subjectSelect.addEventListener('change', checkSelection);
    semesterSelect.addEventListener('change', checkSelection);
});
</script>
@endpush
