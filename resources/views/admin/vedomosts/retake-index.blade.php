@extends('layouts.app')

@section('title', 'Ведомостҳои такрорӣ')
@section('page-header', 'Ведомостҳои такрорӣ')
@section('page-description', 'Рӯйхати ведомостҳои такрорӣ')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Ведомостҳои такрорӣ</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">№</th>
                                <th>Гурӯҳ</th>
                                <th>Фан</th>
                                <th>Семестр</th>
                                <th>Имтиҳон</th>
                                <th>Донишҷӯён</th>
                                <th class="text-center">Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($retakeVedomosts as $index => $rv)
                            <tr>
                                <td style="color: var(--text-muted);">{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $rv->group->name ?? '-' }}</strong>
                                </td>
                                <td>{{ $rv->retakeExam->subject->name ?? '-' }}</td>
                                <td>{{ $rv->retakeExam->semester->name ?? '-' }}</td>
                                <td>{{ $rv->exam_date?->format('d.m.Y') ?? '-' }}</td>
                                <td>
                                    @php
                                        $count = \App\Models\RetakeExamStudent::where('retake_exam_id', $rv->retake_exam_id)
                                            ->whereHas('student', fn($q) => $q->where('group_id', $rv->group_id))
                                            ->count();
                                    @endphp
                                    {{ $count }} нафар
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('admin.retake-exams.vedomost.show', $rv) }}" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Дидан
                                    </a>
                                    <a href="{{ route('admin.retake-exams.vedomost.pdf', $rv) }}" class="btn btn-sm btn-outline-danger" target="_blank">
                                        <i class="bi bi-printer me-1"></i> PDF
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                                    Ведомостҳои такрорӣ ёфт нашданд.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
