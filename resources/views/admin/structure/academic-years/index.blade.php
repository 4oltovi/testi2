@extends('layouts.app')

@section('title', 'Солҳои таҳсилӣ')
@section('page-header', 'Солҳои таҳсилӣ')
@section('page-description', 'Идоракунии солҳо ва семестрҳои таҳсилӣ')

@section('page-actions')
    <a href="{{ route('admin.structure.academic-years.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Соли нав
    </a>
@endsection

@section('content')
    <div class="row g-4">
        @forelse($academicYears as $year)
            <div class="col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm h-100 {{ $year->is_current ? 'border-primary border-2' : '' }}">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold">
                            {{ $year->name }}
                            @if($year->is_current)
                                <span class="badge bg-primary ms-1">Ҷорӣ</span>
                            @endif
                        </h6>
                        @php
                            $statusBadge = match($year->status) {
                                'active' => 'bg-success',
                                'planning' => 'bg-warning',
                                'completed' => 'bg-secondary',
                                default => 'bg-secondary',
                            };
                            $statusLabel = match($year->status) {
                                'active' => 'Фаъол',
                                'planning' => 'Дар банд',
                                'completed' => 'Анҷомёфта',
                                default => $year->status,
                            };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ $year->start_date->format('d.m.Y') }} — {{ $year->end_date->format('d.m.Y') }}
                        </p>

                        @foreach($year->semesters as $sem)
                            <div class="border rounded p-2 mb-2 {{ $sem->is_current ? 'bg-primary bg-opacity-10 border-primary' : '' }}">
                                <strong class="d-block">{{ $sem->name }}
                                    @if($sem->is_current) <span class="badge bg-primary">Ҷорӣ</span> @endif
                                </strong>
                                <small class="text-muted">
                                    {{ $sem->start_date->format('d.m') }} — {{ $sem->end_date->format('d.m.Y') }}
                                </small>
                            </div>
                        @endforeach
                    </div>
                    <div class="card-footer bg-white text-end">
                        <a href="{{ route('admin.structure.academic-years.edit', $year) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil me-1"></i> Таҳрир
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    Соли таҳсилӣ мавҷуд нест.
                </div>
            </div>
        @endforelse
    </div>

    @if($academicYears->hasPages())
        <div class="mt-4">{{ $academicYears->links() }}</div>
    @endif
@endsection
