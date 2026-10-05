@extends('layouts.app')

@section('title', 'Баҳоҳои семестрӣ')
@section('page-header', 'Баҳоҳои семестрӣ — Рейтинг ва Имтиҳон')
@section('page-description')
{{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }} | {{ $semester->name }}
@endsection

@section('content')
{{-- Маълумоти фан --}}
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 bg-primary bg-opacity-10 h-100">
            <div class="card-body text-center">
                <form method="POST" action="{{ route('admin.journal.credits.update', $subjectAssignment) }}"
                    class="d-inline">
                    @csrf
                    <input type="number" name="credits" min="1" max="30"
                        value="{{ $subjectAssignment->credits }}"
                        class="form-control form-control-sm d-inline-block text-center fw-bold text-primary"
                        style="max-width: 70px; font-size: 1.2rem;">
                    <button type="submit" class="btn btn-sm btn-outline-primary" title="Сабт кардан">
                        <i class="bi bi-check-lg"></i>
                    </button>
                </form>
                <small class="text-muted d-block mt-1">Кредит</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 bg-info bg-opacity-10 h-100">
            <div class="card-body text-center">
                <h3 class="text-info mb-0">{{ $students->count() }}</h3>
                <small class="text-muted">Донишҷӯ</small>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 bg-light h-100">
            <div class="card-body">
                <small class="text-muted d-block">Формулаи баҳо:</small>
                <strong>(R1 + R2) ÷ 4 + (Имтиҳон × 0,5)</strong>
                <br><small class="text-muted">Имтиҳон аз тести онлайн автоматӣ гирифта мешавад</small>
                <br><small class="text-muted">Ҳадди ақали гузариш: 50 (баҳои D)</small>
            </div>
        </div>
    </div>
</div>

{{-- Ҷадвали баҳоҳо --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i> Ведомост</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
                <table class="table table-sm table-bordered journal-table mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">№</th>
                        <th style="width: 110px;">ID</th>
                        <th class="student-name">Донишҷӯ</th>
                        <th title="Рейтинги 1 (ҳафтаи 1-8)">
                            R1
                        </th>
                        <th title="Рейтинги 2 (ҳафтаи 9-16)">
                            R2 
                        </th>
                        <th title="Имтиҳони асосӣ">Имт.</th>
                        <th title="Такрорсупорӣ">Такр.</th>
                        <th title="Баҳои ниҳоӣ">Ниҳоӣ</th>
                        <th title="Баҳои ҳарфӣ">Баҳо</th>
                        <th title="Grade Point">GP</th>
                        <th>Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                    @php
                    $grade = $semesterGrades[$student->id] ?? null;
                    $calc = $calculatedGrades[$student->id] ?? ['rating1' => 0, 'rating2' => 0, 'exam' => 0, 'retake_score' => null, 'retake_letter_grade' => null, 'retake_grade_point' => null, 'total_score' => null, 'letter_grade' => null, 'grade_point' => null, 'status' => null];
                    @endphp
                    <tr class="{{ $grade && $grade->is_finalized ? 'table-light' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->student_id_number ?? '—' }}</td>
                        <td class="student-name text-start">
                            <a href="{{ route('admin.students.show', $student) }}">
                                {{ $student->user?->short_name }}
                            </a>
                        </td>
                        <td>
                            @php
                                $r1 = $calc['rating1'] ?? 0;
                                $cr1 = $calc['computer_rating1'] ?? 0;
                                $ej1 = max(0, $r1 - $cr1);
                            @endphp
                            <span title="ЭЖ: {{ number_format($ej1, 0) }}/60 | ТК: {{ number_format($cr1, 0) }}/40">
                                {{ $calc['rating1'] !== null ? number_format($r1, 1) : '—' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $r2 = $calc['rating2'] ?? 0;
                                $cr2 = $calc['computer_rating2'] ?? 0;
                                $ej2 = max(0, $r2 - $cr2);
                            @endphp
                            <span title="ЭЖ: {{ number_format($ej2, 0) }}/60 | ТК: {{ number_format($cr2, 0) }}/40">
                                {{ $calc['rating2'] !== null ? number_format($r2, 1) : '—' }}
                            </span>
                        </td>
                        <td>{{ $calc['exam'] !== null ? number_format($calc['exam'], 0) : '—' }}</td>
                        <td>
                            @if($calc['retake_score'] !== null)
                            <strong>{{ number_format($calc['retake_score'], 0) }}</strong>
                            @else
                            —
                            @endif
                        </td>
                        <td>
                            @if($calc['total_score'] !== null)
                            <strong>{{ number_format($calc['total_score'], 1) }}</strong>
                            @else
                            —
                            @endif
                        </td>
                        <td>
                            @if($calc['letter_grade'])
                            @php $gradeEnum = \App\Enums\GradeScale::tryFrom($calc['letter_grade']); @endphp
                            <span class="badge {{ $gradeEnum?->badgeClass() ?? 'bg-secondary' }}">
                                {{ $calc['letter_grade'] }}
                            </span>
                            @else
                            —
                            @endif
                        </td>
                        <td>{{ $calc['grade_point'] !== null ? number_format($calc['grade_point'], 2) : '—' }}</td>
                        <td>
                            @if($calc['status'])
                            @php
                            $statusBadge = match($calc['status']) {
                                'passed' => 'bg-success',
                                'failed' => 'bg-danger',
                                'retake' => 'bg-warning',
                                'debt' => 'bg-danger',
                                'in_progress' => 'bg-secondary',
                                default => 'bg-secondary',
                            };
                            $statusLabel = match($calc['status']) {
                                'passed' => 'Гузашт',
                                'failed' => 'Нагуз.',
                                'retake' => 'Такр.',
                                'debt' => 'Қарз',
                                'in_progress' => 'Ҷараён',
                                default => $calc['status'],
                            };
                            @endphp
                            <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">
        <div class="row">
            <div class="col-md-6">
                <small class="text-muted">
                    <strong>Тавзеҳот:</strong>
                    R1 = ЭЖ (0-60) + ТК (0-40) = Рейтинги 1 (авто) |
                    R2 = ЭЖ (0-60) + ТК (0-40) = Рейтинги 2 (авто) |
                    Имт. = Имтиҳон (аз тести онлайн)
                </small>
            </div>
            <div class="col-md-6 text-end">
                <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Шкалаи баҳо --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Шкалаи баҳогузорӣ (Низоми кредитии Тоҷикистон)</h6>
    </div>
    <div class="card-body">
        @php
            // Ҷадвали шкала аз GradeScale хонда мешавад — як манбаъи ҳақиқат
            // бо transcript, то ҳар ду ҳамин ҳуқуқро нишон диҳанд.
            $scaleRows = \App\Enums\GradeScale::cases();
            // `90` на 90.00 шавад, `0` бо «-» нашавад
            $fmtNum = fn (float $n) => $n == floor($n)
                ? (string) (int) $n
                : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
            $fmtRange = fn ($r) => $fmtNum((float) $r['min']) . '-' . $fmtNum((float) $r['max']);
        @endphp
        <div class="row">
            @foreach(array_chunk($scaleRows, 6) as $half)
                <div class="col-md-6">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="{{ $half[0]->isPassing() ? 'table-success' : 'table-warning' }}">
                            <tr>
                                <th>Баҳо</th>
                                <th>GPA</th>
                                <th>%</th>
                                <th class="text-center">Анъанавӣ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($half as $s)
                                <tr @unless($s->isPassing()) class="{{ $s->badgeClass() === 'bg-dark' ? 'table-dark' : 'table-danger' }}" @endunless>
                                    <td><span class="badge {{ $s->badgeClass() }}">{{ $s->value }}</span></td>
                                    <td>{{ number_format($s->gradePoint(), 2) }}</td>
                                    <td>{{ $fmtRange($s->percentageRange()) }}</td>
                                    <td class="text-center fw-bold">{{ $s->traditionalFivePoint() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
