@extends('layouts.app')

@section('title', 'Пешназори гузариш ба соли нав')
@section('page-header', 'Пешназори гузариш')
@section('page-description', 'Тавсия ба ҳамаи донишҷӯён барои гузариш ба соли нав')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<h4 class="mb-3">🎓 Пешназори гузариш ба соли нав</h4>
<p class="text-muted mb-4">Ин пешназор ҳеч яке аз амалиётҳои зерро иҷро намекунад. Тавсия ба интиқол ба <strong>POST /admin/settings/promote-all</strong> барои иҷрои воқеӣ.</p>

<div class="row g-3 mb-4">
    {{-- Хатмкарда (барояд қарз) --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-success border-start border-3">
            <div class="card-body">
                <h4 class="text-success mb-0">{{ $categories['graduated']|count }}</h4>
                <small class="text-muted">Хатмкарда (қарз надорад)</small>
            </div>
        </div>
    </div>
    {{-- Хатмкарда (қарз дорад) --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-warning border-start border-3">
            <div class="card-body">
                <h4 class="text-warning mb-0">{{ $categories['graduated_with_debts']|count }}</h4>
                <small class="text-muted">Хатмкарда (қарз дорад — баррасӣ лозим)</small>
            </div>
        </div>
    </div>
    {{-- Гузаронидашуда --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-info border-start border-3">
            <div class="card-body">
                <h4 class="text-info mb-0">{{ $categories['promoted']|count }}</h4>
                <small class="text-muted">Гузаронидашуда (курс+1, гурӯҳи нав)</small>
            </div>
        </div>
    </div>
</div>

{{-- Бозмонандагон --}}
@if(count($categories['graduated_with_debts']) > 0)
<div class="card border-0 shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0">⚠️ Баррасӣ лозим — Донишҷӯёне, ки хатм мекунанд аммо қарз доранд</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳ</th><th>Фан</th><th>Қарзҳои кушод</th></tr></thead>
            <tbody>
                @foreach($categories['graduated_with_debts'] as $item)
                <tr class="table-warning">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>{{ $item['student']->group?->name }}</td>
                    <td>{{ $item['student']->specialty?->name }}</td>
                    <td><span class="badge bg-danger">{{ $item['open_debts'] }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['promoted']) > 0)
<div class="card border-0 shadow-sm mb-4 border-info">
    <div class="card-header bg-info text-dark">
        <h6 class="mb-0">📋 Донишҷӯёне, ки ба курси +1 ва гурӯҳи нав гузаронида мешаванд</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳи қадим → Нав</th><th>Курси қадим → Нав</th></tr></thead>
            <tbody>
                @foreach($categories['promoted'] as $item)
                <tr class="table-info">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>
                        <span class="badge bg-secondary">{{ $item['from_group']?->name }}</span>
                        <i class="bi bi-arrow-right mx-1"></i>
                        <span class="badge bg-info">{{ $item['to_group']?->name }}</span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $item['from_course']?->name }}</span>
                        <i class="bi bi-arrow-right mx-1"></i>
                        <span class="badge bg-success">{{ $item['to_course']?->name }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['needs_review_no_group']) > 0)
<div class="card border-0 shadow-sm mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0">🔴 Баррасӣ лозим — Гурӯҳи нав барои курс+1 вуҷуд надорад</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳ</th><th>Фан</th><th>Сабаб</th></tr></thead>
            <tbody>
                @foreach($categories['needs_review_no_group'] as $item)
                <tr class="table-danger">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>{{ $item['student']->group?->name }}</td>
                    <td>{{ $item['student']->specialty?->name ?? '-' }}</td>
                    <td><span class="badge bg-dark">No matching group</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['needs_review_no_duration']) > 0)
<div class="card border-0 shadow-sm mb-4 border-danger">
    <div class="card-header bg-danger text-white">
        <h6 class="mb-0">🔴 Баррасӣ лозим — Муддати таҳсил анҷом нашудааст</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳ</th><th>Сабаб</th></tr></thead>
            <tbody>
                @foreach($categories['needs_review_no_duration'] as $item)
                <tr class="table-danger">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>{{ $item['student']->group?->name }}</td>
                    <td><span class="badge bg-dark">Muddat not set</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['skipped_no_course']) > 0)
<div class="card border-0 shadow-sm mb-4 border-secondary">
    <div class="card-header bg-secondary text-white">
        <h6 class="mb-0">⏭️ Гузарта нашуданд — Рақами курс муайян нагардорӣ</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳ</th><th>Сабаб</th></tr></thead>
            <tbody>
                @foreach($categories['skipped_no_course'] as $item)
                <tr class="table-secondary">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>{{ $item['student']->group?->name }}</td>
                    <td><small>{{ $item['reason'] }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="d-flex gap-2 mt-4">
    <form method="POST" action="{{ route('settings.promote-all') }}" onsubmit="return confirm('Амалиёти пешназор иҷро шавад? Ин ҳатмият надорад!');">
        @csrf
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="bi bi-check-circle"></i> ✅ Амалӣ сохтани пешназор
        </button>
    </form>
    <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-lg">Бозгашт</a>
</div>

<p class="text-muted mt-3"><small>⏰ Баъди амалӣ сохтан, ёддиҳед ки соли нав ва семестри аввали он бояд фаъол карда шавад преканди "⭐ Фаъол" дар ин саҳифа.</small></p>
@endsection
