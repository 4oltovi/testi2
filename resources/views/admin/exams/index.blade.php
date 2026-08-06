@extends('layouts.app')

@section('title', 'Имтиҳонҳо')
@section('page-header', 'Имтиҳон ва тест')
@section('page-description', 'Идоракунии имтиҳонҳо ва тестҳо')

@section('page-actions')
<a href="{{ route('admin.exams.create') }}" class="btn btn-primary">
    <i class="bi bi-plus-lg me-1"></i> Имтиҳони нав
</a>
@endsection

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="semester_id" class="form-select">
                    @foreach($semesters as $sem)
                    <option value="{{ $sem->id }}" {{ request('semester_id') == $sem->id ? 'selected' : '' }}>
                        {{ $sem->name }} — {{ $sem->academicYear?->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">Ҳама</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Лоиҳа</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Анҷом</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-filter"></i></button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Имтиҳон</th>
                    <th>Фан</th>
                    <th>Гурӯҳ</th>
                    <th>Навъ</th>
                    <th>Формат</th>
                    <th>Вақт</th>
                    <th>Саволҳо</th>
                    <th>Ҳолат</th>
                    <th class="text-end">Амалҳо</th>
                </tr>
            </thead>
            <tbody>
                @forelse($exams as $exam)
                <tr>
                    <td class="fw-semibold">{{ $exam->title }}</td>
                    <td><small>{{ $exam->subjectAssignment?->curriculum?->subject?->name }}</small></td>
                    <td><span class="badge bg-info">{{ $exam->group?->name }}</span></td>
                    <td><small>{{ $exam->exam_type?->label() ?? $exam->exam_type }}</small></td>
                    <td>
                        @php
                        $formatLabel = match($exam->format) {
                        'online_test' => 'Онлайн',
                        'written' => 'Хаттӣ',
                        'oral' => 'Даҳонӣ',
                        'mixed' => 'Омехта',
                        default => $exam->format,
                        };
                        @endphp
                        <small>{{ $formatLabel }}</small>
                    </td>
                    <td>{{ $exam->duration_minutes }} дақ.</td>
                    <td>{{ $exam->total_questions_count }}</td>
                    <td>
                        @php
                        $sBadge = match($exam->status) {
                        'draft' => 'bg-secondary',
                        'active' => 'bg-success',
                        'scheduled' => 'bg-info',
                        'completed' => 'bg-primary',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                        };
                        $sLabel = match($exam->status) {
                        'draft' => 'Лоиҳа',
                        'active' => 'Фаъол',
                        'scheduled' => 'Банд',
                        'completed' => 'Анҷом',
                        'cancelled' => 'Бекор',
                        default => $exam->status,
                        };
                        @endphp
                        <span class="badge {{ $sBadge }}">{{ $sLabel }}</span>
                    </td>
                    <td class="text-end">
                        <a href="{{ route('admin.exams.show', ['exam' => $exam]) }}" class="btn btn-sm btn-outline-info" title="Дидан"><i class="bi bi-eye"></i></a>
                        @if($exam->status === 'draft')
                        <a href="{{ route('admin.exams.edit', ['exam' => $exam]) }}" class="btn btn-sm btn-outline-primary" title="Таҳрир"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('admin.exams.publish', $exam) }}" method="POST" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-success" title="Нашр"><i class="bi bi-send"></i></button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">Имтиҳоне ёфт нашуд.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($exams->hasPages())
    <div class="card-footer bg-white">{{ $exams->links() }}</div>
    @endif
</div>
@endsection