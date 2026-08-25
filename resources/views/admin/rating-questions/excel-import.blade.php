@extends('layouts.app')

@section('title', 'Импорти саволҳои рейтинг')
@section('page-header', 'Импорти саволҳои рейтинг')
@section('page-description', 'Боргузорӣ ва пешакӣ')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><i class="bi bi-file-earmark-excel me-2"></i> Боргузории файли Excel</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle me-1"></i> Шакли файл</h6>
                    <p>Танҳо файлҳои <strong>Excel (.xlsx)</strong> ё <strong>CSV</strong> қабул мешаванд.</p>
                    <p>Сатрҳои сарлавҳа ва мисолҳо дар шаблони зер оварда шудаанд:</p>
                    <pre class="mb-0">@ = савол | $ = ҷавоби дуруст | & = ҷавоби нодуруст</pre>
                </div>

                <div class="mb-3">
                    <label class="form-label">Фан <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select" required>
                        <option value="">— Интихоб кунед —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Файли Excel <span class="text-danger">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-upload me-1"></i> Бор кардан
                    </button>
                    <a href="{{ route('admin.rating-questions.template') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-download me-1"></i> Шаблон
                    </a>
                    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-outline-secondary">Баромад</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
