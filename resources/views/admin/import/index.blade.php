@extends('layouts.app')

@section('title', 'Импорти донишҷӯён')
@section('page-header', 'Импорти донишҷӯён аз Excel/CSV')
@section('page-description', 'Ворид кардани донишҷӯён аз файли шаблонӣ')

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-upload me-2"></i> Боркунии файл</h6>
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

                    <form method="POST" action="{{ route('admin.import.students') }}" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Гурӯҳ <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-select" required>
                                <option value="">— Гурӯҳро интихоб кунед —</option>
                                @foreach($groups as $group)
                                    <option value="{{ $group->id }}">
                                        {{ $group->name }}
                                        @if($group->specialty) — {{ $group->specialty->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('group_id') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Файл (CSV ё Excel) <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls,.txt" required>
                            <small class="text-muted">Формат: CSV ё XLSX. Ҳадди аксар: 5 MB</small>
                            @error('file') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="generate_password"
                                       value="1" id="genPass" checked>
                                <label class="form-check-label" for="genPass">
                                    Паролро автоматикӣ созидан
                                </label>
                            </div>
                            <small class="text-muted">Агар хомӯш бошад, парол барои ҳама: student123</small>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload me-1"></i> Ворид кардан
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Дастурамал --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-file-earmark-arrow-down me-2"></i> Шаблон</h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        Аввал шаблонро зеркашӣ кунед, пур кунед ва бозгашт диҳед.
                    </p>
                    <a href="{{ route('admin.import.template') }}" class="btn btn-outline-success btn-sm w-100">
                        <i class="bi bi-download me-1"></i> Зеркашии шаблон (CSV)
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
                        <li><code>last_name</code> — Насаб *</li>
                        <li><code>first_name</code> — Ном *</li>
                        <li><code>middle_name</code> — Номи падар</li>
                        <li><code>email</code> — Email</li>
                        <li><code>phone</code> — Телефон</li>
                        <li><code>birth_date</code> — Таваллуд (YYYY-MM-DD)</li>
                        <li><code>gender</code> — male/female</li>
                        <li><code>student_id_number</code> — Рақами донишҷӯ</li>
                        <li><code>passport_number</code> — Паспорт</li>
                    </ul>
                    <p class="mt-2 text-muted mb-0">* — ҳатмӣ</p>
                </div>
            </div>
        </div>
    </div>
@endsection
