@extends('layouts.app')

@section('title', 'Баҳоҳои семестрӣ')

@section('page-header', 'Баҳоҳои семестрӣ')
@section('page-description')
<div class="d-flex align-items-center gap-2 text-muted">
    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
        <i class="bi bi-book me-1"></i> {{ $subjectAssignment->subject?->name }}
    </span>
    <span class="text-muted">|</span>
    <span><i class="bi bi-people me-1"></i> {{ $subjectAssignment->group?->name }}</span>
    <span class="text-muted">|</span>
    <span><i class="bi bi-calendar3 me-1"></i> {{ $semester->name }}</span>
    <span class="text-muted">|</span>
    <span class="fw-semibold text-dark">{{ $subject->credits }} кредит</span>
</div>
@endsection

@section('content')

{{-- Иловагиҳои CSS барои зебогии бештар --}}
<style>
    .stat-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }

    .student-avatar {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 600;
        font-size: 0.85rem;
        color: #fff;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .grade-badge {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .journal-table thead th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 2px solid #e9ecef;
        background-color: #f8f9fa;
    }

    .journal-table tbody tr {
        transition: background-color 0.15s ease;
    }

    .journal-table tbody tr:hover {
        background-color: #f1f5f9;
    }

    .formula-box {
        background: linear-gradient(135deg, #e0f2fe 0%, #f0f9ff 100%);
        border-left: 4px solid #0ea5e9;
    }
</style>

{{-- Маълумоти фан ва статистика --}}
<div class="row g-4 mb-4">
    <!-- Карточкаи Кредит -->
    <div class="col-md-4 col-lg-3">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="flex-shrink-0 bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                    <i class="bi bi-patch-check fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">{{ $subjectAssignment->credits }}</h3>
                    <small class="text-muted fw-medium">Кредитҳои фаннӣ</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Карточкаи Донишҷӯён -->
    <div class="col-md-4 col-lg-3">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="flex-shrink-0 bg-success bg-opacity-10 text-success rounded-3 p-3">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">{{ $students->count() }}</h3>
                    <small class="text-muted fw-medium">Донишҷӯён дар гурӯҳ</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Карточкаи Формула -->
    <div class="col-md-12 col-lg-6">
        <div class="card stat-card border-0 shadow-sm h-100 rounded-4 formula-box">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="flex-shrink-0 bg-white text-info rounded-3 p-2 shadow-sm">
                        <i class="bi bi-calculator fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-2">Формулаи ҳисобкунии баҳои ниҳоӣ</h6>
                        <div class="bg-white bg-opacity-75 rounded-3 p-3 mb-2 text-center shadow-sm">
                            <span class="fs-5 fw-bold text-primary">(R1 + R2) ÷ 4 + (Имтиҳон × 0.5)</span>
                        </div>
                        <ul class="list-unstyled mb-0 small text-muted">
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Рейтингҳо аз журнали электронӣ автоматӣ ҳисоб карда мешаванд.</li>
                            <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Имтиҳон аз тести онлайн автоматӣ гирифта мешавад.</li>
                            <li><i class="bi bi-check-circle-fill text-success me-1"></i> Баҳои ниҳоӣ ва тасдиқ ҳангоми супориши имтиҳон автоматӣ анҷом меёбад.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Ведомости ниҳоӣ --}}
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-table me-2 text-primary"></i> Рейтингҳои семестрӣ (R1 / R2)
        </h6>
        <span class="badge bg-light text-dark border">
            <i class="bi bi-info-circle me-1"></i> Маълумотҳои автоматӣ
        </span>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover journal-table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center py-3" style="width: 60px;">№</th>
                        <th class="text-center py-3" style="width: 100px;">ID</th>
                        <th class="py-3">Донишҷӯ</th>
                        <th class="text-center py-3" style="width: 100px;" title="Рейтинги 1 (ҳафтаи 1-8)">R1</th>
                        <th class="text-center py-3" style="width: 100px;" title="Рейтинги 2 (ҳафтаи 9-16)">R2</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                    @php
                    $calc = $calculatedGrades[$student->id] ?? ['rating1' => 0, 'rating2' => 0];
                    $r1 = $calc['rating1'] !== null ? number_format($calc['rating1'], 0) : '—';
                    $r2 = $calc['rating2'] !== null ? number_format($calc['rating2'], 0) : '—';

                    // Ранги баҳоҳо барои хонотар шудан
                    $r1Class = is_numeric($r1) ? (intval($r1) >= 80 ? 'text-success' : (intval($r1) >= 50 ? 'text-warning' : 'text-danger')) : 'text-muted';
                    $r2Class = is_numeric($r2) ? (intval($r2) >= 80 ? 'text-success' : (intval($r2) >= 50 ? 'text-warning' : 'text-danger')) : 'text-muted';

                    // Ҳосил кардани ибтидоии ном барои аватар
                    $fullName = $student->user?->full_name ?? 'Номаълум';
                    $initials = collect(explode(' ', $fullName))->map(fn($n) => mb_substr($n, 0, 1))->take(2)->implode('');
                    @endphp
                    <tr>
                        <td class="text-center text-muted fw-medium">{{ $index + 1 }}</td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-normal">{{ $student->student_id_number ?? '—' }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="student-avatar shadow-sm">
                                    {{ $initials ?: '??' }}
                                </div>
                                <div>
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-decoration-none fw-semibold text-dark hover-text-primary">
                                        {{ $fullName }}
                                    </a>
                                    <div class="small text-muted">Гурӯҳ: {{ $subjectAssignment->group?->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="grade-badge {{ $r1Class }}" title="Ҳисобшуда: {{ $calc['rating1'] !== null ? number_format($calc['rating1'], 1) : '0' }}">
                                {{ $r1 }}
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="grade-badge {{ $r2Class }}" title="Ҳисобшуда: {{ $calc['rating2'] !== null ? number_format($calc['rating2'], 1) : '0' }}">
                                {{ $r2 }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="card-footer bg-light border-top py-3">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Тавзеҳот:</strong> R1 = Рейтинги 1 (автоматӣ) | R2 = Рейтинги 2 (автоматӣ)
                </small>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="bi bi-arrow-left me-2"></i>Бозгашт ба журнал
                </a>
            </div>
        </div>
    </div>
</div>

@endsection