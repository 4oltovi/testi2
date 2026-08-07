@extends('layouts.app')

@section('title', 'Танзимоти система')
@section('page-header', 'Танзимоти система')
@section('page-description', 'Танзимоти формулаҳо ва тест')

@section('content')
{{-- Формулаи баҳо --}}
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-calculator me-2"></i> Формулаи баҳои ниҳоӣ</h6>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            <strong>Формулаи стандарт:</strong> Ниҳоӣ = R1 × W1 + R2 × W2 + Имтиҳон × WE<br>
            <strong>Формулаи бо КМ:</strong> Ниҳоӣ = R1 × W1 + R2 × W2 + КМ × WI + Имтиҳон × WE<br>
            <small class="text-muted">Маҷмӯи коэффисиентҳо бояд ба 1.0 баробар бошад.</small>
        </div>

        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th style="width: 200px;">Қимат</th>
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
                            <input type="{{ $setting->type === 'float' ? 'number' : ($setting->type === 'integer' ? 'number' : 'text') }}"
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

{{-- Танзимоти тест --}}
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-pencil-square me-2"></i> Танзимоти тест</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
            @csrf
            @method('PUT')

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Параметр</th>
                        <th style="width: 200px;">Қимат</th>
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
{{-- Танзимоти муассиса (ведомост/транскрипт) --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-building me-2"></i> Танзимоти муассиса</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.update') }}">
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
{{-- Логотипи муассиса --}}
<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0"><i class="bi bi-image me-2"></i> Логотипи муассиса</h6>
    </div>
    <div class="card-body">
        @php $logo = \App\Models\Setting::get('institution_logo'); @endphp
        @if($logo && file_exists(public_path($logo)))
        <img src="{{ asset($logo) }}" style="height:90px;" class="mb-3 d-block border rounded p-2">
        @endif
        <form method="POST" action="{{ route('admin.settings.logo') }}" enctype="multipart/form-data">
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
@endsection