@extends('layouts.app')

@section('title', 'Имтиҳонҳои такрорӣ')
@section('page-header', 'Имтиҳонҳои такрорӣ')
@section('page-description', 'Имтиҳонҳо барои донишҷӯёни қарздор')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Рӯйхати имтиҳонҳои такрорӣ</h6>
                <a href="{{ route('admin.retake-exams.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Имтиҳони нав
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.retake-exams.index') }}" class="row g-3 mb-3">
                    <div class="col-md-3">
                        <select name="subject_id" class="form-select">
                            <option value="">Ҳамаи фанҳо</option>
                            @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}" {{ request('subject_id') == $subject->id ? 'selected' : '' }}>
                                {{ $subject->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="semester_id" class="form-select">
                            <option value="">Ҳамаи семестрҳо</option>
                            @foreach($semesters as $semester)
                            <option value="{{ $semester->id }}" {{ request('semester_id') == $semester->id ? 'selected' : '' }}>
                                {{ $semester->name }} ({{ $semester->academicYear->name ?? '' }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Ҳамаи ҳолатҳо</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Черновик</option>
                            <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Нақшакардашуда</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Анҷомшуда</option>
                            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Батълшуда</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search me-1"></i> Ҷустуҷӯ
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Фан</th>
                                <th>Семестр</th>
                                <th>Ном</th>
                                <th>Сана</th>
                                <th>Ҳолат</th>
                                <th>Донишҷӯён</th>
                                <th>Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($retakeExams as $exam)
                            <tr>
                                <td>{{ $loop->iteration + ($retakeExams->currentPage() - 1) * $retakeExams->perPage() }}</td>
                                <td>{{ $exam->subject->name ?? '-' }}</td>
                                <td>{{ $exam->semester->name ?? '-' }}</td>
                                <td>{{ $exam->title }}</td>
                                <td>{{ $exam->exam_date?->format('d.m.Y') ?? '-' }}</td>
                                <td>
                                    @php
                                    $statusBadge = match($exam->status) {
                                        'draft' => 'bg-secondary',
                                        'scheduled' => 'bg-info',
                                        'completed' => 'bg-success',
                                        'cancelled' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                    $statusLabel = match($exam->status) {
                                        'draft' => 'Черновик',
                                        'scheduled' => 'Нақшакардашуда',
                                        'completed' => 'Анҷомшуда',
                                        'cancelled' => 'Батълшуда',
                                        default => $exam->status,
                                    };
                                    @endphp
                                    <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $exam->students_count }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.retake-exams.show', $exam) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.retake-exams.vedomost', $exam) }}" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-file-earmark-excel"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                    Ҳанӯз имтиҳони такрорӣ сохта нашудааст.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $retakeExams->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
