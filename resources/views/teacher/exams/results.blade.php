@extends('layouts.app')

@section('title', 'Натиҷаҳои тест')
@section('page-header', 'Натиҷаҳо: ' . $exam->title)
@section('page-description')
    {{ $exam->subjectAssignment?->subject?->name }} | {{ $exam->group?->name }}
@endsection

@section('content')
    {{-- Омор --}}
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="text-primary">{{ $statistics['total_students'] }}</h3>
                    <small class="text-muted">Донишҷӯён</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="text-success">{{ $statistics['passed'] }}</h3>
                    <small class="text-muted">Гузаштанд</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="text-danger">{{ $statistics['failed'] }}</h3>
                    <small class="text-muted">Нагузаштанд</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body">
                    <h3 class="text-info">{{ number_format($statistics['average_score'] ?? 0, 1) }}%</h3>
                    <small class="text-muted">Миёна</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Ҷадвали натиҷаҳо --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-table me-2"></i> Натиҷаҳо</h6>
            <a href="{{ route('teacher.exams.show', $exam) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Балл</th>
                            <th>Фоиз</th>
                            <th>Баҳо</th>
                            <th>Ҳолат</th>
                            <th>Вақти супориш</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attempts as $index => $attempt)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $attempt->student?->user?->full_name }}</td>
                                <td>{{ $attempt->total_score ?? '—' }} / {{ $attempt->max_possible_score ?? '—' }}</td>
                                <td>
                                    @if($attempt->percentage !== null)
                                        <span class="{{ $attempt->percentage >= $exam->passing_score ? 'text-success' : 'text-danger' }}">
                                            <strong>{{ number_format($attempt->percentage, 1) }}%</strong>
                                        </span>
                                    @else — @endif
                                </td>
                                <td>
                                    @if($attempt->letter_grade)
                                        <span class="badge bg-{{ $attempt->percentage >= 50 ? 'success' : 'danger' }}">
                                            {{ $attempt->letter_grade }}
                                        </span>
                                    @else — @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $attempt->status === 'graded' ? 'success' : ($attempt->status === 'in_progress' ? 'warning' : 'info') }}">
                                        {{ $attempt->status }}
                                    </span>
                                </td>
                                <td>{{ $attempt->submitted_at?->format('d.m.Y H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    Ҳоло натиҷае нест
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
