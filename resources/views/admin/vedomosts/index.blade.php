@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">

    @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3>Ведомостҳо</h3>
            <p class="text-muted mb-0">Идоракунии ведомостҳои имтиҳонӣ ва боргирии PDF / ZIP</p>
        </div>
        @if($groupId && $semesterId)
        <a href="{{ url('admin/vedomosts-zip') }}?group_id={{ $groupId }}&semester_id={{ $semesterId }}"
            class="btn btn-success">📦 Боргирии ҳама (ZIP)</a>
        @endif
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url('admin/vedomosts') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Соли хониш</label>
                    <select name="academic_year_id" class="form-select">
                        <option value="">— Интихоб кунед —</option>
                        @foreach($academicYears as $y)
                        <option value="{{ $y->id }}" {{ $yearId == $y->id ? 'selected' : '' }}>
                            {{ $y->name ?? ($y->start_year . '-' . ($y->start_year + 1)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Семестр</label>
                    <select name="semester_id" class="form-select">
                        <option value="">— Интихоб кунед —</option>
                        @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ $semesterId == $s->id ? 'selected' : '' }}>
                            {{ $s->name ?? ('Семестри ' . ($s->number ?? $s->id)) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Гурӯҳ</label>
                    <select name="group_id" class="form-select">
                        <option value="">— Интихоб кунед —</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}" {{ $groupId == $g->id ? 'selected' : '' }}>
                            {{ $g->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Нишон додан</button>
                </div>
            </form>
        </div>
    </div>

    @if($groupId && $semesterId)
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Фан</th>
                        <th>Омӯзгор</th>
                        <th>Кредит</th>
                        <th>Санаи имтиҳон</th>
                        <th>Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vedomosts as $v)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $v->subject->name ?? '-' }}</td>
                        <td>{{ $v->teacher->name ?? '-' }}</td>
                        <td>{{ $v->subject->credits ?? '-' }}</td>
                        <td>{{ $v->exam_date ? $v->exam_date->format('d.m.Y') : '—' }}</td>
                        <td>
                            <a href="{{ url('admin/vedomosts/' . $v->id . '/preview') }}" target="_blank"
                                class="btn btn-sm btn-info">👁 Дидан</a>
                            <a href="{{ url('admin/vedomosts/' . $v->id . '/pdf') }}"
                                class="btn btn-sm btn-primary">📥 PDF</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Ведомост ёфт нашуд</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection