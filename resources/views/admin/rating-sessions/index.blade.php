@extends('layouts.app')

@section('title', 'Рейтингҳои онлайн')
@section('page-header', 'Рейтингҳои онлайн')
@section('page-description', 'Сессияҳои рейтингии R1/R2 — тести компютерӣ')

@section('page-actions')
<a href="{{ route('admin.rating-sessions.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Рейтинги нав
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ном</th>
                        <th>Давра</th>
                        <th>Семестр</th>
                        <th>Санаҳо</th>
                        <th class="text-center">Кӯшишҳо</th>
                        <th class="text-center">Ҳолат</th>
                        <th class="text-center">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sessions as $s)
                    @php
                    [$badge, $label] = match (true) {
                    $s->status === 'draft' => ['bg-secondary', 'Нашрнашуда'],
                    $s->status === 'active' && $s->start_at && $s->end_at && now()->between($s->start_at, $s->end_at) => ['bg-success', 'Кушод'],
                    $s->status === 'active' && $s->start_at && now()->lt($s->start_at) => ['bg-info', 'Интизорӣ'],
                    $s->status === 'active' => ['bg-warning', 'Вақт гузашт'],
                    default => ['bg-dark', 'Пӯшида'],
                    };
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $s->name }}</td>
                        <td><span class="badge bg-primary">{{ $s->period === 'rating1' ? 'Рейтинги 1' : 'Рейтинги 2' }}</span></td>
                        <td><small>{{ $s->semester?->name }} — {{ $s->semester?->academicYear?->name }}</small></td>
                        <td><small>{{ $s->start_at?->format('d.m.Y H:i') ?? '—' }} — {{ $s->end_at?->format('d.m.Y H:i') ?? '—' }}</small></td>
                        <td class="text-center">{{ $s->attempts_count }}</td>
                        <td class="text-center"><span class="badge {{ $badge }}">{{ $label }}</span></td>
                        <td class="text-center">
                            <a href="{{ route('admin.rating-sessions.show', $s->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye me-1"></i>Кушодан
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-lightning-charge fs-1 d-block mb-2"></i>
                            Ҳоло сессияи рейтинг нест.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($sessions->hasPages())
    <div class="card-footer bg-white">{{ $sessions->links() }}</div>
    @endif
</div>
@endsection