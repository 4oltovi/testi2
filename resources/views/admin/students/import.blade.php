@extends('layouts.app')

@section('title', 'Импорти донишҷӯён')
@section('page-header', 'Импорти донишҷӯён аз Excel')
@section('page-description', 'Ворид кардани донишҷӯён аз файли шаблонӣ')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файли Excel</h6>
                </div>
                <div class="card-body">
                    @if(session('import_errors'))
                        <div class="alert alert-warning">
                            <h6><i class="bi bi-exclamation-triangle me-1"></i> Огоҳдориҳо:</h6>
                            <ul class="mb-0 small">
                                @foreach(session('import_errors') as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.students.import') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Гурӯҳ <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-select" required>
                                <option value="">— Гурӯҳро интихоб кунед —</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">
                                        {{ $group->full_name }}
                                        @if($group->specialty) — {{ $group->specialty->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Файл (Excel) <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">Формат: XLSX. Ҳадди аксар: 5 MB</small>
                            @error('file') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="alert alert-info mb-3">
                            <i class="bi bi-key me-1"></i>
                            Пароли ибтидоии ҳамаи донишҷӯёни нав: <strong>12345678</strong>.
                            Ҳангоми воридшавии аввал донишҷӯ бояд онро иваз кунад.
                        </div>

                        <div class="text-end">
                            <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary me-2">
                                <i class="bi bi-arrow-left me-1"></i> Бозгашт
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i> Ворид кардан
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-down me-2"></i> Шаблон</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Аввал шаблонро зеркашӣ кунед, пур кунед ва бозгашт диҳед.
                    </p>
                    <a href="{{ route('admin.students.import-template') }}" class="btn btn-outline-success btn-sm w-100">
                        <i class="bi bi-download me-1"></i> Зеркашии шаблон (Excel)
                    </a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Дастурамал</h6>
                </div>
                <div class="card-body small">
                    <ol class="ps-3 mb-0">
                        <li>Шаблонро зеркашӣ кунед</li>
                        <li>Маълумоти донишҷӯёнро ворид кунед</li>
                        <li>Гурӯҳро интихоб кунед</li>
                        <li>Файлро боркунӣ кунед</li>
                    </ol>
                    <hr>
                    <p class="mb-1"><strong>Сутунҳо:</strong></p>
                    <ul class="ps-3 mb-0">
                        <li><code>Насаб *</code> — Насаб *</li>
                        <li><code>Ном *</code> — Ном *</li>
                        <li><code>Номи падар</code> — Номи падар</li>
                        <li><code>Email</code> — Email</li>
                        <li><code>ID донишҷӯӣ *</code> — ID донишҷӯӣ *</li>
                        <li><code>Рақами зачётка</code> — Рақами зачётка</li>
                        <li><code>Шакли таъмин *</code> — Буҷетӣ/Шартномавӣ</li>
                        <li><code>Шакли таҳсил *</code> — Рӯзона/Ғоибона/Шабона</li>
                        <li><code>Ихтисос *</code> — Ихтисос *</li>
                        <li><code>Гурӯҳ *</code> — Гурӯҳ *</li>
                        <li><code>Курс *</code> — Курс *</li>
                        <li><code>Санаи қабул *</code> — DD.MM.YYYY</li>
                        <li><code>Санаи таваллуд</code> — DD.MM.YYYY</li>
                        <li><code>Ҷинс</code> — Мард/Зан</li>
                        <li><code>Миллат</code> — Миллат</li>
                        <li><code>Паспорт (серия)</code> — Серия</li>
                        <li><code>Паспорт (рақам)</code> — Рақам</li>
                        <li><code>Телефон</code> — Телефон</li>
                        <li><code>Волидон (ном)</code> — Номи волидон</li>
                        <li><code>Телефони волидон</code> — Телефон</li>
                        <li><code>Суроғаи доимӣ</code> — Суроғаи доимӣ</li>
                        <li><code>Суроғаи ҳозира</code> — Суроғаи ҳозира</li>
                    </ul>
                    <p class="mt-2 text-muted mb-0">* — ҳатмӣ</p>
                </div>
            </div>
        </div>
    </div>
@endsection
