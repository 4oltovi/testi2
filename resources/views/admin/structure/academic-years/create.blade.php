@extends('layouts.app')

@section('title', 'Сохтани соли таҳсилӣ')
@section('page-header', 'Сохтани соли таҳсилии нав')
@section('page-description', 'Илова кардани соли таҳсилии нав бо 2 семестр')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error) <p class="mb-0">{{ $error }}</p> @endforeach
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.structure.academic-years.store') }}">
                    @csrf

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Тавзеҳ:</strong> Вақте ки соли нав месозед, маълумоти соли пешина ДАР ҶОЙ мемонад!
                        Ҳама баҳоҳо, давомот, рейтингҳо ва transcript-ҳо нигоҳ дошта мешаванд.
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-calendar3 me-2"></i>Соли таҳсилӣ</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="2025-2026" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи оғоз <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи анҷом <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Ҳолат</label>
                            <select name="status" class="form-select">
                                <option value="planning">Банди кор</option>
                                <option value="active">Фаъол</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_current" class="form-check-input" id="is_current" value="1">
                                <label class="form-check-label" for="is_current">
                                    <strong>Ин соли ҷорист</strong><br>
                                    <small class="text-muted">(соли пешина ғайрифаъол мешавад)</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-bookmark me-2"></i>Семестри 1</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Оғоз <span class="text-danger">*</span></label>
                            <input type="date" name="sem1_start" class="form-control" value="{{ old('sem1_start') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷом <span class="text-danger">*</span></label>
                            <input type="date" name="sem1_end" class="form-control" value="{{ old('sem1_end') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Оғози сессия</label>
                            <input type="date" name="sem1_exam_start" class="form-control" value="{{ old('sem1_exam_start') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷоми сессия</label>
                            <input type="date" name="sem1_exam_end" class="form-control" value="{{ old('sem1_exam_end') }}">
                        </div>
                    </div>

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-bookmark me-2"></i>Семестри 2</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Оғоз <span class="text-danger">*</span></label>
                            <input type="date" name="sem2_start" class="form-control" value="{{ old('sem2_start') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷом <span class="text-danger">*</span></label>
                            <input type="date" name="sem2_end" class="form-control" value="{{ old('sem2_end') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Оғози сессия</label>
                            <input type="date" name="sem2_exam_start" class="form-control" value="{{ old('sem2_exam_start') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷоми сессия</label>
                            <input type="date" name="sem2_exam_end" class="form-control" value="{{ old('sem2_exam_end') }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('admin.structure.academic-years.index') }}" class="btn btn-outline-secondary">← Бозгашт</a>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg me-1"></i> Сохтани соли нав
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Тавзеҳот --}}
        <div class="card border-0 shadow-sm mt-3">
            <div class="card-header bg-white"><h6 class="mb-0"><i class="bi bi-question-circle me-2"></i>Чӣ мешавад вақте ки соли нав оғоз мешавад?</h6></div>
            <div class="card-body">
                <ol class="mb-0">
                    <li class="mb-2"><strong>Маълумоти кӯҳна НЕСТ НАМЕШАВАД</strong> — ҳама баҳоҳо, давомот, transcript дар DB мемонанд</li>
                    <li class="mb-2"><strong>Соли пешина</strong> → is_current=false, status=completed</li>
                    <li class="mb-2"><strong>Соли нав</strong> → is_current=true</li>
                    <li class="mb-2"><strong>Донишҷӯён</strong> → бо тугмаи "Гузаронидан ба курси нав" ба курси 2, 3... гузаронида мешаванд</li>
                    <li class="mb-2"><strong>Гурӯҳҳои нав</strong> → сохта мешаванд (мисол: ТИ-1-25)</li>
                    <li class="mb-2"><strong>Таъиноти омӯзгорон</strong> → барои семестри нав аз нав сохта мешавад</li>
                    <li class="mb-2"><strong>Ҳисоботҳо</strong> → бо филтр аз рӯйи сол/семестр кор мекунанд</li>
                    <li><strong>GPA кумулятивӣ</strong> → аз ТАМОМИ солҳо ҳисоб мешавад</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
