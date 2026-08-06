@extends('layouts.app')

@section('title', $exam->title)
@section('page-header', $exam->title)
@section('page-description')
    {{ $exam->subjectAssignment->subject->name ?? '' }} | {{ $exam->subjectAssignment->group->name ?? '' }}
@endsection

@section('content')
<div class="row">
    {{-- Left column: Info --}}
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle"></i> Маълумоти имтиҳон</h5>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Навъ</th>
                            <td>
                                @php
                                    $typeLabels = [
                                        'main' => 'Имтиҳони асосӣ',
                                        'retake' => 'Такрорсупорӣ',
                                        'retake_commission' => 'Комиссионӣ',
                                        'rating1' => 'Рейтинги 1',
                                        'rating2' => 'Рейтинги 2',
                                        'quiz' => 'Тести кӯтоҳ',
                                    ];
                                @endphp
                                {{ $typeLabels[$exam->exam_type] ?? $exam->exam_type }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Формат</th>
                            <td>
                                @php
                                    $formatLabels = [
                                        'online_test' => 'Тести онлайн',
                                        'written' => 'Хаттӣ',
                                        'oral' => 'Даҳонӣ',
                                        'mixed' => 'Омехта',
                                    ];
                                @endphp
                                {{ $formatLabels[$exam->format] ?? $exam->format }}
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Вақт (дақиқа)</th>
                            <td>{{ $exam->duration_minutes }} дақиқа</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Саволҳо</th>
                            <td>{{ $exam->questions_count ?? $exam->questions->count() ?? 0 }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳадди гузариш</th>
                            <td>{{ $exam->passing_score }}%</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кӯшишҳо</th>
                            <td>{{ $exam->max_attempts }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳолат</th>
                            <td>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'published' => 'success',
                                        'active' => 'primary',
                                        'completed' => 'info',
                                        'archived' => 'dark',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Пешнавис',
                                        'published' => 'Нашршуда',
                                        'active' => 'Фаъол',
                                        'completed' => 'Анҷомёфта',
                                        'archived' => 'Архив',
                                    ];
                                    $color = $statusColors[$exam->status] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">{{ $statusLabels[$exam->status] ?? $exam->status }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Санаи оғоз</th>
                            <td>
                                @if($exam->starts_at)
                                    {{ \Carbon\Carbon::parse($exam->starts_at)->format('d.m.Y H:i') }}
                                @else
                                    <span class="text-muted">Муайян нашудааст</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Санаи анҷом</th>
                            <td>
                                @if($exam->ends_at)
                                    {{ \Carbon\Carbon::parse($exam->ends_at)->format('d.m.Y H:i') }}
                                @else
                                    <span class="text-muted">Муайян нашудааст</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Омехтакунии саволҳо</th>
                            <td>
                                @if($exam->shuffle_questions)
                                    <i class="bi bi-check-circle text-success"></i> Ҳа
                                @else
                                    <i class="bi bi-x-circle text-danger"></i> Не
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Омехтакунии ҷавобҳо</th>
                            <td>
                                @if($exam->shuffle_answers)
                                    <i class="bi bi-check-circle text-success"></i> Ҳа
                                @else
                                    <i class="bi bi-x-circle text-danger"></i> Не
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Right column: Stats and Actions --}}
    <div class="col-lg-4">
        {{-- Statistics --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-graph-up"></i> Омор</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Кӯшишҳо</span>
                    <span class="fw-bold">{{ $exam->attempts_count ?? $exam->attempts->count() ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Гузаштагон</span>
                    <span class="fw-bold text-success">{{ $exam->passed_count ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">Балли миёна</span>
                    <span class="fw-bold">{{ $exam->average_score ? number_format($exam->average_score, 1) . '%' : '—' }}</span>
                </div>
            </div>
        </div>

        {{-- Publish button --}}
        @if($exam->status === 'draft')
            <div class="card">
                <div class="card-body text-center">
                    <p class="text-muted mb-3">Имтиҳон дар ҳолати пешнавис аст</p>
                    <form action="{{ route('admin.exams.publish', $exam) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-megaphone"></i> Нашр кардан
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="mt-3">
    <a href="{{ url('/admin/exams') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Бозгашт
    </a>
</div>
@endsection
