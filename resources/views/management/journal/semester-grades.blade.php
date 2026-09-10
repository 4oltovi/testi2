@extends('layouts.app')

@section('title', 'Баҳоҳои семестрӣ')
@section('page-header', 'Баҳоҳои семестрӣ')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }}
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумот</h6>
    </div>
    <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
            <tr>
                <th class="text-muted">Фан:</th>
                <td>{{ $subject->name }}</td>
                <th class="text-muted ms-4">Гурӯҳ:</th>
                <td>{{ $subjectAssignment->group?->name }}</td>
            </tr>
            <tr>
                <th class="text-muted">Омӯзгор:</th>
                <td>{{ $subjectAssignment->teacher?->first_name }} {{ $subjectAssignment->teacher?->last_name }}</td>
                <th class="text-muted ms-4">Семестр:</th>
                <td>{{ $semester->name }}</td>
            </tr>
            <tr>
                <th class="text-muted">Кредит:</th>
                <td>{{ $subjectAssignment->credits }}</td>
                <th class="text-muted ms-4">Навъ:</th>
                <td>{{ $subject->exam_type }}</td>
            </tr>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-clipboard-data me-2"></i> Баҳоҳо</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th class="text-center">R1</th>
                        <th class="text-center">R2</th>
                        <th class="text-center">ИМТ</th>
                        <th class="text-center">Такрори</th>
                        <th class="text-end">Нихои</th>
                        <th class="text-end">GPA</th>
                        <th class="text-center">Эҳ</th>
                        <th class="text-center">Статус</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $i => $student)
                    @php
                        $sg = $semesterGrades[$student->id] ?? null;
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $student->user?->last_name }} {{ $student->user?->first_name }}</td>
                        <td class="text-center">{{ $sg?->rating1_score ?? '—' }}</td>
                        <td class="text-center">{{ $sg?->rating2_score ?? '—' }}</td>
                        <td class="text-center">{{ $sg?->independent_work_score ?? '—' }}</td>
                        <td class="text-center">{{ $sg?->exam_score ?? '—' }}</td>
                        <td class="text-end">{{ $sg?->total_score ?? '—' }}</td>
                        <td class="text-end">{{ $sg?->grade_point ?? '—' }}</td>
                        <td class="text-center">
                            @if($sg)
                                <span class="badge bg-{{ match($sg->letter_grade) {
                                    'A' => 'success', 'B' => 'info', 'C' => 'primary',
                                    'D' => 'warning', 'F' => 'danger', default => 'secondary'
                                } }}">{{ $sg->letter_grade }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($sg)
                                <span class="badge bg-{{ $sg->status === 'passed' ? 'success' : ($sg->status === 'retake' ? 'warning' : 'danger') }}">{{ $sg->status }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted py-3">Донишҷӯ нест</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
