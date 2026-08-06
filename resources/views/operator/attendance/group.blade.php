@extends('layouts.app')

@section('title', 'Давомот — ' . $group->name)
@section('page-header', 'Давомот: ' . $group->name)
@section('page-description')
    {{ \Carbon\Carbon::parse($date)->format('d.m.Y — l') }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">
                <i class="bi bi-people me-2"></i>
                {{ $group->name }} — {{ $students->count() }} донишҷӯ
            </h6>
            <div>
                <button type="button" class="btn btn-sm btn-outline-success" id="markAllPresent">
                    <i class="bi bi-check-all me-1"></i> Ҳамаро ҳозир
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" id="markAllAbsent">
                    <i class="bi bi-x-lg me-1"></i> Ҳамаро ғоиб
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="POST" action="{{ route('operator.attendance.store', $group) }}">
                @csrf
                <input type="hidden" name="date" value="{{ $date }}">

                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Донишҷӯ</th>
                            <th class="text-center" style="width: 200px;">Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php
                                $currentStatus = $dailyAttendance[$student->id] ?? 'present';
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $student->user?->last_name }} {{ $student->user?->first_name }}</strong>
                                    <br><small class="text-muted">{{ $student->student_id_number }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <input type="radio" class="btn-check attendance-radio"
                                               name="attendance[{{ $student->id }}]" value="present"
                                               id="present_{{ $student->id }}"
                                               {{ $currentStatus === 'present' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-success btn-sm" for="present_{{ $student->id }}">
                                            <i class="bi bi-check-lg"></i> Ҳозир
                                        </label>

                                        <input type="radio" class="btn-check attendance-radio"
                                               name="attendance[{{ $student->id }}]" value="absent"
                                               id="absent_{{ $student->id }}"
                                               {{ $currentStatus === 'absent' ? 'checked' : '' }}>
                                        <label class="btn btn-outline-danger btn-sm" for="absent_{{ $student->id }}">
                                            <i class="bi bi-x-lg"></i> Ғоиб
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('operator.attendance.index', ['date' => $date]) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабт кардан
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-warning mt-3">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <strong>Диққат:</strong> Агар донишҷӯ «Ғоиб» гирифта шавад — дар ҳамаи дарсҳои ин рӯз автоматикӣ <strong>0</strong> мегирад.
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('markAllPresent')?.addEventListener('click', function() {
    document.querySelectorAll('input[value="present"]').forEach(r => r.checked = true);
});
document.getElementById('markAllAbsent')?.addEventListener('click', function() {
    document.querySelectorAll('input[value="absent"]').forEach(r => r.checked = true);
});
</script>
@endpush
