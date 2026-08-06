@extends('layouts.app')

@section('title', 'Қарздории академӣ')
@section('page-header', 'Қарздории академӣ')
@section('page-description', $debt->student->user->name ?? 'Донишҷӯ')

@section('content')
<div class="row">
    {{-- Left column: Info and History --}}
    <div class="col-lg-8">
        {{-- Info card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Маълумоти қарздорӣ</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Донишҷӯ</th>
                            <td>{{ $debt->student->user->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Гурӯҳ</th>
                            <td>{{ $debt->student->group->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Фан</th>
                            <td>{{ $debt->subject->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Семестр</th>
                            <td>{{ $debt->semester->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Сабаб</th>
                            <td>{{ $debt->reason_label }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Баҳои аслӣ</th>
                            <td>
                                @if($debt->semesterGrade)
                                    <span class="badge bg-danger">{{ $debt->semesterGrade->total_score }}%</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Санаи қарз</th>
                            <td>{{ $debt->created_at ? $debt->created_at->format('d.m.Y') : '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кӯшиш</th>
                            <td>{{ $debt->attempts_used ?? 0 }} / {{ $debt->max_attempts ?? 3 }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Мӯҳлат</th>
                            <td>
                                @if($debt->retake_deadline)
                                    {{ \Carbon\Carbon::parse($debt->retake_deadline)->format('d.m.Y') }}
                                @else
                                    <span class="text-muted">Муайян нашудааст</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳолат</th>
                            <td>
                                @php
                                    $statusColors = [
                                        'active' => 'warning',
                                        'resolved' => 'success',
                                        'escalated' => 'danger',
                                        'expired' => 'dark',
                                    ];
                                    $color = $statusColors[$debt->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $debt->status_label ?? $debt->status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- History card --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-clock-history"></i> Таърихи тағйирот</h5>
            </div>
            <div class="card-body">
                @if($debt->history && $debt->history->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Сана</th>
                                    <th>Амал</th>
                                    <th>Аз ҳолат</th>
                                    <th>Ба ҳолат</th>
                                    <th>Тавзеҳ</th>
                                    <th>Иҷрокунанда</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($debt->history as $record)
                                    <tr>
                                        <td><small>{{ $record->created_at->format('d.m.Y H:i') }}</small></td>
                                        <td>{{ $record->action }}</td>
                                        <td><span class="badge bg-secondary">{{ $record->from_status ?? '—' }}</span></td>
                                        <td><span class="badge bg-primary">{{ $record->to_status ?? '—' }}</span></td>
                                        <td><small>{{ $record->comment ?? '—' }}</small></td>
                                        <td>{{ $record->performedBy->name ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <p class="mb-0">Таърих вуҷуд надорад</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right column: Actions --}}
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-gear"></i> Амалҳо</h5>
            </div>
            <div class="card-body">
                @if($debt->canRetake())
                    <form action="{{ route('admin.debts.schedule-retake', $debt) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-arrow-repeat"></i> Таъини такрорсупорӣ
                        </button>
                    </form>
                @endif

                <form action="{{ route('admin.debts.resolve', $debt) }}" method="POST" class="mb-3">
                    @csrf
                    <div class="mb-3">
                        <label for="score" class="form-label">Баҳо (0-100)</label>
                        <input type="number" name="score" id="score" class="form-control" min="0" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="note" class="form-label">Тавзеҳ</label>
                        <textarea name="note" id="note" class="form-control" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Ҳал кардан
                    </button>
                </form>

                <form action="{{ route('admin.debts.escalate', $debt) }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="bi bi-exclamation-triangle"></i> Ба комиссия
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="{{ url('/admin/debts') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Бозгашт
    </a>
</div>
@endsection
