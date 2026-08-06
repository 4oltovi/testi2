@extends('layouts.app')

@section('title', $student->user?->full_name)
@section('page-header', $student->user?->full_name)
@section('page-description')
    {{ $student->student_id_number }} | {{ $student->group?->name }} | {{ $student->course?->name }}
@endsection

@section('page-actions')
    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline-primary me-1">
        <i class="bi bi-pencil me-1"></i> Таҳрир
    </a>
@endsection

@section('content')
<div class="row g-4">
    {{-- Колонкаи чап: Маълумоти шахсӣ --}}
    <div class="col-lg-4">
        {{-- Профил --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body text-center">
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center mb-3"
                     style="width: 80px; height: 80px; font-size: 2rem; font-weight: 600;">
                    {{ mb_substr($student->user?->first_name, 0, 1) }}{{ mb_substr($student->user?->last_name, 0, 1) }}
                </div>
                <h5 class="mb-1">{{ $student->user?->full_name }}</h5>
                <p class="text-muted mb-2">{{ $student->student_id_number }}</p>
                <span class="badge {{ $student->status->badgeClass() }} fs-6">{{ $student->status->label() }}</span>
                @if($student->has_debts)
                    <span class="badge bg-danger fs-6 ms-1">Қарздор</span>
                @endif
            </div>
        </div>

        {{-- GPA --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-trophy me-2"></i> GPA ва кредитҳо</h6>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">GPA кумулятивӣ:</span>
                    <strong class="fs-4 {{ $student->cumulative_gpa >= 3.0 ? 'text-success' : ($student->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                        {{ number_format($student->cumulative_gpa, 2) }}
                    </strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Кредитҳои гирифташуда:</span>
                    <strong>{{ $student->total_credits_earned }} / {{ $student->specialty?->total_credits ?? '—' }}</strong>
                </div>
                @if($student->specialty?->total_credits)
                    @php $progress = min(100, round(($student->total_credits_earned / $student->specialty->total_credits) * 100)); @endphp
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $progress }}%"></div>
                    </div>
                    <small class="text-muted">{{ $progress }}% анҷомёфта</small>
                @endif
            </div>
        </div>

        {{-- Маълумоти шахсӣ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-person me-2"></i> Шахсӣ</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Таваллуд:</td><td>{{ $student->birth_date?->format('d.m.Y') ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Ҷинс:</td><td>{{ $student->gender === 'male' ? 'Мард' : ($student->gender === 'female' ? 'Зан' : '—') }}</td></tr>
                    <tr><td class="text-muted">Миллат:</td><td>{{ $student->nationality ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Шаҳрвандӣ:</td><td>{{ $student->citizenship ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Паспорт:</td><td>{{ $student->passport_series }} {{ $student->passport_number }}</td></tr>
                    <tr><td class="text-muted">Телефон:</td><td>{{ $student->user?->phone ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Email:</td><td>{{ $student->user?->email ?? '—' }}</td></tr>
                    <tr><td class="text-muted">Волидон:</td><td>{{ $student->parent_name ?? '—' }}<br><small>{{ $student->parent_phone ?? '' }}</small></td></tr>
                </table>
            </div>
        </div>

        {{-- Маълумоти таълимӣ --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i> Таълим</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">Ихтисос:</td><td>{{ $student->specialty?->name }}</td></tr>
                    <tr><td class="text-muted">Факултет:</td><td>{{ $student->specialty?->department?->faculty?->name }}</td></tr>
                    <tr><td class="text-muted">Гурӯҳ:</td><td><strong>{{ $student->group?->name }}</strong></td></tr>
                    <tr><td class="text-muted">Курс:</td><td>{{ $student->course?->name }}</td></tr>
                    <tr><td class="text-muted">Шакл:</td><td>{{ $student->education_form === 'budget' ? 'Буҷетӣ' : 'Шартномавӣ' }}</td></tr>
                    <tr><td class="text-muted">Санаи қабул:</td><td>{{ $student->enrollment_date?->format('d.m.Y') }}</td></tr>
                    <tr><td class="text-muted">Фармон:</td><td>{{ $student->enrollment_order ?? '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Колонкаи рост --}}
    <div class="col-lg-8">
        {{-- Тағйири ҳолат --}}
        @if(auth()->user()->hasPermission('students.status') && $student->isActive())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i> Тағйири ҳолат</h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.students.change-status', $student) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">Ҳолати нав</label>
                        <select name="new_status" class="form-select" required>
                            <option value="">— Интихоб —</option>
                            <option value="academic_leave">Рухсатии академӣ</option>
                            <option value="expelled">Хориҷ кардан</option>
                            <option value="graduated">Хатм</option>
                            <option value="transferred">Гузаронидан</option>
                            <option value="suspended">Боздоштан</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Сабаб</label>
                        <input type="text" name="reason" class="form-control" required placeholder="Сабаби тағйир">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Фармон</label>
                        <input type="text" name="order_number" class="form-control" placeholder="№">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-warning w-100" data-confirm="Оё мутмаин ҳастед?">
                            <i class="bi bi-check"></i> Тағйир
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        {{-- Баҳоҳои семестрӣ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Баҳоҳои семестрӣ ({{ $student->semesterGrades->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Фан</th>
                                <th>Семестр</th>
                                <th>R1</th>
                                <th>R2</th>
                                <th>КМ</th>
                                <th>Имт.</th>
                                <th>Ниҳоӣ</th>
                                <th>Баҳо</th>
                                <th>Кредит</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($student->semesterGrades->sortByDesc('semester_id')->take(15) as $grade)
                                <tr>
                                    <td><small>{{ $grade->curriculum?->subject?->name }}</small></td>
                                    <td><small>{{ $grade->semester?->name }}</small></td>
                                    <td>{{ $grade->rating1_score ?? '—' }}</td>
                                    <td>{{ $grade->rating2_score ?? '—' }}</td>
                                    <td>{{ $grade->independent_work_score ?? '—' }}</td>
                                    <td>{{ $grade->exam_score ?? ($grade->retake_score ? "Т:{$grade->retake_score}" : '—') }}</td>
                                    <td><strong>{{ $grade->total_score ?? '—' }}</strong></td>
                                    <td>
                                        @if($grade->letter_grade)
                                            <span class="badge {{ $grade->isPassed() ? 'bg-success' : 'bg-danger' }}">
                                                {{ $grade->letter_grade }}
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $grade->credits_earned }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="text-center text-muted py-3">Баҳое мавҷуд нест.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Қарздориҳо --}}
        @if($student->activeDebts->isNotEmpty())
        <div class="card border-0 shadow-sm border-danger mb-3">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="mb-0 text-danger"><i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳои фаъол ({{ $student->activeDebts->count() }})</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Фан</th><th>Сабаб</th><th>Баҳо</th><th>Санаи қарз</th><th>Ҳолат</th></tr>
                    </thead>
                    <tbody>
                        @foreach($student->activeDebts as $debt)
                            <tr>
                                <td>{{ $debt->subject?->name }}</td>
                                <td><small>{{ $debt->reason_label }}</small></td>
                                <td><span class="badge bg-danger">{{ $debt->original_grade }}</span> ({{ $debt->original_score }}%)</td>
                                <td>{{ $debt->debt_date?->format('d.m.Y') }}</td>
                                <td><span class="badge {{ $debt->status->badgeClass() }}">{{ $debt->status->label() }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Таърихи ҳолат --}}
        @if($student->statusHistory->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i> Таърихи ҳолат</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Сана</th><th>Аз</th><th>Ба</th><th>Сабаб</th><th>Фармон</th></tr>
                    </thead>
                    <tbody>
                        @foreach($student->statusHistory->sortByDesc('created_at') as $history)
                            <tr>
                                <td><small>{{ $history->created_at->format('d.m.Y') }}</small></td>
                                <td><small>{{ $history->from_status ?? '—' }}</small></td>
                                <td><strong>{{ $history->to_status }}</strong></td>
                                <td><small>{{ $history->reason }}</small></td>
                                <td><small>{{ $history->order_number ?? '—' }}</small></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- GPA семестрӣ --}}
        @if($student->semesterGpas->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-graph-up me-2"></i> GPA семестрӣ</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Семестр</th><th>GPA</th><th>Гирифта</th><th>Кӯшиш</th><th>Гузашт</th><th>Нагуз.</th><th>Кумулятивӣ</th></tr>
                    </thead>
                    <tbody>
                        @foreach($student->semesterGpas->sortByDesc('semester_id') as $gpa)
                            <tr>
                                <td>{{ $gpa->semester?->name }}</td>
                                <td><strong class="{{ $gpa->gpa >= 3.0 ? 'text-success' : ($gpa->gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">{{ number_format($gpa->gpa, 2) }}</strong></td>
                                <td>{{ $gpa->credits_earned }}</td>
                                <td>{{ $gpa->credits_attempted }}</td>
                                <td class="text-success">{{ $gpa->subjects_passed }}</td>
                                <td class="text-danger">{{ $gpa->subjects_failed }}</td>
                                <td><strong>{{ number_format($gpa->cumulative_gpa, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
