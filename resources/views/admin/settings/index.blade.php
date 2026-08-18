@extends('layouts.app')

@section('title', 'Танзимот')
@section('page-header', 'Танзимот')
@section('page-description', 'Формулаи баҳо, тест, муассиса ва амният')

@section('content')
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ===================== Формулаи баҳо ===================== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calculator me-2"></i> Формулаи баҳои ниҳоӣ</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ url('admin/settings') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th style="width:200px;">Қимат</th>
                        <th>Шарҳ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($formulaSettings as $setting)
                    <tr>
                        <td>
                            <strong>{{ $setting->display_name }}</strong>
                            <br><small class="text-muted">{{ $setting->key }}</small>
                        </td>
                        <td>
                            <input type="number"
                                name="settings[{{ $setting->key }}]"
                                class="form-control"
                                value="{{ $setting->value }}"
                                step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                                {{ $setting->type === 'float' ? 'min=0 max=1' : '' }}>
                        </td>
                        <td><small>{{ $setting->description }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардани формула
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== Танзимоти тест ===================== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Танзимоти тест</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ url('admin/settings') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th style="width:200px;">Қимат</th>
                        <th>Шарҳ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($testSettings as $setting)
                    <tr>
                        <td>
                            <strong>{{ $setting->display_name }}</strong>
                            <br><small class="text-muted">{{ $setting->key }}</small>
                        </td>
                        <td>
                            @if($setting->type === 'boolean')
                            <select name="settings[{{ $setting->key }}]" class="form-select">
                                <option value="1" {{ $setting->value ? 'selected' : '' }}>Ҳа</option>
                                <option value="0" {{ !$setting->value ? 'selected' : '' }}>Не</option>
                            </select>
                            @else
                            <input type="number" name="settings[{{ $setting->key }}]"
                                class="form-control" value="{{ $setting->value }}" min="1">
                            @endif
                        </td>
                        <td><small>{{ $setting->description }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардани танзимоти тест
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== Танзимоти муассиса ===================== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-building me-2"></i> Танзимоти муассиса</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ url('admin/settings') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th>Қимат</th>
                        <th>Шарҳ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($organizationSettings as $setting)
                    @if($setting->key !== 'institution_logo')
                    <tr>
                        <td>
                            <strong>{{ $setting->display_name }}</strong>
                            <br><small class="text-muted">{{ $setting->key }}</small>
                        </td>
                        <td>
                            <input type="text" name="settings[{{ $setting->key }}]"
                                class="form-control" value="{{ $setting->value }}">
                        </td>
                        <td><small>{{ $setting->description }}</small></td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардан
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ===================== Логотипи муассиса ===================== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-image me-2"></i> Логотипи муассиса</h6>
    </div>
    <div class="card-body">
        @php $logo = \App\Models\Setting::get('institution_logo'); @endphp
        @if($logo && file_exists(public_path($logo)))
        <img src="{{ asset($logo) }}" style="height:90px;" class="mb-3 d-block border rounded p-2">
        @endif
        <form method="POST" action="{{ url('admin/settings/logo') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-2">
                <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-upload me-1"></i> Бор кардани логотип
            </button>
        </form>
        <small class="text-muted">Формат: PNG ё JPG. Логотип дар барнома, ведомост ва транскрипт автоматӣ истифода мешавад.</small>
    </div>
</div>

{{-- ===================== Амният ва режими тест ===================== --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i> Амният ва режими тест</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ url('admin/settings') }}">
            @csrf
            @method('PUT')
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th style="width:220px;">Қимат</th>
                        <th>Шарҳ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($securitySettings as $setting)
                    <tr>
                        <td>
                            <strong>{{ $setting->display_name }}</strong>
                            <br><small class="text-muted">{{ $setting->key }}</small>
                        </td>
                        <td>
                            @if($setting->key === 'test_mode')
                            <select name="settings[{{ $setting->key }}]" class="form-select">
                                <option value="online" {{ $setting->value === 'online' ? 'selected' : '' }}>🌐 Онлайн</option>
                                <option value="offline" {{ $setting->value === 'offline' ? 'selected' : '' }}>🏠 Оффлайн (локалӣ)</option>
                            </select>
                            @else
                            <select name="settings[{{ $setting->key }}]" class="form-select">
                                <option value="1" {{ $setting->value ? 'selected' : '' }}>Фаъол</option>
                                <option value="0" {{ !$setting->value ? 'selected' : '' }}>Ғайрифаъол</option>
                            </select>
                            @endif
                        </td>
                        <td><small>{{ $setting->description }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Сабт кардан
            </button>
        </form>

        <hr>

        <div class="d-flex gap-2">
            <form method="POST" action="{{ url('admin/settings/optimize') }}">
                @csrf
                <button class="btn btn-success"><i class="bi bi-lightning-charge me-1"></i> ⚡ Оптимизатсия (кеш)</button>
            </form>
            <form method="POST" action="{{ url('admin/settings/clear-cache') }}">
                @csrf
                <button class="btn btn-warning"><i class="bi bi-trash me-1"></i> 🧹 Тоза кардани кеш</button>
            </form>
        </div>
        <small class="text-muted d-block mt-2">
            ⚡ Оптимизатсия — танҳо дар сервер (production) пахш кунед! Пас аз ҳар тағйироти код аввал 🧹 Тоза кунед.
        </small>
    </div>
</div>
{{-- ===================== Соли хониш ва гузариш ===================== --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calendar2-range me-2"></i> Соли хониш ва гузариш ба соли нав</h6>
    </div>
    <div class="card-body">

        <div class="row g-3">
            {{-- Сохтани соли нав --}}
            <div class="col-md-4">
                <form method="POST" action="{{ url('admin/settings/new-year') }}">
                    @csrf
                    <label class="form-label">Соли нави хониш</label>
                    <div class="input-group">
                        <input type="number" name="start_year" class="form-control"
                            value="{{ now()->year }}" min="2000" max="2100">
                        <button class="btn btn-success" type="submit">➕ Сохтан</button>
                    </div>
                    <small class="text-muted">Сол + 2 семестр автоматӣ сохта мешаванд</small>
                </form>
            </div>

            {{-- Фаъол кардани сол --}}
            <div class="col-md-4">
                <form method="POST" action="{{ url('admin/settings/activate-year') }}">
                    @csrf
                    <label class="form-label">Соли ҷорӣ</label>
                    <div class="input-group">
                        <select name="academic_year_id" class="form-select">
                            @foreach($academicYears as $y)
                            <option value="{{ $y->id }}" {{ $y->is_current ? 'selected' : '' }}>
                                {{ $y->name }} @if($y->is_current) (ҷорӣ) @endif
                            </option>
                            @endforeach
                        </select>
                        <button class="btn btn-primary" type="submit">⭐ Фаъол</button>
                    </div>
                    <small class="text-muted">Соли интихобшуда "ҷорӣ" мешавад</small>
                </form>
            </div>

            {{-- Гузариш ба соли нав --}}
            <div class="col-md-4">
                <form method="POST" action="{{ url('admin/settings/promote-all') }}"
                    onsubmit="return confirm('Диққат! Ҳамаи донишҷӯёни фаъол ба курси нав мегузаранд ва курси 4 хатм мекунанд. Давом диҳед?');">
                    @csrf
                    <label class="form-label">Гузариш ба соли нав</label>
                    <div class="d-grid">
                        <button class="btn btn-warning" type="submit">🎓 Гузариш ба соли нав</button>
                    </div>
                    <small class="text-muted">Донишҷӯён курс+1, гурӯҳи нав, курси 4 → хатм</small>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection