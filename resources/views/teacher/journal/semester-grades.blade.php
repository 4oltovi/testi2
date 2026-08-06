@extends('layouts.app')

@section('title', 'Рейтинг ва Имтиҳон')
@section('page-header', 'Баҳоҳои семестрӣ')
@section('page-description')
    {{ $subjectAssignment->curriculum?->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }} | {{ $curriculum->credits }} кредит
@endsection

@section('content')
    {{-- Сабти рейтингҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Сабти рейтингҳо (R1, R2, КМ)</h6>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="{{ route('teacher.journal.set-rating', $subjectAssignment) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Донишҷӯ</th>
                                <th title="Рейтинги 1 (ҳисобшуда)">R1 (авто)</th>
                                <th title="Рейтинги 1 (сабтшуда)">R1</th>
                                <th title="Рейтинги 2 (ҳисобшуда)">R2 (авто)</th>
                                <th title="Рейтинги 2 (сабтшуда)">R2</th>
                                <th>КМ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php
                                    $grade = $semesterGrades[$student->id] ?? null;
                                    $calc = $calculatedRatings[$student->id] ?? ['rating1' => 0, 'rating2' => 0];
                                    $isLocked = $grade?->is_finalized ?? false;
                                @endphp
                                <tr class="{{ $isLocked ? 'table-light' : '' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->user?->short_name }}</td>
                                    <td class="text-muted">{{ number_format($calc['rating1'], 1) }}</td>
                                    <td>
                                        <input type="hidden" name="ratings[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        <input type="number" name="ratings[{{ $index }}][rating1_score]"
                                               class="form-control form-control-sm" style="width: 70px"
                                               value="{{ $grade?->rating1_score }}" min="0" max="100" step="0.5"
                                               {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td class="text-muted">{{ number_format($calc['rating2'], 1) }}</td>
                                    <td>
                                        <input type="number" name="ratings[{{ $index }}][rating2_score]"
                                               class="form-control form-control-sm" style="width: 70px"
                                               value="{{ $grade?->rating2_score }}" min="0" max="100" step="0.5"
                                               {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                    <td>
                                        <input type="number" name="ratings[{{ $index }}][independent_work_score]"
                                               class="form-control form-control-sm" style="width: 70px"
                                               value="{{ $grade?->independent_work_score }}" min="0" max="100" step="0.5"
                                               {{ $isLocked ? 'disabled' : '' }}>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабти рейтингҳо
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Сабти баҳои имтиҳон --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Сабти баҳои имтиҳон</h6>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="{{ route('teacher.journal.set-exam', $subjectAssignment) }}">
                @csrf
                <div class="px-3 pt-3">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Навъи имтиҳон</label>
                            <select name="exam_type" class="form-select" required>
                                <option value="main">Имтиҳони асосӣ</option>
                                <option value="retake">Такрорсупорӣ</option>
                                <option value="retake2">Комиссионӣ</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Донишҷӯ</th>
                                <th>Имт. асосӣ</th>
                                <th>Такрорсуп.</th>
                                <th>Баҳо (0-100)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $index => $student)
                                @php
                                    $grade = $semesterGrades[$student->id] ?? null;
                                    $isLocked = $grade?->is_finalized ?? false;
                                @endphp
                                <tr class="{{ $isLocked ? 'table-light' : '' }}">
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->user?->short_name }}</td>
                                    <td>{{ $grade?->exam_score ?? '—' }}</td>
                                    <td>{{ $grade?->retake_score ?? '—' }}</td>
                                    <td>
                                        <input type="hidden" name="exam_scores[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        <input type="number" name="exam_scores[{{ $index }}][exam_score]"
                                               class="form-control form-control-sm" style="width: 80px"
                                               min="0" max="100" step="0.5"
                                               {{ $isLocked ? 'disabled' : '' }}
                                               placeholder="0-100">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end">
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg me-1"></i> Сабти баҳои имтиҳон
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ведомости ниҳоӣ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-file-earmark-check me-2"></i> Ведомости ниҳоӣ</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered journal-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th class="student-name">Донишҷӯ</th>
                            <th>R1</th>
                            <th>R2</th>
                            <th>КМ</th>
                            <th>Имт.</th>
                            <th>Такр.</th>
                            <th><strong>Ниҳоӣ</strong></th>
                            <th>Баҳо</th>
                            <th>GP</th>
                            <th>Тасдиқ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php $grade = $semesterGrades[$student->id] ?? null; @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="student-name text-start">{{ $student->user?->short_name }}</td>
                                <td>{{ $grade?->rating1_score !== null ? number_format($grade->rating1_score, 0) : '—' }}</td>
                                <td>{{ $grade?->rating2_score !== null ? number_format($grade->rating2_score, 0) : '—' }}</td>
                                <td>{{ $grade?->independent_work_score !== null ? number_format($grade->independent_work_score, 0) : '—' }}</td>
                                <td>{{ $grade?->exam_score !== null ? number_format($grade->exam_score, 0) : '—' }}</td>
                                <td>{{ $grade?->retake_score !== null ? number_format($grade->retake_score, 0) : '—' }}</td>
                                <td><strong>{{ $grade?->total_score !== null ? number_format($grade->total_score, 1) : '—' }}</strong></td>
                                <td>
                                    @if($grade?->letter_grade)
                                        @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                                        <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                                    @else — @endif
                                </td>
                                <td>{{ $grade?->grade_point !== null ? number_format($grade->grade_point, 2) : '—' }}</td>
                                <td>
                                    @if($grade && !$grade->is_finalized && $grade->rating1_score !== null && $grade->exam_score !== null)
                                        <form action="{{ route('teacher.journal.finalize', $grade) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" data-confirm="Тасдиқ кардан?">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @elseif($grade?->is_finalized)
                                        <i class="bi bi-lock-fill text-success" title="Тасдиқ шуда"></i>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            <small class="text-muted">Формула: ((Рейтинг + Журнал) / 2) + (Имтиҳон × 0,5) | Ҳадди ақал: 50% (D)</small>
        </div>
    </div>
@endsection
