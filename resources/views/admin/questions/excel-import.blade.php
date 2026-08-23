@extends('layouts.app')
@section('title', 'Импорти саволҳо аз Excel')
@section('page-header', 'Импорти саволҳо аз Excel')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файли Excel</h6>
                <a href="{{ route('admin.questions.excel-import-template') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i> Шаблонро зеркашӣ кунед
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.questions.excel-import-upload') }}" enctype="multipart/form-data" id="uploadForm">
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
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Намунаи формати Excel</h6>

                <h6 class="text-primary mt-3">Намунаи формати нав</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>Сатр</th>
                                <th>Мундариҷа</th>
                                <th>Маъно</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>@Пойтахти Тоҷикистон кадом шаҳр аст?</td>
                                <td><span class="badge bg-primary">Савол</span></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>$Душанбе</td>
                                <td><span class="badge bg-success">Ҷавоби дуруст</span></td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>&Хуҷанд</td>
                                <td><span class="badge bg-danger">Ҷавоби нодуруст</span></td>
                            </tr>
                            <tr>
                                <td>4</td>
                                <td>&Кӯлоб</td>
                                <td><span class="badge bg-danger">Ҷавоби нодуруст</span></td>
                            </tr>
                            <tr>
                                <td>5</td>
                                <td>&Бохтар</td>
                                <td><span class="badge bg-danger">Ҷавоби нодуруст</span></td>
                            </tr>
                            <tr>
                                <td>6</td>
                                <td>(холӣ)</td>
                                <td><span class="badge bg-secondary">Анҷоми савол</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    <strong>Қоидаҳо:</strong><br>
                    • `@` = саволи нав<br>
                    • `$` = ҷавоби дуруст<br>
                    • `&` = ҷавоби нодуруст<br>
                    • Тартиби `$` муҳим нест — метавонад дар ҳама ҷой бошад<br>
                    • Ҳар савол бояд ҳадди ақал 1 `$` + 1 `&` дошта бошад<br>
                    • Сатрҳои холӣ саволҳоро ҷудо мекунанд
                </p>

                <h6 class="text-primary mt-3">Намуна 2: Саволи мувофиқоварӣ</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Item</th>
                                <th>Key</th>
                                <th>Answer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Муҳофиқати барномаҳоро ёбед?</td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Ms.Excel</td>
                                <td>A</td>
                                <td>Ҷадвали электронӣ</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Ms.Word</td>
                                <td>B</td>
                                <td>Редактори матнӣ</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td>E</td>
                                <td></td>
                                <td>Почтаи электронӣ</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <hr>
                <p class="small text-muted mb-2">
                    <strong>Қоидаҳо:</strong>
                </p>
                <ul class="small text-muted ps-3 mb-0">
                    <li>Барои саволи оддӣ: Ҷавоби дуруст бояд ба яке аз вариантҳо мувофиқат кунад.</li>
                    <li>Барои саволи мувофиқоварӣ: Саволи аввал бояд дар сатри алоҳида бошад.</li>
                    <li>Вариантҳои иловагӣ: Агар сатри дуюм холӣ бошад, он variant-и иловагӣ мебошад.</li>
                    <li>Ҳар саволи мувофиқоварӣ бояд ҳадди ақал 1 ҷуфт дошта бошад.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('uploadForm').addEventListener('submit', function() {
    const btn = document.getElementById('uploadBtn');
    const file = document.getElementById('fileInput').value;
    const subject = document.querySelector('select[name="subject_id"]').value;

    if (!file || !subject) {
        alert('Лутфан файл ва фанро интихоб кунед.');
        return false;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Санчида истодааст...';
});
</script>
@endpush
