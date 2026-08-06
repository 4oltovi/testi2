@extends('layouts.app')

@section('title', 'Давомот')
@section('page-header', 'Давомоти рӯзона')
@section('page-description', 'Як бор дар рӯз барои ҳар гурӯҳ ҳозир/ғоиб мегузоред')

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
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Филтр
                    </button>
                </div>
                <div class="col-md-7 text-end">
                    <span class="badge bg-light text-dark fs-6">
                        <i class="bi bi-calendar-event me-1"></i>
                        {{ \Carbon\Carbon::parse($date)->format('d.m.Y — l') }}
                    </span>
                </div>
            </form>
        </div>
    </div>

    {{-- Рӯйхати гурӯҳҳо --}}
    <div class="row g-3">
        @foreach($groups as $group)
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center">
                        <h6 class="fw-bold">{{ $group->name }}</h6>
                        <p class="text-muted small mb-2">
                            {{ $group->specialty?->name }}
                        </p>
                        <span class="badge bg-info">{{ $group->active_students_count }} донишҷӯ</span>
                    </div>
                    <div class="card-footer bg-white border-0 text-center">
                        <a href="{{ route('operator.attendance.group', ['group' => $group, 'date' => $date]) }}"
                           class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-check2-square me-1"></i> Давомот
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
