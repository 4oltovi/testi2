@extends('layouts.app')

@section('title', 'Гузариши донишҷӯён')
@section('page-header', 'Гузариши донишҷӯён')
@section('page-description', 'Таърихи гузаришҳои донишҷӯён')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Гузариши донишҷӯён</h6>
                <a href="{{ route('admin.student-transfers.create') }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Гузариши нав
                </a>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.student-transfers.index') }}" class="row g-3 mb-3">
                    <div class="col-md-4">
                        <select name="student_id" class="form-select">
                            <option value="">Ҳамаи донишҷӯён</option>
                            @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->user?->short_name ?? 'Донишҷӯ #' . $student->id }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="from_specialty_id" class="form-select">
                            <option value="">Ихтисоси қаблӣ</option>
                            @foreach($specialties as $specialty)
                            <option value="{{ $specialty->id }}" {{ request('from_specialty_id') == $specialty->id ? 'selected' : '' }}>
                                {{ $specialty->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="to_specialty_id" class="form-select">
                            <option value="">Ихтисоси нав</option>
                            @foreach($specialties as $specialty)
                            <option value="{{ $specialty->id }}" {{ request('to_specialty_id') == $specialty->id ? 'selected' : '' }}>
                                {{ $specialty->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search me-1"></i> Ҷустуҷӯ
                        </button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>№</th>
                                <th>Донишҷӯ</th>
                                <th>Ихтисоси қаблӣ</th>
                                <th>Гурӯҳи қаблӣ</th>
                                <th>Ихтисоси нав</th>
                                <th>Гурӯҳи нав</th>
                                <th>Санаи гузариш</th>
                                <th>Амал</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transfers as $transfer)
                            <tr>
                                <td>{{ $loop->iteration + ($transfers->currentPage() - 1) * $transfers->perPage() }}</td>
                                <td>{{ $transfer->student->user?->short_name ?? 'Донишҷӯ #' . $transfer->student->id }}</td>
                                <td>{{ $transfer->fromSpecialty->name ?? '-' }}</td>
                                <td>{{ $transfer->fromGroup->name ?? '-' }}</td>
                                <td>{{ $transfer->toSpecialty->name ?? '-' }}</td>
                                <td>{{ $transfer->toGroup->name ?? '-' }}</td>
                                <td>{{ $transfer->transfer_date?->format('d.m.Y') ?? '-' }}</td>
                                <td>
                                    <a href="{{ route('admin.student-transfers.show', $transfer) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="bi bi-arrow-left-right fs-1 d-block mb-2"></i>
                                    Ҳанӯз гузариш сабт нашудааст.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $transfers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
