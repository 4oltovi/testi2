@extends('layouts.app')
@section('title', 'Пешакӣ: Импорти саволҳо аз Excel')
@section('page-header', 'Пешакӣ: Импорти саволҳо аз Excel')

@section('content')
<div class="row mb-4">
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-primary mb-0">{{ $totalRows }}</h3>
                <small class="text-muted">Умумии сатрҳо</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-info mb-0">{{ $simpleCount }}</h3>
                <small class="text-muted">Саволи оддӣ</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h3 class="fw-bold text-success mb-0">{{ $validCount }}</h3>
                <small class="text-muted">Мувофиқ</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
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
        <h6 class="mb-0"><i class="bi bi-list-check me-2"></i> Саволи 1-ум: {{ $subject->name }}</h6>
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
                    <span class="badge bg-{{ $q['type'] === 'matching' ? 'info' : 'primary' }} me-2">
                        {{ $q['type'] === 'matching' ? 'Мувофиқоварӣ' : 'Якҷавобӣ' }}
                    </span>
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
                @if($q['type'] === 'matching')
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted small text-uppercase">Ҷуфтҳо</h6>
                        <table class="table table-sm table-bordered small">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th>Key</th>
                                    <th>Ҷавоб</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($q['pairs'] as $pair)
                                <tr>
                                    <td>{{ $pair['item'] }}</td>
                                    <td><span class="badge bg-secondary">{{ $pair['key'] }}</span></td>
                                    <td>{{ $pair['match'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if(!empty($q['extra_options']))
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted small text-uppercase">Вариантҳои иловагӣ</h6>
                        <table class="table table-sm table-bordered small">
                            <thead class="table-light">
                                <tr>
                                    <th>Key</th>
                                    <th>Ҷавоб</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($q['extra_options'] as $extra)
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $extra['key'] }}</span></td>
                                    <td>{{ $extra['text'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                @else
                <h6 class="fw-bold text-muted small text-uppercase">Вариантҳои ҷавоб</h6>
                <div class="row">
                    @foreach($q['options'] as $idx => $opt)
                    <div class="col-md-6 mb-2">
                        <div class="d-flex align-items-center p-2 border rounded {{ $idx === $q['correct_index'] ? 'border-success bg-success bg-opacity-10' : '' }}">
                            <span class="badge bg-{{ $idx === $q['correct_index'] ? 'success' : 'secondary' }} me-2">
                                {{ chr(65 + $idx) }}
                            </span>
                            {{ $opt }}
                            @if($idx === $q['correct_index'])
                            <i class="bi bi-check-circle-fill text-success ms-auto"></i>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.questions.excel-import-form') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>

            @if($validCount > 0)
            <form method="POST"
                action="{{ route('admin.questions.excel-import-confirm') }}"
                id="confirmForm">
                @csrf

                <input type="hidden" name="confirm" value="1">

                <button type="submit" class="btn btn-success" id="confirmBtn">
                    <i class="bi bi-check-lg me-1"></i>
                    Тасдиқ кардани import ({{ $validCount }} савол)
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