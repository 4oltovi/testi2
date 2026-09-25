@extends('layouts.app')

@section('title', 'Таҳрири журнал')
@section('page-header', 'Таҳрири таъиноти журнал')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('admin.journal.assignments.update', $subjectAssignment) }}">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Фан <span class="text-danger">*</span></label>
                            <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                @foreach($subjects as $subject)
                                <option value="{{ $subject->id }}" {{ $subjectAssignment->subject_id == $subject->id ? 'selected' : '' }}>
                                    {{ $subject->name }} ({{ $subject->code }})
                                </option>
                                @endforeach
                            </select>
                            @error('subject_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Омӯзгор <span class="text-danger">*</span></label>
                            <select name="teacher_id" class="form-select @error('teacher_id') is-invalid @enderror" required>
                                @foreach($teachers as $teacher)
                                <option value="{{ $teacher->user_id }}" {{ $subjectAssignment->teacher_id == $teacher->user_id ? 'selected' : '' }}>
                                    {{ $teacher->user?->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('teacher_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Гурӯҳ <span class="text-danger">*</span></label>
                            <select name="group_id" class="form-select @error('group_id') is-invalid @enderror" required>
                                @foreach($groups as $group)
                                <option value="{{ $group->id }}" {{ $subjectAssignment->group_id == $group->id ? 'selected' : '' }}>
                                    {{ $group->full_name }}
                                </option>
                                @endforeach
                            </select>
                            @error('group_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Семестр <span class="text-danger">*</span></label>
                            <select name="semester_id" class="form-select @error('semester_id') is-invalid @enderror" required>
                                @foreach($semesters as $semester)
                                <option value="{{ $semester->id }}" {{ $subjectAssignment->semester_id == $semester->id ? 'selected' : '' }}>
                                    {{ $semester->name }} ({{ $semester->academicYear?->name }})
                                </option>
                                @endforeach
                            </select>
                            @error('semester_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Навъи дарс <span class="text-danger">*</span></label>
                            <select name="lesson_type" class="form-select @error('lesson_type') is-invalid @enderror" required>
                                <option value="lecture" {{ $subjectAssignment->lesson_type == 'lecture' ? 'selected' : '' }}>Лексия</option>
                                <option value="practice" {{ $subjectAssignment->lesson_type == 'practice' ? 'selected' : '' }}>Амалӣ</option>
                                <option value="lab" {{ $subjectAssignment->lesson_type == 'lab' ? 'selected' : '' }}>Лабораторӣ</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Кредит <span class="text-danger">*</span></label>
                            <input type="number" name="credits" value="{{ old('credits', $subjectAssignment->credits) }}" class="form-control" required min="1" max="30">
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary">Бозгашт</a>
                        <button type="submit" class="btn btn-primary">Захира кардан</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
