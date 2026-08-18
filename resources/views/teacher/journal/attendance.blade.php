@extends('layouts.app')

@section('title', 'Давомот')
@section('page-header', 'Сабти давомот')
@section('page-description')
    {{ $subjectAssignment->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Сана</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Дарс №</label>
                    <select name="lesson_number" class="form-select">
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ $lessonNumber == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary">Нишон деҳ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}, Дарси {{ $lessonNumber }} — {{ $students->count() }} донишҷӯ</h6>
        </div>
        <form method="POST" action="{{ route('teacher.journal.attendance.store', $subjectAssignment) }}">
            @csrf
            <input type="hidden" name="date" value="{{ $date }}">
            <input type="hidden" name="lesson_number" value="{{ $lessonNumber }}">

            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php $current = $existingAttendance[$student->id] ?? 'present'; @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->user?->full_name }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group">
                                        @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                                            <input type="radio" class="btn-check"
                                                   name="attendance[{{ $student->id }}]"
                                                   id="a_{{ $student->id }}_{{ $status->value }}"
                                                   value="{{ $status->value }}"
                                                   {{ $current === $status->value ? 'checked' : '' }}>
                                            <label class="btn btn-outline-{{ match($status->value) {
                                                'present' => 'success', 'absent' => 'danger',
                                                'excused' => 'info', 'late' => 'warning', 'sick' => 'secondary'
                                            } }}" for="a_{{ $student->id }}_{{ $status->value }}">
                                                {{ $status->shortLabel() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('teacher.journal.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-lg me-1"></i> Сабт кардан
                </button>
            </div>
        </form>
    </div>
@endsection
