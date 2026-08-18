@extends('layouts.app')

@section('title', 'Илова кардани таъинӣ')
@section('page-header', 'Илова кардани таъинӣ')
@section('page-description', 'Фан, омӯзгор, гурӯҳ ва семестрро интихоб кунед')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.journal.assignments.store') }}">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Фан <span class="text-danger">*</span></label>
                    <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                        <option value="">— Фанро интихоб кунед —</option>
                        @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" {{ old('subject_id') == $subject->id ? 'selected' : '' }}>
                            {{ $subject->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('subject_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-2">
                    <label class="form-label">Кредит (ихтиёрӣ)</label>
                    <input type="number" name="credits" min="1" max="30" class="form-control"
                        placeholder="аз фан">
                </div>

                <div class="col-md-6">
                    <label class="form-label">Омӯзгор <span class="text-danger">*</span></label>
                    <select name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                        <option value="">— Омӯзгорро интихоб кунед —</option>
                        @foreach($teachers as $teacher)
                        <option value="{{ $teacher->user_id }}" {{ old('teacher_id') == $teacher->user_id ? 'selected' : '' }}>
                            {{ $teacher->user?->full_name ?? $teacher->user?->login }}
                        </option>
                        @endforeach
                    </select>
                    @error('teacher_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12">
                    <label class="form-label">Гурӯҳҳо <span class="text-danger">*</span></label>
                    <div class="border rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <small class="text-muted">Якчанд гурӯҳро интихоб кунед</small>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-groups">
                                Ҳамаи гурӯҳҳо
                            </button>
                        </div>

                        <div class="row g-2">
                            @foreach($groups as $group)
                            <div class="col-md-4 col-lg-3">
                                <div class="form-check border rounded p-2 bg-white h-100">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="group_ids[]"
                                        value="{{ $group->id }}"
                                        id="group-{{ $group->id }}"
                                        {{ in_array($group->id, old('group_ids', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label ms-2" for="group-{{ $group->id }}">
                                        {{ $group->name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @error('group_ids')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Семестр <span class="text-danger">*</span></label>
                    <select name="semester_id" class="form-select @error('semester_id') is-invalid @enderror" required>
                        <option value="">— Семестрро интихоб кунед —</option>
                        @foreach($semesters as $semester)
                        <option value="{{ $semester->id }}" {{ old('semester_id') == $semester->id ? 'selected' : '' }}>
                            {{ $semester->name }} — {{ $semester->academicYear?->name }}
                            {{ $semester->is_current ? '(ҷорӣ)' : '' }}
                        </option>
                        @endforeach
                    </select>
                    @error('semester_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Навъи дарс</label>
                    <select name="lesson_type" class="form-select">
                        <option value="lecture" {{ old('lesson_type') == 'lecture' ? 'selected' : '' }}>Лексия</option>
                        <option value="practice" {{ old('lesson_type', 'practice') == 'practice' ? 'selected' : '' }}>Амалӣ</option>
                        <option value="lab" {{ old('lesson_type') == 'lab' ? 'selected' : '' }}>Лабораторӣ</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Соат/ҳафта</label>
                    <input type="number" name="hours_per_week" class="form-control" min="1" max="20" value="{{ old('hours_per_week', 2) }}">
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардан
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllBtn = document.getElementById('select-all-groups');
        if (!selectAllBtn) return;

        let allSelected = false;

        selectAllBtn.addEventListener('click', function() {
            const boxes = document.querySelectorAll('input[name="group_ids[]"]');
            boxes.forEach(function(box) {
                box.checked = !allSelected;
            });
            allSelected = !allSelected;
            selectAllBtn.textContent = allSelected ? 'Лакирандаи гурӯҳҳо' : 'Ҳамаи гурӯҳҳо';
        });
    });
</script>
@endsection