@extends('layouts.app')

@section('title', 'Таҳрири соли таҳсилӣ')
@section('page-header', 'Таҳрири соли таҳсилӣ')
@section('page-description', $academicYear->name)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
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
                <form method="POST" action="{{ route('admin.structure.academic-years.update', $academicYear) }}">
                    @csrf
                    @method('PUT')

                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-calendar3 me-2"></i>Соли таҳсилӣ</h6>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Ном <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $academicYear->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи оғоз <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $academicYear->start_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Санаи анҷом <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $academicYear->end_date->format('Y-m-d')) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Ҳолат <span class="text-danger">*</span></label>
                            <select name="status" class="form-select">
                                <option value="planning" {{ old('status', $academicYear->status) == 'planning' ? 'selected' : '' }}>Банди кор</option>
                                <option value="active" {{ old('status', $academicYear->status) == 'active' ? 'selected' : '' }}>Фаъол</option>
                                <option value="completed" {{ old('status', $academicYear->status) == 'completed' ? 'selected' : '' }}>Анҷомёфта</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="is_current" class="form-check-input" id="is_current" value="1"
                                       {{ old('is_current', $academicYear->is_current) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_current">
                                    <strong>Ин соли ҷорист</strong><br>
                                    <small class="text-muted">(дигар солҳо ғайриҷорӣ мешаванд)</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Семестрҳо --}}
                    @foreach($academicYear->semesters as $sem)
                    <h6 class="text-muted border-bottom pb-2 mb-3"><i class="bi bi-bookmark me-2"></i>{{ $sem->name }}</h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Оғоз</label>
                            <input type="date" name="semesters[{{ $sem->id }}][start_date]" class="form-control"
                                   value="{{ old("semesters.{$sem->id}.start_date", $sem->start_date->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷом</label>
                            <input type="date" name="semesters[{{ $sem->id }}][end_date]" class="form-control"
                                   value="{{ old("semesters.{$sem->id}.end_date", $sem->end_date->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Оғози сессия</label>
                            <input type="date" name="semesters[{{ $sem->id }}][exam_start_date]" class="form-control"
                                   value="{{ old("semesters.{$sem->id}.exam_start_date", $sem->exam_start_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Анҷоми сессия</label>
                            <input type="date" name="semesters[{{ $sem->id }}][exam_end_date]" class="form-control"
                                   value="{{ old("semesters.{$sem->id}.exam_end_date", $sem->exam_end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ҳолати семестр</label>
                            <select name="semesters[{{ $sem->id }}][status]" class="form-select form-select-sm">
                                <option value="planning" {{ $sem->status == 'planning' ? 'selected' : '' }}>Банди кор</option>
                                <option value="active" {{ $sem->status == 'active' ? 'selected' : '' }}>Фаъол</option>
                                <option value="exam_period" {{ $sem->status == 'exam_period' ? 'selected' : '' }}>Давраи сессия</option>
                                <option value="retake_period" {{ $sem->status == 'retake_period' ? 'selected' : '' }}>Такрорсупорӣ</option>
                                <option value="completed" {{ $sem->status == 'completed' ? 'selected' : '' }}>Анҷомёфта</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="semesters[{{ $sem->id }}][is_current]" class="form-check-input"
                                       id="sem_current_{{ $sem->id }}" value="1" {{ $sem->is_current ? 'checked' : '' }}>
                                <label class="form-check-label" for="sem_current_{{ $sem->id }}">Семестри ҷорӣ</label>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.structure.academic-years.index') }}" class="btn btn-outline-secondary">← Бозгашт</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Навсозӣ кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
