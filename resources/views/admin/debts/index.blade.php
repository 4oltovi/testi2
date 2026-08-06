@extends('layouts.app')

@section('title', 'Қарздориҳо')
@section('page-header', 'Қарздории академӣ')
@section('page-description', 'Идоракунии қарздориҳои донишҷӯён')

@section('content')
    {{-- Омор --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm border-danger border-start border-3">
                <div class="card-body"><h4 class="text-danger mb-0">{{ $stats['total_open'] }}</h4><small class="text-muted">Қарздориҳои кушод</small></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body"><h4 class="mb-0">{{ $stats['active'] }}</h4><small class="text-muted">Фаъол</small></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body"><h4 class="text-warning mb-0">{{ $stats['retake_scheduled'] }}</h4><small class="text-muted">Такрорсупорӣ таъин</small></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body"><h4 class="text-success mb-0">{{ $stats['resolved_this_month'] }}</h4><small class="text-muted">Ҳалшуда (ин моҳ)</small></div>
            </div>
        </div>
    </div>

    {{-- Филтр --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <input type="text" name="search" class="form-control" placeholder="Ном..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Кушодҳо</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                        <option value="retake_scheduled" {{ request('status') == 'retake_scheduled' ? 'selected' : '' }}>Такрорсупорӣ</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Ҳалшуда</option>
                        <option value="escalated" {{ request('status') == 'escalated' ? 'selected' : '' }}>Комиссия</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="group_id" class="form-select">
                        <option value="">Ҳама гурӯҳҳо</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвал --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>Фан</th>
                            <th>Сабаб</th>
                            <th>Баҳо</th>
                            <th>Санаи қарз</th>
                            <th>Кӯшиш</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debts as $debt)
                            <tr>
                                <td><a href="{{ route('admin.students.show', $debt->student) }}">{{ $debt->student?->user?->short_name }}</a></td>
                                <td><span class="badge bg-info">{{ $debt->student?->group?->name }}</span></td>
                                <td><small>{{ $debt->subject?->name }}</small></td>
                                <td><small>{{ $debt->reason_label }}</small></td>
                                <td><span class="badge bg-danger">{{ $debt->original_grade }}</span> ({{ $debt->original_score }}%)</td>
                                <td><small>{{ $debt->debt_date?->format('d.m.Y') }}</small></td>
                                <td>{{ $debt->retake_attempts_used }}/{{ $debt->max_retake_attempts }}</td>
                                <td><span class="badge {{ $debt->status->badgeClass() }}">{{ $debt->status->label() }}</span></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.debts.show', $debt) }}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                                    @if($debt->canRetake())
                                        <form action="{{ route('admin.debts.schedule-retake', $debt) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-warning" title="Такрорсупорӣ"><i class="bi bi-arrow-repeat"></i></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-4">Қарздорӣ ёфт нашуд.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($debts->hasPages())
            <div class="card-footer bg-white">{{ $debts->links() }}</div>
        @endif
    </div>
@endsection
