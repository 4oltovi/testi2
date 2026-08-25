@extends('layouts.app')

@section('title', 'Пешакӣ: импорти саволҳои рейтинг')
@section('page-header', 'Пешакӣ: импорти саволҳои рейтинг')
@section('page-description', 'Санҷидани маълумот пеш аз сабт')

@section('content')
<div class="row">
    <div class="col-12">
        {{-- Омор --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h4 class="text-primary mb-0">{{ $totalRows }}</h4>
                        <small class="text-muted">Сатрҳои умумӣ</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h4 class="text-success mb-0">{{ $validCount }}</h4>
                        <small class="text-muted">Саволҳои дуруст</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h4 class="text-danger mb-0">{{ $invalidCount }}</h4>
                        <small class="text-muted">Саволҳои хато</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h4 class="text-info mb-0">{{ $subject->name }}</h4>
                        <small class="text-muted">Фан</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Хатогиҳо ва огоҳиҳо --}}
        @if($importErrors->isNotEmpty() || $warnings->isNotEmpty())
        <div class="alert alert-warning">
            <h6><i class="bi bi-exclamation-triangle me-1"></i> Хатогиҳо ва огоҳиҳо</h6>
            <ul class="mb-0">
                @foreach($importErrors as $error)
                    <li>{{ $error }}</li>
                @endforeach
                @foreach($warnings as $warning)
                    <li>{{ $warning }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        {{-- Саволҳо --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Саволҳо ({{ $questions->count() }})</h6>
                <div class="text-muted small">
                    <span class="text-success">● Дуруст: {{ $validCount }}</span>
                    <span class="text-danger ms-2">● Хато: {{ $invalidCount }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Савол</th>
                                <th>Вариантҳо</th>
                                <th>Дуруст</th>
                                <th>Душворӣ</th>
                                <th>Ҳолат</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($questions as $index => $q)
                            <tr class="{{ !empty($q['errors']) ? 'table-danger' : '' }}">
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $q['question_text'] }}</td>
                                <td>
                                    @foreach($q['options'] as $i => $opt)
                                        <span class="badge {{ $i === $q['correct_index'] ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $i + 1 }}. {{ $opt }}
                                        </span>
                                    @endforeach
                                </td>
                                <td>{{ $q['correct_index'] + 1 }}</td>
                                <td>{{ $q['difficulty_level'] ?? 1 }}</td>
                                <td>
                                    @if(!empty($q['errors']))
                                        <span class="badge bg-danger">Хато</span>
                                        @foreach($q['errors'] as $err)
                                            <div class="small text-danger">{{ $err }}</div>
                                        @endforeach
                                    @else
                                        <span class="badge bg-success">OK</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Амалҳо --}}
        <div class="mt-3 d-flex justify-content-between">
            <a href="{{ route('admin.rating-questions.excel-import') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>
            <form method="POST" action="{{ route('admin.rating-questions.excel-import-confirm') }}" class="d-inline">
                @csrf
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-success" {{ $invalidCount > 0 ? '' : '' }}>
                    <i class="bi bi-check-lg me-1"></i> Тасдиқ ва сабт кардан ({{ $validCount }} савол)
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
