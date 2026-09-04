@extends('layouts.app')

@section('title', 'Давомот')
@section('page-header', 'ДАВОМОТ')
@section('page-description', 'Гурӯҳро интихоб кунед барои гузоштани давомот')

@section('page-actions')
    <a href="{{ route('operator.attendance.statistics') }}" class="btn btn-primary btn-sm" style="height: 44px; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.3);">
        <i class="bi bi-bar-chart-line me-1"></i> Омора
    </a>
@endsection

@section('content')
    <div class="row g-3">
        @foreach($groups as $group)
            <div class="col-md-4 col-lg-3">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5; transition: all 0.2s ease; cursor: pointer;" onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.08)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 1px 3px 0 rgba(0, 0, 0, 0.1)';">
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-people-fill text-primary fs-2"></i>
                        </div>
                        <h5 class="fw-bold mb-1">{{ $group->name }}</h5>
                        <p class="text-muted small mb-3" style="font-size: 0.85rem;">
                            {{ $group->specialty?->name ?? '—' }}
                        </p>
                        <div class="badge bg-light text-dark rounded-pill mb-3" style="font-size: 0.85rem; padding: 0.5em 0.8em;">
                            <i class="bi bi-person me-1"></i>{{ $group->active_students_count }} донишҷӯ
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 text-center pb-3">
                        <a href="{{ route('operator.attendance.group', $group) }}?date={{ now()->format('Y-m-d') }}"
                           class="btn btn-primary w-100" style="border-radius: 10px; font-weight: 600; padding: 0.6rem;">
                            <i class="bi bi-check2-square me-1"></i> Давомот
                        </a>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
