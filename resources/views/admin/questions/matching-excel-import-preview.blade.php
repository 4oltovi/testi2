@extends('layouts.app')
@section('title', 'Пешакӣ: Импорти саволҳои мувофиқоварӣ')
@section('page-header', 'Пешакӣ: Импорти мувофиқоварӣ')

@section('content')
<div class="row mb-4">
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-primary mb-0">{{ $totalRows }}</h3>
                <small class="text-muted">Умумии сатрҳо</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-info mb-0">{{ $matchingCount }}</h3>
                <small class="text-muted">Саволи мувофиқоварӣ</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-success mb-0">{{ $validCount }}</h3>
                <small class="text-muted">Мувофиқ</small>
            </div>
        </div>
    </div>
    <div class="col-md-2 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-danger mb-0">{{ $invalidCount }}</h3>
                <small class="text-muted">Хатогиҳо</small>
            </div>
        </div>
    </div>
</div>

@if($importErrors->isNotEmpty())
<div class="alert alert-danger">
    <h6 class="fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Хатогиҳои умумӣ</h6>
    <ul class="mb-0">
        @foreach($importErrors as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($warnings->isNotEmpty())
<div class="alert alert-warning">
    <h6 class="fw-bold"><i class="bi bi-exclamation-circle me-2"></i>Огоҳиҳо</h6>
    <ul class="mb-0">
        @foreach($warnings as $warning)
        <li>{{ $warning }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-list-check me-2"></i> Саволҳои мувофиқоварӣ: {{ $subject->name }}</h6>
        <span class="badge bg-{{ $invalidCount > 0 ? 'danger' : 'success' }}">
            {{ $validCount }} / {{ $questions->count() }} савол мувофиқ
        </span>
    </div>
    <div class="card-body">
        @foreach($questions as $index => $q)
        @php
        $hasError = !empty($q['errors']);
        $borderClass = $hasError ? 'border-danger' : 'border-success';
        @endphp
        <div class="card mb-3 {{ $borderClass }} border">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-info me-2">Мувофиқоварӣ</span>
                    <strong>Сатр {{ $q['row_num'] }}:</strong>
                    {{ $q['question_text'] }}
                    @if($hasError)
                    <span class="badge bg-danger ms-2">Хатогӣ</span>
                    @endif
                </div>
            </div>
            <div class="card-body">
                @if($hasError)
                <div class="alert alert-danger small">
                    <ul class="mb-0">
                        @foreach($q['errors'] as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @else
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted small text-uppercase">Ҷуфтҳо (Pairs)</h6>
                        <table class="table table-sm table-bordered small">
                            <thead class="table-light">
                                <tr>
                                    <th>№</th>
                                    <th>Item</th>
                                    <th>Match</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($q['pairs'] as $idx => $pair)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $pair['item'] }}</td>
                                    <td>{{ $pair['match'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($q['extra_options']))
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted small text-uppercase">Вариантҳои иловагӣ (Distractors)</h6>
                        <table class="table table-sm table-bordered small">
                            <thead class="table-light">
                                <tr>
                                    <th>№</th>
                                    <th>Text</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($q['extra_options'] as $idx => $extra)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>{{ $extra['text'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="col-md-6">
                        <div class="alert alert-warning small">
                            <strong>Вариант иловагӣ надорад.</strong> <em>(Ба тавсияи афзалӣ мувофиқ намекунад)</em>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.questions.matching-import') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Базгашт
            </a>

            @if($validCount > 0)
            <form method="POST"
                action="{{ route('admin.questions.matching-import-confirm') }}"
                id="confirmForm">
                @csrf

                <input type="hidden" name="confirm" value="1">

                <button type="submit" class="btn btn-success" id="confirmBtn">
                    <i class="bi bi-check-lg me-1"></i>
                    Тасдиқ кардани импорт ({{ $validCount }} савол)
                </button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script>
        document.getElementById('confirmForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('confirmBtn');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Иҷро шуда истодааст...';
        });
    </script>
@endpush
