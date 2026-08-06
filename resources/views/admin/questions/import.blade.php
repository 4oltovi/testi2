@extends('layouts.app')
@section('title', 'Импорти саволномаҳо')
@section('page-header', 'Импорти саволномаҳо аз CSV')

@section('content')
<div class="row">
<div class="col-md-8">
<div class="card border-0 shadow-sm">
<div class="card-header bg-white">
    <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файл</h6>
</div>
<div class="card-body">
    @if(session('import_errors'))
    <div class="alert alert-warning">
        <ul class="mb-0 small">
            @foreach(session('import_errors') as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.exams.questions.import') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Фан *</label>
            <select name="subject_id" class="form-select" required>
                <option value="">— Интихоб —</option>
                @foreach($subjects as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Файл (CSV) *</label>
            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
        </div>
        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i> Ворид кардан
            </button>
        </div>
    </form>
</div>
</div>
</div>
<div class="col-md-4">
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <a href="{{ route('admin.exams.questions.download-template') }}" class="btn btn-outline-success w-100 mb-3">
            <i class="bi bi-download me-1"></i> Зеркашии шаблон
        </a>
        <h6>Сутунҳо:</h6>
        <ul class="small ps-3">
            <li><code>question_text</code> — Матни савол *</li>
            <li><code>type</code> — single_choice / multiple_choice / true_false / matching</li>
            <li><code>difficulty_level</code> — 1-5</li>
            <li><code>option_a..e</code> — Вариантҳо (то 5)</li>
            <li><code>correct_answer</code> — a/b/c/d/e (ё ab, ac...)</li>
            <li><code>explanation</code> — Шарҳ</li>
        </ul>
        <hr>
        <p class="small text-muted mb-0">
            <strong>Балл:</strong> Автоматикӣ аз Танзимот гирифта мешавад.<br>
            <strong>Генератсия:</strong> Саволҳо ҳар бор омехта мешаванд.
        </p>
    </div>
</div>
</div>
</div>
@endsection
