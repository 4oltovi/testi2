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
                <h4 class="text-success mb-0">{{ count($categories['graduated']) }}</h4>
                <small class="text-muted">Хатмкарда (қарз надорад)</small>
            </div>
        </div>
    </div>
    {{-- Хатмкарда (қарз дорад) --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-warning border-start border-3">
            <div class="card-body">
                <h4 class="text-warning mb-0">{{ count($categories['graduated_with_debts']) }}</h4>
                <small class="text-muted">Хатмкарда (қарз дорад — баррасӣ лозим)</small>
            </div>
        </div>
    </div>
    {{-- Гузаронидашуда --}}
    <div class="col-md-4">
        <div class="card border-0 shadow-sm border-info border-start border-3">
            <div class="card-body">
                <h4 class="text-info mb-0">{{ count($categories['promoted']) }}</h4>
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
                    <td>{{ $item['student']->group?->full_name }}</td>
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
        <h6 class="mb-0">📋 Донишҷӯёне, ки ба курси +1 гузаронида мешаванд (гурӯҳи ҳамдор тағйир меёбад)</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Донишҷӯ</th><th>Гурӯҳ (ҳозир → баъдан)</th><th>Курси ҳозир → Нав</th></tr></thead>
            <tbody>
                @foreach($categories['promoted'] as $item)
                <tr class="table-info">
                    <td><a href="{{ route('admin.students.show', $item['student']) }}">{{ $item['student']->user?->full_name }}</a></td>
                    <td>
                        <span class="badge bg-secondary">{{ $item['from_group']?->full_name }}</span>
                        <i class="bi bi-arrow-right mx-1"></i>
                        <span class="badge bg-info">
                            @if(isset($item['new_full_name']))
                                {{ $item['new_full_name'] }}
                            @else
                                {{ $item['to_group']?->full_name }}
                            @endif
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $item['from_course']?->name }}</span>
                        <i class="bi bi-arrow-right mx-1"></i>
                        <span class="badge bg-success">{{ $item['to_course']?->name }}</span>
                        @if(isset($item['new_code']))
                            <br><small class="text-muted">Рамз: {{ $item['to_group']?->code ?? '' }} → {{ $item['new_code'] }}</small>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['groups_renamed']) > 0)
<div class="card border-0 shadow-sm mb-4 border-success">
    <div class="card-header bg-success text-white">
        <h6 class="mb-0">✅ Ҳамгурӯҳҳое, ки ба донишҷӯён таъсир мерасанд (курс+1)</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Гурӯҳ</th><th>Пурра ном (пеш)</th><th>Пурра ном (баъд)</th><th>Код (пеш)</th><th>Код (баъд)</th></tr></thead>
            <tbody>
                @foreach($categories['groups_renamed'] as $groupId => $info)
                <tr class="table-success">
                    <td>{{ $groupId }}</td>
                    <td>{{ $info['old_full_name'] }}</td>
                    <td>{{ $info['new_full_name'] }}</td>
                    <td><code>{{ $info['old_code'] }}</code></td>
                    <td><code>{{ $info['new_code'] }}</code></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($categories['groups_code_unchanged']) > 0)
<div class="card border-0 shadow-sm mb-4 border-warning">
    <div class="card-header bg-warning text-dark">
        <h6 class="mb-0">⚠️ Баррасӣ лозим — Рамзҳои гурӯҳҳои зер ҳал нагарданд (мануалӣ тозикор лозим)</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light"><tr><th>Гурӯҳ</th><th>Рамз</th><th>Сабаб</th></tr></thead>
            <tbody>
                @foreach($categories['groups_code_unchanged'] as $groupId => $code)
                <tr class="table-warning">
                    <td>{{ $groupId }}</td>
                    <td><code>{{ $code }}</code></td>
                    <td><span class="badge bg-dark">Code does not start with expected course digit — manual fix needed</span></td>
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
                    <td>{{ $item['student']->group?->full_name }}</td>
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
                    <td>{{ $item['student']->group?->full_name }}</td>
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
                    <td>{{ $item['student']->group?->full_name }}</td>
                    <td><small>{{ $item['reason'] }}</small></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="d-flex gap-2 mt-4">
    <form method="POST" action="{{ route('admin.settings.promote-all') }}" onsubmit="return confirm('Амалиёти пешназор иҷро шавад? Ин ҳатмият надорад!');">
        @csrf
        <button type="submit" class="btn btn-warning btn-lg">
            <i class="bi bi-check-circle"></i> ✅ Амалӣ сохтани пешназор
        </button>
    </form>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-lg">Бозгашт</a>
</div>

<p class="text-muted mt-3"><small>⏰ Баъди амалӣ сохтан, ёддиҳед ки соли нав ва семестри аввали он бояд фаъол карда шавад преканди "⭐ Фаъол" дар ин саҳифа.</small></p>
@endsection
