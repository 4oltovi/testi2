@extends('layouts.app')

@section('title', 'Импорти омӯзгорон аз Excel')
@section('page-header', 'Импорти омӯзгорон аз Excel')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файли Excel</h6>
                <a href="{{ route('admin.teachers.excel-import.template') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i> Шаблонро зеркашӣ кунед
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.teachers.excel-import.upload') }}" enctype="multipart/form-data" id="uploadForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Файл (Excel) *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required id="fileInput">
                        <div class="form-text">
                            Форматҳои дастгирифта: .xlsx, .xls (ҳадди калон: 10MB)
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-primary" id="uploadBtn">
                            <i class="bi bi-upload me-1"></i> Бор кардан ва пешакӣ нишон додан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Намуни формати Excel</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>Сатр</th>
                                <th>Насаб</th>
                                <th>Ном</th>
                                <th>Логин</th>
                                <th>Кафедра</th>
                                <th>Вазифа</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Раҳимов</td>
                                <td>Фирдавс</td>
                                <td>f.rahimov</td>
                                <td>FTM</td>
                                <td>Ассистент</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Каримова</td>
                                <td>Малика</td>
                                <td>m.karimova</td>
                                <td>FIL</td>
                                <td>Доцент</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    <strong>Қоидаҳо:</strong><br>
                    • Сатри аввал = сарлавҳа (бохшед бошад)<br>
                    • Ҳар сатр = як омӯзгор<br>
                    • Парол хоҳӣ бошад, худкунон аст<br>
                    • Рамзи кафедра бояд бо кафедраи вазъият масдуд ҳамоңгиш бошад
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('uploadBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Санчида истодааст...';
});
</script>
@endpush
