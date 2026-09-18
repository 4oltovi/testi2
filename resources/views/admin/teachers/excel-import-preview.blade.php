@extends('layouts.app')

@section('title', 'Пешакӣ: Импорти омӯзгорон')
@section('page-header', 'Пешакӣ: Импорти омӯзгорон')

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

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="bi bi-table me-2"></i> Сарфиҳои пешакӣ</h6>
        <span class="badge bg-{{ $invalidCount > 0 ? 'danger' : 'success' }}">
            {{ $validCount }} / {{ $totalRows }} мувофиқ
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Ном</th>
                        <th>Логин</th>
                        <th>Кафедра</th>
                        <th>Вазифа</th>
                        <th>Қабулият</th>
                        <th>Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachers as $teacher)
                    @php
                        $hasError = !empty($teacher['errors']);
                    @endphp
                    <tr class="{{ $hasError ? 'table-danger' : '' }}">
                        <td>{{ $teacher['row_num'] }}</td>
                        <td>{{ $teacher['last_name'] }} {{ $teacher['first_name'] }}</td>
                        <td>{{ $teacher['login'] }}</td>
                        <td>{{ $teacher['department_code'] }}<br><small class="text-muted">{{ $teacher['department_id'] ? 'ёфт шуд' : 'ёфт нашуд' }}</small></td>
                        <td>{{ $teacher['position'] ?? '—' }}</td>
                        <td>{{ $teacher['hire_date'] ?? '—' }}</td>
                        <td>
                            @if($hasError)
                                <span class="badge bg-danger">Хато</span>
                                <small class="d-block text-danger">
                                    @foreach($teacher['errors'] as $err)
                                        <div>{{ $err }}</div>
                                    @endforeach
                                </small>
                            @else
                                <span class="badge bg-success">ОК</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="{{ route('admin.teachers.excel-import') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-right me-1"></i> Азнавон
        </a>
        <form method="POST" action="{{ route('admin.teachers.excel-import.confirm') }}" id="confirmForm">
            @csrf
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="btn btn-success" id="confirmBtn">
                <i class="bi bi-check-lg me-1"></i> Тасдиқи импорт ({{ $validCount }})
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
document.getElementById('confirmForm').addEventListener('submit', function(e) {
    if (!confirm('Амиқ дӯст доред {{ $validCount }} омӯзгорро ворид кунед?')) {
        e.preventDefault();
    }
});
document.getElementById('confirmBtn').addEventListener('click', function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Санчида истодааст...';
});
</script>
@endpush
