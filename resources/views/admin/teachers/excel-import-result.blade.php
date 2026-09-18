@extends('layouts.app')

@section('title', 'Натиҷаи импорти омӯзгорон')
@section('page-header', 'Натиҷаи импорти омӯзгорон')

@section('content')
<div class="row mb-4">
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold text-success mb-0">{{ $imported }}</h2>
                <small class="text-muted">Сабт шуданд</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold text-secondary mb-0">{{ $skipped }}</h2>
                <small class="text-muted">Скрейт шуданд (хато)</small>
            </div>
        </div>
    </div>
    <div class="col-md-4 col-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h2 class="fw-bold text-danger mb-0">{{ $failed }}</h2>
                <small class="text-muted">Номуваффақият</small>
            </div>
        </div>
    </div>
</div>

@if(!empty($generatedPasswords))
<div class="alert alert-warning">
    <h6 class="fw-bold"><i class="bi bi-key me-2"></i>Паролҳои худкунонашуда</h6>
    <p class="small text-muted">Ин паролҳо танҳо дар ин ҷо нишон дода мешаванд. Лутфан онҳоро ба омӯзгорон пеш аз дӯхтан равшан кунед.</p>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Ном</th>
                    <th>Логин</th>
                    <th>Парол</th>
                </tr>
            </thead>
            <tbody>
                @foreach($generatedPasswords as $idx => $cred)
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td>{{ $cred['name'] }}</td>
                    <td><code>{{ $cred['login'] }}</code></td>
                    <td><code>{{ $cred['password'] }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Ҳеч парол худкунонашуда надорад.
</div>
@endif

@if(!empty($failures))
<div class="alert alert-danger">
    <h6 class="fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Сатрҳои номуваффақият</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Логин</th>
                    <th>Ном</th>
                    <th>Сабаб</th>
                </tr>
            </thead>
            <tbody>
                @foreach($failures as $failure)
                <tr>
                    <td>{{ $failure['row_num'] }}</td>
                    <td><code>{{ $failure['login'] }}</code></td>
                    <td>{{ $failure['name'] }}</td>
                    <td>{{ $failure['reason'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="text-end">
    <a href="{{ route('admin.teachers.index') }}" class="btn btn-primary">
        <i class="bi bi-arrow-right me-1"></i> Бозгашт ба рӯйхати омӯзгорон
    </a>
</div>
@endsection
