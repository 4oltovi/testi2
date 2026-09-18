@extends('layouts.app')
@section('title', 'Импорти саволҳои мувофиқоварӣ аз Excel')
@section('page-header', 'Импорти саволҳои мувофиқоварӣ')

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файли Excel (мувофиқоварӣ)</h6>
                <a href="{{ route('admin.questions.matching-import-template') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i> Шаблонро зеркашӣ кунед
                </a>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.questions.matching-import-upload') }}" enctype="multipart/form-data" id="uploadForm">
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
                <h6 class="fw-bold mb-3">Намунаи формати мувофиқоварӣ</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered small">
                        <thead class="table-light">
                            <tr>
                                <th>A</th>
                                <th>B — Савол / Item</th>
                                <th>C — Letter</th>
                                <th>D — Match / Answer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td></td>
                                <td><strong>Боғи савол</strong></td>
                                <td></td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>1</td>
                                <td>Item 1</td>
                                <td>A</td>
                                <td>Match 1</td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Item 2</td>
                                <td>B</td>
                                <td>Match 2</td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Item 3</td>
                                <td>C</td>
                                <td>Match 3</td>
                            </tr>
                            <tr>
                                <td></td>
                                <td></td>
                                <td>D</td>
                                <td>Вариант иловагӣ (distractor)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mt-2 mb-0">
                    <strong>Қоидаҳо:</strong><br>
                    • Сатри аввал: матни савол дар боғи B<br>
                    • Сатрҳои озод: ҷуфт (item дар B, letter дар C, match дар D)<br>
                    • Сатри охир: вариант иловагӣ (letter дар C, матн дар D)<br>
                    • Сатри холӣ: саволи навро ҷудо мекунад<br>
                    • Ҳадди ақал 2 ҷуфт лозим аст<br>
                    • Вариант иловагӣ (distractor) иборат аст — ба тавсияи афзалӣ мувофиқ намекунад
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('uploadForm')?.addEventListener('submit', function() {
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
