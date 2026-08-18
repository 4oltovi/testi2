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
                <br><small class="text-muted">Ҳадди ақали гузариш: 50% (баҳои D)</small>
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
                        <th class="student-name">#</th>
                        <th class="student-name">Донишҷӯ</th>
                        <th title="Рейтинги 1 (ҳафтаи 1-8)">R1</th>
                        <th title="Рейтинги 2 (ҳафтаи 9-16)">R2</th>
                        <th title="Корҳои мустақилона">КМ</th>
                        <th title="Имтиҳони асосӣ">Имт.</th>
                        <th title="Такрорсупорӣ">Такр.</th>
                        <th title="Комиссионӣ">Ком.</th>
                        <th title="Баҳои ниҳоӣ (%)">Ниҳоӣ</th>
                        <th title="Баҳои ҳарфӣ">Баҳо</th>
                        <th title="Grade Point">GP</th>
                        <th>Ҳолат</th>
                        <th>Тасдиқ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                    @php
                    $grade = $semesterGrades[$student->id] ?? null;
                    $calc = $calculatedGrades[$student->id] ?? ['rating1' => 0, 'rating2' => 0, 'exam' => 0, 'total_score' => null, 'letter_grade' => null, 'grade_point' => null, 'status' => null];
                    @endphp
                    <tr class="{{ $grade && $grade->is_finalized ? 'table-light' : '' }}">
                        <td>{{ $index + 1 }}</td>
                        <td class="student-name text-start">
                            <a href="{{ route('admin.students.show', $student) }}">
                                {{ $student->user?->short_name }}
                            </a>
                        </td>
                        <td>
                            <span title="Захирашуда: {{ $grade?->rating1_score !== null ? number_format($grade->rating1_score, 0) : '—' }}">
                                {{ $calc['rating1'] !== null ? number_format($calc['rating1'], 0) : '—' }}
                            </span>
                        </td>
                        <td>
                            <span title="Захирашуда: {{ $grade?->rating2_score !== null ? number_format($grade->rating2_score, 0) : '—' }}">
                                {{ $calc['rating2'] !== null ? number_format($calc['rating2'], 0) : '—' }}
                            </span>
                        </td>
                        <td>{{ $grade?->independent_work_score !== null ? number_format($grade->independent_work_score, 0) : '—' }}</td>
                        <td>{{ $calc['exam'] !== null ? number_format($calc['exam'], 0) : '—' }}</td>
                        <td>{{ $grade?->retake_score !== null ? number_format($grade->retake_score, 0) : '—' }}</td>
                        <td>{{ $grade?->retake2_score !== null ? number_format($grade->retake2_score, 0) : '—' }}</td>
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
                        <td>
                            @if($grade && !$grade->is_finalized && $calc['total_score'] !== null)
                            <form action="{{ route('admin.journal.finalize', $grade) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success"
                                    data-confirm="Тасдиқ кардан? Баъд тағйир додан мушкил аст."
                                    title="Тасдиқ">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                            @elseif($grade?->is_finalized)
                            <i class="bi bi-lock-fill text-muted" title="Тасдиқ шуда: {{ $grade->finalized_at?->format('d.m.Y') }}"></i>
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
                    R1 = Рейтинги 1 (авто) |
                    R2 = Рейтинги 2 (авто) |
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
        <div class="row">
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-success">
                        <tr>
                            <th>Баҳо</th>
                            <th>GPA</th>
                            <th>%</th>
                            <th>Анъанавӣ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-success">A</span></td>
                            <td>4.00</td>
                            <td>95-100</td>
                            <td>Аъло</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-success">A-</span></td>
                            <td>3.67</td>
                            <td>90-94</td>
                            <td>Аъло</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">B+</span></td>
                            <td>3.33</td>
                            <td>85-89</td>
                            <td>Хуб</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">B</span></td>
                            <td>3.00</td>
                            <td>80-84</td>
                            <td>Хуб</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-primary">B-</span></td>
                            <td>2.67</td>
                            <td>75-79</td>
                            <td>Хуб</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning">C+</span></td>
                            <td>2.33</td>
                            <td>70-74</td>
                            <td>Қаноатбахш</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-warning">
                        <tr>
                            <th>Баҳо</th>
                            <th>GPA</th>
                            <th>%</th>
                            <th>Анъанавӣ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge bg-warning">C</span></td>
                            <td>2.00</td>
                            <td>65-69</td>
                            <td>Қаноатбахш</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning">C-</span></td>
                            <td>1.67</td>
                            <td>60-64</td>
                            <td>Қаноатбахш</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning">D+</span></td>
                            <td>1.33</td>
                            <td>55-59</td>
                            <td>Қаноатбахш</td>
                        </tr>
                        <tr>
                            <td><span class="badge bg-warning">D</span></td>
                            <td>1.00</td>
                            <td>50-54</td>
                            <td>Қаноатбахш</td>
                        </tr>
                        <tr class="table-danger">
                            <td><span class="badge bg-danger">Fx</span></td>
                            <td>0</td>
                            <td>45-49</td>
                            <td>Ғайриқ. (такрор.)</td>
                        </tr>
                        <tr class="table-dark">
                            <td><span class="badge bg-dark">F</span></td>
                            <td>0</td>
                            <td>0-44</td>
                            <td>Ғайриқ. (дубора)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection