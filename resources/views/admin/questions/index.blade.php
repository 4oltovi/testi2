@extends('layouts.app')
@section('title', 'Саволномаҳо')
@section('page-header', 'Саволномаҳо')
@section('page-description', 'Фанро интихоб кунед ва саволҳоро ворид кунед')

@section('page-actions')
    <a href="{{ route('admin.exams.questions.import-form') }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-upload me-1"></i> Импорт
    </a>
@endsection

@section('content')
    {{-- Агар фан интихоб нашуда — рӯйхати фанҳо --}}
    @if(!request('subject_id'))
        <div class="row g-3">
            @foreach($subjects as $subject)
                @php
                    $count = \App\Models\Question::where('subject_id', $subject->id)->count();
                @endphp
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="{{ route('admin.exams.questions.index', ['subject_id' => $subject->id]) }}" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 hover-card">
                            <div class="card-body text-center py-4">
                                <div class="mb-2">
                                    <i class="bi bi-book fs-2 text-primary"></i>
                                </div>
                                <h6 class="mb-1 text-dark">{{ $subject->name }}</h6>
                                <span class="badge bg-{{ $count > 0 ? 'success' : 'secondary' }}">
                                    {{ $count }} савол
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        @if($subjects->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="bi bi-book fs-1 d-block mb-2"></i>
                <p>Фанҳо ёфт нашуд. Аввал фанҳоро илова кунед.</p>
            </div>
        @endif

    @else
        {{-- Саволҳои як фани мушаххас --}}
        @php $currentSubject = $subjects->firstWhere('id', request('subject_id')); @endphp

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <a href="{{ route('admin.exams.questions.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Ҳама фанҳо
                </a>
                <span class="badge bg-primary ms-2 fs-6">
                    <i class="bi bi-book me-1"></i> {{ $currentSubject?->name }}
                </span>
            </div>
            <div>
                <a href="{{ route('admin.exams.questions.create', ['subject_id' => request('subject_id')]) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> Савол илова
                </a>
            </div>
        </div>

        {{-- Филтр бо навъ --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="subject_id" value="{{ request('subject_id') }}">
                    <select name="type" class="form-select form-select-sm" style="width: auto;">
                        <option value="">Ҳама навъ</option>
                        <option value="single_choice" {{ request('type') == 'single_choice' ? 'selected' : '' }}>Якҷавобӣ</option>
                        <option value="multiple_choice" {{ request('type') == 'multiple_choice' ? 'selected' : '' }}>Чандҷавобӣ</option>
                        <option value="true_false" {{ request('type') == 'true_false' ? 'selected' : '' }}>Дуруст/Нодуруст</option>
                        <option value="matching" {{ request('type') == 'matching' ? 'selected' : '' }}>Мувофиқоварӣ</option>
                    </select>
                    <button type="submit" class="btn btn-outline-primary btn-sm"><i class="bi bi-filter"></i></button>
                </form>
            </div>
        </div>

        {{-- Ҷадвали саволҳо --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Савол</th>
                                <th class="d-none d-md-table-cell">Навъ</th>
                                <th class="d-none d-md-table-cell">Сатҳ</th>
                                <th class="d-none d-sm-table-cell">Вариант</th>
                                <th>Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($questions as $index => $q)
                                <tr>
                                    <td>{{ $questions->firstItem() + $index }}</td>
                                    <td>
                                        <span class="question-text">{!! \Illuminate\Support\Str::limit($q->question_text, 80) !!}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell">
                                        @php
                                            $typeLabel = match($q->type) {
                                                'single_choice' => 'Якҷавобӣ',
                                                'multiple_choice' => 'Чандҷавобӣ',
                                                'true_false' => 'Д/Н',
                                                'matching' => 'Мувофиқ',
                                                default => $q->type,
                                            };
                                        @endphp
                                        <span class="badge bg-light text-dark">{{ $typeLabel }}</span>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $q->difficulty_level }}/5</td>
                                    <td class="d-none d-sm-table-cell">{{ $q->answerOptions->count() }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.exams.questions.edit', $q) }}" class="btn btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                            <form method="POST" action="{{ route('admin.exams.questions.destroy', $q) }}" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('Нест?')"><i class="bi bi-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        Савол нест.
                                        <a href="{{ route('admin.exams.questions.create', ['subject_id' => request('subject_id')]) }}">Илова кунед</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($questions->hasPages())
                <div class="card-footer bg-white">{{ $questions->appends(request()->query())->links() }}</div>
            @endif
        </div>
    @endif
@endsection

@push('styles')
<style>
.hover-card { transition: transform 0.2s, box-shadow 0.2s; }
.hover-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>
@endpush
