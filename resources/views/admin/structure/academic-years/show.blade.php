@extends('layouts.app')

@section('title', 'Соли таҳсилӣ: ' . $academicYear->name)
@section('page-header', 'Соли таҳсилӣ: ' . $academicYear->name)
@section('page-description')
    {{ $academicYear->start_date->format('d.m.Y') }} — {{ $academicYear->end_date->format('d.m.Y') }}
    @if($academicYear->is_current) | <span class="badge bg-primary">Ҷорӣ</span> @endif
@endsection

@section('page-actions')
    <a href="{{ route('admin.structure.academic-years.edit', $academicYear) }}" class="btn btn-outline-primary btn-sm">
        <i class="bi bi-pencil me-1"></i> Таҳрир
    </a>
@endsection

@section('content')
    {{-- Маълумоти умумӣ --}}
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Маълумот</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted">Ном:</td><td><strong>{{ $academicYear->name }}</strong></td></tr>
                        <tr><td class="text-muted">Оғоз:</td><td>{{ $academicYear->start_date->format('d.m.Y') }}</td></tr>
                        <tr><td class="text-muted">Анҷом:</td><td>{{ $academicYear->end_date->format('d.m.Y') }}</td></tr>
                        <tr>
                            <td class="text-muted">Ҳолат:</td>
                            <td>
                                @php
                                    $statusBadge = match($academicYear->status) {
                                        'active' => 'bg-success',
                                        'planning' => 'bg-warning',
                                        'completed' => 'bg-secondary',
                                        default => 'bg-secondary',
                                    };
                                    $statusLabel = match($academicYear->status) {
                                        'active' => 'Фаъол',
                                        'planning' => 'Банди кор',
                                        'completed' => 'Анҷомёфта',
                                        default => $academicYear->status,
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted">Ҷорӣ:</td>
                            <td>
                                @if($academicYear->is_current)
                                    <span class="badge bg-primary">Бале — ин соли ҷорист</span>
                                @else
                                    <span class="text-muted">Не</span>
                                @endif
                            </td>
                        </tr>
                        <tr><td class="text-muted">Гурӯҳҳо:</td><td><span class="badge bg-info">{{ $academicYear->groups->count() }}</span></td></tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Семестрҳо --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-bookmark me-2"></i>Семестрҳо</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Семестр</th>
                                <th>Оғоз</th>
                                <th>Анҷом</th>
                                <th>Сессия</th>
                                <th>Ҳолат</th>
                                <th>Ҷорӣ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($academicYear->semesters as $sem)
                                <tr class="{{ $sem->is_current ? 'table-primary' : '' }}">
                                    <td><strong>{{ $sem->name }}</strong></td>
                                    <td>{{ $sem->start_date->format('d.m.Y') }}</td>
                                    <td>{{ $sem->end_date->format('d.m.Y') }}</td>
                                    <td>
                                        @if($sem->exam_start_date && $sem->exam_end_date)
                                            {{ $sem->exam_start_date->format('d.m') }} — {{ $sem->exam_end_date->format('d.m.Y') }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $semStatusBadge = match($sem->status) {
                                                'active' => 'bg-success',
                                                'planning' => 'bg-warning',
                                                'exam_period' => 'bg-danger',
                                                'retake_period' => 'bg-info',
                                                'completed' => 'bg-secondary',
                                                default => 'bg-secondary',
                                            };
                                            $semStatusLabel = match($sem->status) {
                                                'active' => 'Фаъол',
                                                'planning' => 'Банди кор',
                                                'exam_period' => 'Сессия',
                                                'retake_period' => 'Такрорсупорӣ',
                                                'completed' => 'Анҷомёфта',
                                                default => $sem->status,
                                            };
                                        @endphp
                                        <span class="badge {{ $semStatusBadge }}">{{ $semStatusLabel }}</span>
                                    </td>
                                    <td>
                                        @if($sem->is_current)
                                            <i class="bi bi-check-circle-fill text-primary"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Гурӯҳҳо --}}
            @if($academicYear->groups->isNotEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="bi bi-people me-2"></i>Гурӯҳҳо дар ин сол ({{ $academicYear->groups->count() }})</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Гурӯҳ</th><th>Ихтисос</th><th>Ҳолат</th></tr>
                        </thead>
                        <tbody>
                            @foreach($academicYear->groups as $group)
                                <tr>
                                    <td><a href="/admin/structure/groups/{{ $group->id }}">{{ $group->name }}</a></td>
                                    <td><small>{{ $group->specialty?->name }}</small></td>
                                    <td>
                                        @if($group->is_active)
                                            <span class="badge bg-success">Фаъол</span>
                                        @else
                                            <span class="badge bg-secondary">Ғайрифаъол</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Тавзеҳот --}}
    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Маълумот дар бораи гузариш ба соли нав</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success"><i class="bi bi-check-circle me-2"></i>Вақте ки ин сол тамом мешавад:</h6>
                    <ul class="small">
                        <li>Ҳамаи баҳоҳо, давомот, рейтингҳо <strong>боқӣ мемонанд</strong></li>
                        <li>Transcript тамоми солҳоро дарбар мегирад</li>
                        <li>GPA кумулятивӣ аз ҳамаи солҳо ҳисоб мешавад</li>
                        <li>Ҳисоботҳо бо филтри сол/семестр кор мекунанд</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="bi bi-arrow-right-circle me-2"></i>Барои оғози соли нав:</h6>
                    <ul class="small">
                        <li>Соли навро созед (status=active, is_current=true)</li>
                        <li>Донишҷӯёнро ба курси нав гузаронед</li>
                        <li>Гурӯҳҳои навро барои курси 1 созед</li>
                        <li>Таъиноти омӯзгоронро аз нав кунед</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
