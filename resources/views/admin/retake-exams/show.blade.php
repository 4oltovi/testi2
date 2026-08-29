@extends('layouts.app')

@section('title', 'Имтиҳони такрорӣ — ' . $retakeExam->title)
@section('page-header', 'Имтиҳони такрорӣ')
@section('page-description', $retakeExam->subject->name ?? 'Фан' . ' | ' . $retakeExam->semester->name ?? '')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">{{ $retakeExam->title }}</h6>
                    <small class="text-muted">
                        {{ $retakeExam->subject->name ?? '-' }} |
                        {{ $retakeExam->semester->name ?? '-' }} |
                        {{ $retakeExam->exam_date?->format('d.m.Y') ?? '-' }}
                    </small>
                </div>
                <div>
                    <a href="{{ route('admin.retake-exams.vedomost', $retakeExam) }}" class="btn btn-sm btn-outline-success">
                        <i class="bi bi-file-earmark-excel me-1"></i> Ведомост
                    </a>
                    <a href="{{ route('admin.retake-exams.print-vedomost', $retakeExam) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="bi bi-printer me-1"></i> Чоп
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Формат:</strong> {{ match($retakeExam->format) {
                            'online_test' => 'Онлайн',
                            'written' => 'Хаттӣ',
                            'oral' => 'Даҳонӣ',
                            'mixed' => 'Омехта',
                            default => $retakeExam->format,
                        } }}
                    </div>
                    <div class="col-md-3">
                        <strong>Даврият:</strong> {{ $retakeExam->duration_minutes }} дақ.
                    </div>
                    <div class="col-md-3">
                        <strong>Ҳадди ақал:</strong> {{ $retakeExam->passing_score }}%
                    </div>
                    <div class="col-md-3">
                        <strong>Ҳолат:</strong>
                        @php
                        $statusBadge = match($retakeExam->status) {
                            'draft' => 'bg-secondary',
                            'scheduled' => 'bg-info',
                            'completed' => 'bg-success',
                            'cancelled' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ $retakeExam->status }}</span>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Донишҷӯ</th>
                                <th>Гурӯҳ</th>
                                <th>Баҳои аслӣ</th>
                                <th>Баҳои такрорӣ</th>
                                <th>Кӯшиш</th>
                                <th>Ҳолат</th>
                                <th>Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($retakeExam->retakeExamStudents as $student)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $student->student->user?->short_name ?? 'Донишҷӯ #' . $student->student->id }}</td>
                                <td>{{ $student->student->group?->name ?? '-' }}</td>
                                <td>
                                    {{ $student->academicDebt->original_grade ?? '-' }}
                                    ({{ $student->academicDebt->original_score ?? '-' }})
                                </td>
                                <td>
                                    @if($student->score !== null)
                                    <strong>{{ $student->letter_grade }}</strong>
                                    ({{ number_format($student->score, 2) }}%)
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $student->attempt_number }}</td>
                                <td>
                                    @php
                                    $statusBadge = match($student->status) {
                                        'pending' => 'bg-warning',
                                        'passed' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        'absent' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $student->status }}</span>
                                </td>
                                <td>
                                    @if($student->status === 'pending')
                                    <button class="btn btn-sm btn-primary"
                                        onclick="openScoreModal({{ $student->id }}, '{{ $student->student->user?->short_name ?? 'Донишҷӯ' }}')">
                                        <i class="bi bi-pencil"></i> Баҳо
                                    </button>
                                    @else
                                    <span class="text-muted">
                                        {{ $student->examined_at?->format('d.m.Y H:i') ?? '-' }}
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.retake-exams.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for entering score -->
<div class="modal fade" id="scoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Вориди натиҷа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="scoreForm">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="retake_exam_student_id" id="scoreStudentId">
                    <div class="mb-3">
                        <label class="form-label">Донишҷӯ</label>
                        <input type="text" class="form-control" id="scoreStudentName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Баҳо (%) <span class="text-danger">*</span></label>
                        <input type="number" name="score" class="form-control" required min="0" max="100" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тавзеҳот</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Бозгашт</button>
                    <button type="submit" class="btn btn-primary">Сабт кардан</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openScoreModal(studentId, studentName) {
    document.getElementById('scoreStudentId').value = studentId;
    document.getElementById('scoreStudentName').value = studentName;
    document.getElementById('scoreForm').action = '/admin/retake-exams/students/' + studentId + '/score';
    new bootstrap.Modal(document.getElementById('scoreModal')).show();
}
</script>
@endsection
