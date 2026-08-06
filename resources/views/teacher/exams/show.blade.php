@extends('layouts.app')

@section('title', $exam->title)
@section('page-header', $exam->title)
@section('page-description')
    {{ $exam->subjectAssignment?->curriculum?->subject?->name }} | {{ $exam->group?->name }}
@endsection

@section('content')
    {{-- Маълумоти тест --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-{{ $exam->status === 'active' ? 'success' : ($exam->status === 'draft' ? 'secondary' : 'info') }} mb-2">
                                {{ ucfirst($exam->status) }}
                            </span>
                            <h5>{{ $exam->title }}</h5>
                            @if($exam->description)
                                <p class="text-muted">{{ $exam->description }}</p>
                            @endif
                        </div>
                        <div class="text-end">
                            @if($exam->status === 'draft')
                                <a href="{{ route('teacher.exams.edit', $exam) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil me-1"></i> Таҳрир
                                </a>
                            @endif
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-4">
                            <small class="text-muted">Вақт:</small><br>
                            <strong>{{ $exam->duration_minutes }} дақиқа</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Саволҳо:</small><br>
                            <strong>{{ $examQuestions->count() }} / {{ $exam->total_questions_count }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Ҳадди гузариш:</small><br>
                            <strong>{{ $exam->passing_score }}%</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Кӯшишҳо:</small><br>
                            <strong>{{ $exam->max_attempts }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Навъ:</small><br>
                            <strong>{{ $exam->exam_type }}</strong>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Формат:</small><br>
                            <strong>{{ $exam->format }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6><i class="bi bi-lightning me-2"></i> Амалҳо</h6>
                    @if($exam->status === 'draft')
                        @if($examQuestions->count() >= $exam->total_questions_count)
                            <form method="POST" action="{{ route('teacher.exams.publish', $exam) }}">
                                @csrf
                                <button type="submit" class="btn btn-success w-100 mb-2">
                                    <i class="bi bi-send me-1"></i> Нашр кардан
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning py-2">
                                <small><i class="bi bi-exclamation-triangle me-1"></i>
                                    Барои нашр {{ $exam->total_questions_count - $examQuestions->count() }} савол лозим аст.
                                </small>
                            </div>
                        @endif
                    @endif

                    <a href="{{ route('teacher.exams.results', $exam) }}" class="btn btn-outline-info w-100 mb-2">
                        <i class="bi bi-graph-up me-1"></i> Натиҷаҳо
                    </a>
                    <a href="{{ route('teacher.exams.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Саволҳои тест --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-list-ol me-2"></i> Саволҳои тест ({{ $examQuestions->count() }})</h6>
        </div>
        <div class="card-body p-0">
            @if($examQuestions->isEmpty())
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    Ҳоло савол илова нашудааст
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Савол</th>
                                <th style="width: 80px;">Навъ</th>
                                <th style="width: 80px;">Балл</th>
                                <th style="width: 80px;">Сатҳ</th>
                                @if($exam->status === 'draft')
                                    <th style="width: 80px;">Амал</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examQuestions as $index => $eq)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ \Illuminate\Support\Str::limit($eq->question->question_text, 80) }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $eq->question->type }}</span></td>
                                    <td><strong>{{ $eq->points }}</strong></td>
                                    <td>{{ $eq->question->difficulty_level }}/5</td>
                                    @if($exam->status === 'draft')
                                        <td>
                                            <form method="POST" action="{{ route('teacher.exams.remove-question', [$exam, $eq]) }}" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Нест кардан?')">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="3"><strong>Маҷмӯи баллҳо:</strong></td>
                                <td><strong>{{ $examQuestions->sum('points') }}</strong></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Илова кардани саволҳо --}}
    @if($exam->status === 'draft' && $availableQuestions->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-plus-circle me-2"></i> Илова кардани саволҳо</h6>
            </div>
            <div class="card-body p-0">
                <form method="POST" action="{{ route('teacher.exams.add-questions', $exam) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="selectAll"></th>
                                    <th>Савол</th>
                                    <th style="width: 80px;">Навъ</th>
                                    <th style="width: 80px;">Балл</th>
                                    <th style="width: 80px;">Сатҳ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($availableQuestions->take(50) as $q)
                                    <tr>
                                        <td><input type="checkbox" name="question_ids[]" value="{{ $q->id }}"></td>
                                        <td>{{ \Illuminate\Support\Str::limit($q->question_text, 80) }}</td>
                                        <td><span class="badge bg-light text-dark">{{ $q->type }}</span></td>
                                        <td>{{ $q->points }}</td>
                                        <td>{{ $q->difficulty_level }}/5</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white text-end">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Илова кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="question_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>
@endpush
