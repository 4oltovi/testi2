@extends('layouts.app')

@section('title', 'Қарздорӣ')
@section('page-header', 'Қарздорӣ: ' . ($debt->student?->user?->full_name ?? '—'))
@section('page-description')
    {{ $debt->subject?->name ?? '—' }} | Гурӯҳ: {{ $debt->student?->group?->name ?? '—' }}
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th class="text-muted">Донишҷӯ:</th>
                <td>{{ $debt->student?->user?->full_name ?? '—' }}</td>
                <th class="text-muted ms-4">Гурӯҳ:</th>
                <td>{{ $debt->student?->group?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted">Фан:</th>
                <td>{{ $debt->subject?->name ?? '—' }}</td>
                <th class="text-muted ms-4">Семестр:</th>
                <td>{{ $debt->semester?->name ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted">Сабаб:</th>
                <td>{{ $debt->reason ?? '—' }}</td>
                <th class="text-muted ms-4">Сана:</th>
                <td>{{ $debt->debt_date?->format('d.m.Y') ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted">Статус:</th>
                <td>
                    <span class="badge bg-{{ match($debt->status) {
                        'active' => 'warning', 'retake_scheduled' => 'info',
                        'escalated' => 'danger', 'resolved' => 'success',
                        default => 'secondary'
                    } }}">{{ $debt->status }}</span>
                </td>
                <th class="text-muted ms-4">Ҷмъо:</th>
                <td>{{ $debt->remaining_amount ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>

@if($debt->semesterGrade)
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-clipboard-data me-2"></i> Баҳо</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th class="text-muted">R1:</th>
                <td>{{ $debt->semesterGrade->rating1_score ?? '—' }}</td>
                <th class="text-muted ms-4">R2:</th>
                <td>{{ $debt->semesterGrade->rating2_score ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted">И.М.:</th>
                <td>{{ $debt->semesterGrade->independent_work_score ?? '—' }}</td>
                <th class="text-muted ms-4">Имтиҳон:</th>
                <td>{{ $debt->semesterGrade->exam_score ?? '—' }}</td>
            </tr>
            <tr>
                <th class="text-muted">Рақам:</th>
                <td>{{ $debt->semesterGrade->total_score ?? '—' }}</td>
                <th class="text-muted ms-4">Даража:</th>
                <td>{{ $debt->semesterGrade->letter_grade ?? '—' }}</td>
            </tr>
        </table>
    </div>
</div>
@endif

<div class="mt-4">
    <a href="{{ route('management.debts.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i> Бозгашт</a>
</div>
@endsection