@extends('layouts.app')

@section('title', 'Импорти саволҳои рейтинг аз Excel')
@section('page-header', 'Импорти саволҳои рейтинг аз Excel')
@section('page-description', 'Боргузорӣ ва пешакӣ дидани саволҳо')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-upload me-2"></i>Боргузории файли Excel</h6></div>
            <div class="card-body">
                <div class="alert alert-info">
                    <p class="mb-1">Форматҳои дастгирифта: .xlsx, .xls (ҳадди калон: 10MB)</p>
                    <p class="mb-0"><code>@</code> саволи нав, <code>$</code> ҷавоби дуруст, <code>&amp;</code> ҷавоби нодуруст.</p>
                </div>
                <form method="POST" action="{{ route('admin.rating-questions.import') }}" enctype="multipart/form-data">
                    @csrf
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
                        <label class="form-label">Файл (Excel) <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept=".xlsx,.xls" class="form-control" required>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i>Бор кардан ва пешакӣ нишон додан</button>
                        <a href="{{ route('admin.rating-questions.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i>Шаблонро зеркашӣ кунед</a>
                        <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-outline-secondary">Баромад</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold">Намунаи формати Excel</h6>
                <table class="table table-sm table-bordered small mt-3">
                    <tbody>
                        <tr><td><code>@</code> Савол</td><td>Саволи нав</td></tr>
                        <tr><td><code>$</code> Ҷавоби дуруст</td><td>Якто бошад</td></tr>
                        <tr><td><code>&amp;</code> Ҷавоби нодуруст</td><td>Вариантҳои дигар</td></tr>
                        <tr><td>Сатри холӣ</td><td>Анҷоми савол</td></tr>
                    </tbody>
                </table>
                <p class="small text-muted mb-0">Мисол: <code>@Савол?</code>, баъд як сатри <code>$</code> ва чанд сатри <code>&amp;</code>.</p>
            </div>
        </div>
    </div>
</div>
@endsection
