@extends('layouts.app')

@section('title', 'Давомот')
@section('page-header', 'Давомот')
@section('page-description')
    {{ $subjectAssignment->curriculum?->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    {{-- Интихоби сана --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Сана</label>
                    <input type="date" name="date" class="form-control" value="{{ $date }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Дарси №</label>
                    <select name="lesson_number" class="form-select">
                        @for($i = 1; $i <= 6; $i++)
                            <option value="{{ $i }}" {{ $lessonNumber == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">Нишон деҳ</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Формаи давомот --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between">
            <h6 class="mb-0"><i class="bi bi-check2-square me-2"></i> Давомот: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}, Дарси {{ $lessonNumber }}</h6>
            <span class="badge bg-info">{{ $students->count() }} донишҷӯ</span>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="{{ route('admin.journal.attendance.store', $subjectAssignment) }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">
                <input type="hidden" name="lesson_number" value="{{ $lessonNumber }}">

                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th width="35%">Донишҷӯ</th>
                            <th width="35%">Ҳолат</th>
                            <th width="25%">Давомот (умумӣ)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $currentStatus = $existingAttendance[$student->id] ?? 'present';
                                $stats = $attendanceStats[$student->id] ?? null;
                                $percentage = $stats ? round(($stats->present_count / max($stats->total, 1)) * 100) : 100;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->user?->full_name }}</td>
                                <td>
                                    <div class="btn-group btn-group-sm w-100" role="group">
                                        @foreach(\App\Enums\AttendanceStatus::cases() as $status)
                                            <input type="radio"
                                                   class="btn-check"
                                                   name="attendance[{{ $student->id }}]"
                                                   id="att_{{ $student->id }}_{{ $status->value }}"
                                                   value="{{ $status->value }}"
                                                   {{ $currentStatus === $status->value ? 'checked' : '' }}>
                                            <label class="btn btn-outline-{{ match($status->value) {
                                                'present' => 'success',
                                                'absent' => 'danger',
                                                'excused' => 'info',
                                                'late' => 'warning',
                                                'sick' => 'secondary',
                                            } }}" for="att_{{ $student->id }}_{{ $status->value }}"
                                                   title="{{ $status->label() }}">
                                                {{ $status->shortLabel() }}
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    @if($stats)
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $percentage >= 75 ? 'bg-success' : 'bg-danger' }}"
                                                 style="width: {{ $percentage }}%">{{ $percentage }}%</div>
                                        </div>
                                        <small class="text-muted">{{ $stats->present_count }}/{{ $stats->total }} (Ғ: {{ $stats->absent_count }})</small>
                                    @else
                                        <small class="text-muted">Маълумот нест</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('admin.journal.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Сабт кардан
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
