@extends('layouts.app')

@section('title', 'Transcript / GPA')
@section('page-header', 'Transcript ва GPA')
@section('page-description', 'Рӯйхати донишҷӯён бо GPA')

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Ном ё рақами дон..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="group_id" class="form-select">
                        <option value="">Ҳама гурӯҳҳо</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i> Ҷустуҷӯ</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Ихтисос</th>
                        <th>Курс</th>
                        <th>GPA</th>
                        <th>Кредитҳо</th>
                        <th>Қарз</th>
                        <th class="text-end">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($students as $index => $student)
                        <tr>
                            <td>{{ $students->firstItem() + $index }}</td>
                            <td><a href="{{ route('admin.transcript.show', $student) }}">{{ $student->user?->full_name }}</a></td>
                            <td><span class="badge bg-info">{{ $student->group?->name }}</span></td>
                            <td><small>{{ $student->specialty?->name }}</small></td>
                            <td>{{ $student->course?->number }}</td>
                            <td>
                                <strong class="{{ $student->cumulative_gpa >= 3.0 ? 'text-success' : ($student->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($student->cumulative_gpa, 2) }}
                                </strong>
                            </td>
                            <td>{{ $student->total_credits_earned }} / {{ $student->specialty?->total_credits ?? '—' }}</td>
                            <td>
                                @if($student->has_debts)
                                    <span class="badge bg-danger">Ҳа</span>
                                @else
                                    <span class="badge bg-success">Не</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.transcript.show', $student) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text"></i> Transcript
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($students->hasPages())
            <div class="card-footer bg-white">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
