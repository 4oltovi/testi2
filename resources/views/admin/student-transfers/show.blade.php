@extends('layouts.app')

@section('title', 'Гузариши донишҷӯ — ' . $transfer->student->user?->short_name)
@section('page-header', 'Гузариши донишҷӯ')
@section('page-description', 'Маълумоти гузариш')

@section('content')
<div class="row g-4">
    <div class="col-12">
        {{-- Header Card --}}
        <div class="card border-0 shadow-md" style="border-radius: 1rem; background: linear-gradient(135deg, #ffffff 0%, #eef2ff 50%, #e0f2fe 100%);">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h4 class="fw-bold mb-1" style="color: #1e293b;">
                            Гузариши донишҷӯ
                            <i class="bi bi-arrow-left-right ms-2" style="color: #22d3ee;"></i>
                        </h4>
                        <p class="text-muted mb-0">Маълумоти гузариш</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.student-transfers.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-three-dots"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Student Profile --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 1rem;">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white fw-bold" style="width: 48px; height: 48px; font-size: 1.1rem;">
                    {{ mb_substr($transfer->student->user?->short_name ?? 'Д', 0, 1) }}
                </div>
                <div>
                    <div class="fw-semibold">ДОНИШҶӮ: {{ $transfer->student->user?->short_name ?? 'Донишҷӯ #' . $transfer->student->id }}</div>
                    <div class="text-muted small">Маълумоти шахсӣ</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Comparative Cards --}}
    <div class="col-12">
        <div class="row g-3">
            {{-- Left Column --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 1rem; border: 1px solid #e8edf5;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-primary">
                            <i class="bi bi-arrow-left me-2"></i> Маълумоти қаблӣ
                        </h6>

                        {{-- Specialty Card --}}
                        <div class="card mb-3" style="border-radius: 0.75rem; border: 1px solid #e0e7ff; background: #f8faff;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-file-earmark-text text-primary"></i>
                                    <span class="fw-semibold">Ихтисоси қаблӣ</span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">{{ $transfer->fromSpecialty->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $transfer->fromSpecialty->code ?? '' }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Group Card --}}
                        <div class="card mb-3" style="border-radius: 0.75rem; border: 1px solid #e0e7ff; background: #f8faff;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-arrow-left-circle text-primary"></i>
                                    <span class="fw-semibold">Гурӯҳи қаблӣ</span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">{{ $transfer->fromGroup->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $transfer->fromGroup->specialty?->name ?? '' }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 rounded" style="background: #f8faff; border: 1px solid #e0e7ff;">
                                    <small class="text-muted d-block mb-1">Рақами фармон</small>
                                    <span class="fw-semibold">{{ $transfer->order_number ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded" style="background: #f8faff; border: 1px solid #e0e7ff;">
                                    <small class="text-muted d-block mb-1">Сабт карда</small>
                                    <span class="fw-semibold">{{ $transfer->createdBy?->short_name ?? 'Номаълум' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 p-3 rounded" style="background: #f8faff; border: 1px solid #e0e7ff;">
                            <small class="text-muted d-block mb-1">Тавзеҳот</small>
                            <span>{{ $transfer->note ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100" style="border-radius: 1rem; border: 1px solid #e0f2fe; background: #f0f9ff;">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3 text-info">
                            <i class="bi bi-arrow-right me-2"></i> Маълумоти нав
                        </h6>

                        {{-- Transfer Date --}}
                        <div class="card mb-3" style="border-radius: 0.75rem; border: 1px solid #c7d2fe; background: #ffffff;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-calendar-event text-info"></i>
                                    <span class="fw-semibold">Санаи гузариш</span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">{{ $transfer->transfer_date?->format('d.m.Y') ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Specialty Card --}}
                        <div class="card mb-3" style="border-radius: 0.75rem; border: 1px solid #c7d2fe; background: #ffffff;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-pencil-square text-info"></i>
                                    <span class="fw-semibold">Ихтисоси нав</span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">{{ $transfer->toSpecialty->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $transfer->toSpecialty->code ?? '' }}</small>
                                </div>
                            </div>
                        </div>

                        {{-- Group Card --}}
                        <div class="card mb-3" style="border-radius: 0.75rem; border: 1px solid #c7d2fe; background: #ffffff;">
                            <div class="card-body py-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <i class="bi bi-arrow-right-circle text-info"></i>
                                    <span class="fw-semibold">Гурӯҳи нав</span>
                                </div>
                                <div class="ps-4">
                                    <div class="fw-semibold">{{ $transfer->toGroup->name ?? '-' }}</div>
                                    <small class="text-muted">{{ $transfer->toGroup->specialty?->name ?? '' }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <div class="p-3 rounded" style="background: #ffffff; border: 1px solid #c7d2fe;">
                                    <small class="text-muted d-block mb-1">Сабаб</small>
                                    <span class="fw-semibold">{{ $transfer->reason ?? '-' }}</span>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded" style="background: #ffffff; border: 1px solid #c7d2fe;">
                                    <small class="text-muted d-block mb-1">Вақт</small>
                                    <span class="fw-semibold">{{ $transfer->created_at?->format('d.m.Y H:i') ?? '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
