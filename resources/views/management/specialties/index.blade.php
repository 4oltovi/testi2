@extends('layouts.app')

@section('title', 'Ихтисосҳо')
@section('page-header', 'Ихтисосҳо (Специальности)')
@section('page-description', 'Рӯйхати ихтисосҳои ' . ($faculty?->name ?? 'факултет'))

@section('content')
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control" placeholder="Ном ё рамзи ихтисос..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search me-1"></i> Ҷустуҷӯ</button>
                    <a href="{{ route('management.specialties.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ихтисос</th>
                            <th>Рамз</th>
                            <th>Кафедра / Факултет</th>
                            <th>Сатҳ</th>
                            <th>Муддат</th>
                            <th>Кредитҳо</th>
                            <th>Гурӯҳҳо</th>
                            <th>Донишҷӯён</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($specialties as $spec)
                            <tr>
                                <td>
                                    <a href="{{ route('management.specialties.show', $spec) }}" class="fw-semibold text-decoration-none">
                                        {{ $spec->name }}
                                    </a>
                                </td>
                                <td><code>{{ $spec->code }}</code></td>
                                <td><small>{{ $spec->department?->name }} / {{ $spec->department?->faculty?->short_name }}</small></td>
                                <td>
                                    @php
                                        $levelLabel = match($spec->education_level) {
                                            'bachelor' => 'Бакалавр',
                                            'master' => 'Магистр',
                                            'specialist' => 'Мутахассис',
                                            default => $spec->education_level,
                                        };
                                    @endphp
                                    <small>{{ $levelLabel }}</small>
                                </td>
                                <td>{{ $spec->study_years }} сол</td>
                                <td><span class="badge bg-primary">{{ $spec->total_credits }}</span></td>
                                <td><span class="badge bg-info">{{ $spec->groups_count }}</span></td>
                                <td><span class="badge bg-success">{{ $spec->students_count }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('management.specialties.show', $spec) }}" class="btn btn-sm btn-outline-info" title="Дидан">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Ихтисосе ёфт нашуд.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($specialties->hasPages())
            <div class="card-footer bg-white">{{ $specialties->links() }}</div>
        @endif
    </div>
@endsection
