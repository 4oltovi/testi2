@extends('layouts.app')

@section('title', 'Гузариши донишҷӯ — ' . $transfer->student->user?->short_name)
@section('page-header', 'Гузариши донишҷӯ')
@section('page-description', 'Маълумоти гузариш')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Гузариши донишҷӯ</h6>
                <a href="{{ route('admin.student-transfers.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Донишҷӯ:</strong> {{ $transfer->student->user?->short_name ?? 'Донишҷӯ #' . $transfer->student->id }}
                    </div>
                    <div class="col-md-6">
                        <strong>Санаи гузариш:</strong> {{ $transfer->transfer_date?->format('d.m.Y') ?? '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Ихтисоси қаблӣ:</strong> {{ $transfer->fromSpecialty->name ?? '-' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Ихтисоси нав:</strong> {{ $transfer->toSpecialty->name ?? '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Гурӯҳи қаблӣ:</strong> {{ $transfer->fromGroup->name ?? '-' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Гурӯҳи нав:</strong> {{ $transfer->toGroup->name ?? '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Рақами фармон:</strong> {{ $transfer->order_number ?? '-' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Сабаб:</strong> {{ $transfer->reason ?? '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <strong>Тавзеҳот:</strong> {{ $transfer->note ?? '-' }}
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Сабт карда:</strong> {{ $transfer->createdBy?->short_name ?? 'Номаълум' }}
                    </div>
                    <div class="col-md-6">
                        <strong>Вақт:</strong> {{ $transfer->created_at?->format('d.m.Y H:i') ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
