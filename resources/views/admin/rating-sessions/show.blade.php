@extends('layouts.app')

@section('title', $session->name)
@section('page-header', $session->name)
@section('page-description',
($session->period === 'rating1' ? 'Рейтинги 1' : 'Рейтинги 2') .
' | ' . ($session->semester?->name ?? '-') . ' — ' . ($session->semester?->academicYear?->name ?? ''))

@section('content')
{{-- ==================== МАЪЛУМОТ + АМАЛҲО ==================== --}}
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col">
                        <h5 class="mb-0">{{ $session->start_at?->format('d.m.Y H:i') ?? '—' }}</h5>
                        <small class="text-muted">Оғоз</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->end_at?->format('d.m.Y H:i') ?? '—' }}</h5>
                        <small class="text-muted">Анҷом</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->duration_minutes }}</h5>
                        <small class="text-muted">Дақиқа</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->questions_count }}</h5>
                        <small class="text-muted">Саволҳо</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->max_attempts }}</h5>
                        <small class="text-muted">Кӯшишҳо</small>
                    </div>
                    <div class="col">
                        <h5 class="mb-0">{{ $session->schedule_mode === 'by_group' ? 'Аз рӯи гурӯҳ' : 'Умумӣ' }}</h5>
                        <small class="text-muted">Режим</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-grid gap-2">
                @if($session->status === 'draft')
                <form method="POST" action="{{ route('admin.rating-sessions.publish', $session->id) }}">
                    @csrf
                    <button class="btn btn-success w-100" {{ $readiness['missing'] > 0 ? 'disabled' : '' }}>
                        <i class="bi bi-broadcast me-1"></i> Нашр кардан
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.rating-sessions.destroy', $session->id) }}"
                    onsubmit="return confirm('Сессия нест шавад?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger w-100"><i class="bi bi-trash me-1"></i> Нест кардан</button>
                </form>
                @else
                <a href="{{ route('admin.rating-sessions.protocol', $session->id) }}" class="btn btn-outline-dark w-100">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Протоколи PDF
                </a>
                <form method="POST" action="{{ route('admin.rating-sessions.extend', $session->id) }}" class="d-flex gap-2">
                    @csrf
                    <input type="number" name="hours" class="form-control" value="24" min="1" max="168">
                    <button class="btn btn-outline-warning text-nowrap"><i class="bi bi-clock-history me-1"></i>Дароз кардан</button>
                </form>
                <form method="POST" action="{{ route('admin.rating-sessions.close', $session->id) }}"
                    onsubmit="return confirm('Рейтинг пӯшида шавад?')">
                    @csrf
                    <button class="btn btn-danger w-100"><i class="bi bi-lock me-1"></i> Пӯшидан</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ==================== САНҶИШИ ОМАДАГӢ ==================== --}}
<div class="card border-0 shadow-sm mb-4 {{ $readiness['missing'] > 0 ? 'border-danger border-2' : 'border-success border-2' }}">
    <div class="card-body">
        <h6 class="mb-2">
            <i class="bi bi-clipboard-check me-2"></i>Санҷиши омодагии саволҳо:
            <span class="badge {{ $readiness['missing'] > 0 ? 'bg-danger' : 'bg-success' }}">
                {{ $readiness['ready'] }} / {{ $readiness['total'] }} фан тайёр
            </span>
        </h6>
        @if($readiness['missing'] > 0)
        <small class="text-danger">
            Саволҳо кам аст (ҳадди ақал {{ $session->questions_count }} лозим) барои:
            @foreach($session->subjects->whereIn('id', $readiness['missing_ids']) as $m)
            <span class="badge bg-danger-subtle text-danger">{{ $m->name }}</span>
            @endforeach
            — ба <a href="{{ route('admin.rating-questions.index') }}">Саволномаҳои рейтинг</a> гузаред.
        </small>
        @else
        <small class="text-success">Ҳамаи фанҳо саволҳои кофӣ доранд — нашр имкон аст ✅</small>
        @endif
    </div>
</div>

{{-- ==================== НАТИҶАҲО ==================== --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i>Натиҷаҳо (автоматӣ)</h6>
        <small class="text-muted">Журнал: {{ $results['journal_max'] }} бал | Тест: {{ $results['test_max'] }} бал</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Фан</th>
                        <th class="text-center">Кӯшиш</th>
                        <th class="text-center">Тест (%)</th>
                        <th class="text-center">Балл (аз {{ $results['test_max'] }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($results['rows'] as $row)
                    <tr>
                        <td>{{ $row['student'] }}</td>
                        <td><span class="badge bg-info">{{ $row['group'] }}</span></td>
                        <td>{{ $row['subject'] }}</td>
                        <td class="text-center">{{ $row['attempts'] ?? '—' }}</td>
                        <td class="text-center">
                            @if($row['pct'] !== null)
                            <span class="badge {{ $row['pct'] >= 50 ? 'bg-success' : 'bg-danger' }}">{{ $row['pct'] }}%</span>
                            @else
                            <span class="text-muted">ҳанӯз нест</span>
                            @endif
                        </td>
                        <td class="text-center fw-bold">{{ $row['scaled'] ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Натиҷа нест.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection