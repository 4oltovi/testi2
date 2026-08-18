@extends('layouts.app')

@section('title', 'Рейтинги ман')
@section('page-header', 'Рейтинги ман')
@section('page-description', 'Рейтингҳои онлайн (R1/R2)')

@section('content')
@if(!$session)
<div class="text-center text-muted py-5">
    <i class="bi bi-clock-history fs-1 d-block mb-2"></i>
    Ҳоло рейтинги фаъол нест. Дар вақти эълоншуда хабар дода мешавад.
</div>
@else
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-1">{{ $session->name }}</h6>
            <small class="text-muted">
                {{ $session->start_at->format('d.m.Y H:i') }} — {{ $session->end_at->format('d.m.Y H:i') }}
                | Вақти тест: {{ $session->duration_minutes }} дақ.
                | Кӯшишҳо: {{ $session->max_attempts }}
            </small>
        </div>
        <span class="badge bg-success fs-6">Кушод</span>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Фан</th>
                        <th class="text-center">Кӯшишҳо</th>
                        <th class="text-center">Беҳтарин натиҷа</th>
                        <th class="text-center">Амал</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($session->subjects as $subject)
                    @php
                    $atts = $attemptsMap->get($subject->id, collect());
                    $finished = $atts->where('status', 'finished');
                    $best = $finished->max('percentage');
                    $open = $atts->firstWhere('status', 'in_progress');
                    $used = $atts->whereIn('status', ['finished', 'in_progress'])->count();
                    @endphp
                    <tr>
                        <td class="fw-semibold">{{ $subject->name }}</td>
                        <td class="text-center">{{ $used }} / {{ $session->max_attempts }}</td>
                        <td class="text-center">
                            @if($best !== null)
                            <span class="badge {{ $best >= 50 ? 'bg-success' : 'bg-danger' }}">{{ $best }}%</span>
                            @else
                            <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($open)
                            <a href="{{ route('student.rating.take', $open) }}" class="btn btn-sm btn-warning">
                                <i class="bi bi-play-fill me-1"></i>Идома додан
                            </a>
                            @elseif($used < $session->max_attempts)
                                <form method="POST" action="{{ route('student.rating.start', [$session, $subject]) }}" class="d-inline"
                                    onsubmit="return confirm('Тест оғоз шавад? Вақт: {{ $session->duration_minutes }} дақиқа.')">
                                    @csrf
                                    <button class="btn btn-sm btn-primary"><i class="bi bi-play-fill me-1"></i>Оғоз кардан</button>
                                </form>
                                @else
                                <span class="badge bg-secondary">Тамом</span>
                                @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endsection