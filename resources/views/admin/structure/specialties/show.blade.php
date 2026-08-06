@extends('layouts.app')

@section('title', 'Маълумоти ихтисос: ' . $specialty->name)
@section('page-header', 'Маълумоти ихтисос')
@section('page-description', $specialty->name)

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- Маълумоти асосӣ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумоти асосӣ</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Рамз</th>
                            <td>{{ $specialty->code }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кафедра</th>
                            <td>{{ $specialty->department?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Факултет</th>
                            <td>{{ $specialty->department?->faculty?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Сатҳ</th>
                            <td>
                                @switch($specialty->education_level)
                                    @case('bachelor') Бакалавр @break
                                    @case('master') Магистр @break
                                    @case('specialist') Мутахассис @break
                                    @default {{ $specialty->education_level }}
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Муддат</th>
                            <td>{{ $specialty->study_years }} сол</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кредитҳо</th>
                            <td>{{ $specialty->total_credits }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Шакл</th>
                            <td>
                                @switch($specialty->study_form)
                                    @case('full_time') Рӯзона @break
                                    @case('part_time') Ғоибона @break
                                    @case('evening') Шабона @break
                                    @default {{ $specialty->study_form }}
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳолат</th>
                            <td>
                                @if($specialty->is_active)
                                    <span class="badge bg-success">Фаъол</span>
                                @else
                                    <span class="badge bg-secondary">Ғайрифаъол</span>
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Гурӯҳҳо --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-people me-2"></i> Гурӯҳҳо</h6>
            </div>
            <div class="card-body">
                @if($specialty->groups && $specialty->groups->count() > 0)
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Ном</th>
                                <th>Курс</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($specialty->groups as $group)
                                <tr>
                                    <td>{{ $group->name }}</td>
                                    <td>{{ $group->course->name }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0">Гурӯҳҳо ёфт нашуданд</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <a href="{{ route('admin.structure.specialties.edit', $specialty) }}" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Таҳрир кардан
                </a>
                <a href="{{ route('admin.structure.specialties.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
