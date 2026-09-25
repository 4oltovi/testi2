@extends('layouts.app')

@section('title', 'Қарздориҳо')
@section('page-header', 'Қарздории академӣ')
@section('page-description', 'Идоракунии қарздориҳои донишҷӯён')

@section('content')

<style>
    .debts-page .stat-card {
        border: none;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 2px 10px rgba(20, 20, 43, 0.06);
        transition: transform .18s ease, box-shadow .18s ease;
        overflow: hidden;
        position: relative;
        height: 100%;
    }

    .debts-page .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 24px rgba(20, 20, 43, 0.1);
    }

    .debts-page .stat-card .stat-icon {
        position: absolute;
        right: 12px;
        top: 12px;
        font-size: 2.1rem;
        opacity: .15;
    }

    .debts-page .stat-card .stat-value {
        font-size: 1.9rem;
        font-weight: 700;
        line-height: 1;
    }

    .debts-page .stat-card .stat-label {
        font-size: .8rem;
        color: #8a8fa3;
        font-weight: 500;
        margin-top: 6px;
        display: block;
    }

    .debts-page .stat-card.accent-danger {
        border-top: 4px solid #e5484d;
    }

    .debts-page .stat-card.accent-neutral {
        border-top: 4px solid #e2e4ea;
    }

    .debts-page .stat-card.accent-warning {
        border-top: 4px solid #f5a524;
    }

    .debts-page .stat-card.accent-success {
        border-top: 4px solid #30a46c;
    }

    .debts-page .stat-card.gradient-fx {
        background: linear-gradient(135deg, #ff5f6d 0%, #c22b3a 100%);
        color: #fff;
    }

    .debts-page .stat-card.gradient-f {
        background: linear-gradient(135deg, #3a3d5c 0%, #1c1d2e 100%);
        color: #fff;
    }

    .debts-page .stat-card.gradient-fx .stat-label,
    .debts-page .stat-card.gradient-f .stat-label {
        color: rgba(255, 255, 255, .75);
    }

    .debts-page .stat-card.gradient-fx .stat-icon,
    .debts-page .stat-card.gradient-f .stat-icon {
        opacity: .25;
    }

    .debts-page .filter-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(20, 20, 43, 0.05);
    }

    .debts-page .filter-card .form-control,
    .debts-page .filter-card .form-select {
        border-radius: 10px;
        border: 1px solid #e6e7ee;
    }

    .debts-page .filter-card .form-control:focus,
    .debts-page .filter-card .form-select:focus {
        border-color: #7c6ff0;
        box-shadow: 0 0 0 3px rgba(124, 111, 240, .12);
    }

    .debts-page .btn-search {
        border-radius: 10px;
        background: #7c6ff0;
        border: none;
        color: #fff;
        font-weight: 500;
    }

    .debts-page .btn-search:hover {
        background: #6a5ce6;
        color: #fff;
    }

    .debts-page .table-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(20, 20, 43, 0.06);
        overflow: hidden;
    }

    .debts-page .table-card .table-toolbar {
        padding: 14px 18px;
        border-bottom: 1px solid #f1f1f6;
    }

    .debts-page .btn-download {
        border-radius: 10px;
        background: #16a34a;
        border: none;
        font-weight: 500;
        padding: 8px 16px;
    }

    .debts-page .btn-download:hover {
        background: #128a3e;
    }

    .debts-page table thead th {
        background: #f7f7fb;
        border: none;
        color: #6b6f80;
        text-transform: uppercase;
        font-size: .72rem;
        letter-spacing: .04em;
        font-weight: 700;
        padding: 12px 16px;
    }

    .debts-page table tbody td {
        padding: 14px 16px;
        vertical-align: middle;
        border-color: #f1f1f6;
    }

    .debts-page table tbody tr {
        transition: background .12s ease;
    }

    .debts-page table tbody tr:hover {
        background: #faf9ff;
    }

    .debts-page .student-link {
        color: #2b2d42;
        font-weight: 600;
        text-decoration: none;
    }

    .debts-page .student-link:hover {
        color: #7c6ff0;
    }

    .debts-page .badge-soft-info {
        background: #e8ecff;
        color: #3548a3;
        font-weight: 600;
        border-radius: 8px;
        padding: 5px 10px;
    }

    .debts-page .badge-grade {
        background: #fdeaea;
        color: #c0392b;
        font-weight: 700;
        border-radius: 8px;
        padding: 5px 10px;
    }

    .debts-page .attempts-pill {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        background: #f2f2f7;
        border-radius: 20px;
        padding: 4px 10px;
        font-size: .82rem;
        font-weight: 600;
        color: #4a4d5e;
    }

    .debts-page .action-btn {
        border-radius: 8px;
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        margin-inline-start: 4px;
    }

    .debts-page .empty-state {
        padding: 60px 20px;
        text-align: center;
        color: #9a9dae;
    }

    .debts-page .empty-state i {
        font-size: 2.6rem;
        color: #d9dbe6;
        margin-bottom: 10px;
        display: block;
    }
</style>

<div class="debts-page">

    {{-- Омор --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-2">
            <div class="card stat-card accent-danger">
                <div class="card-body">
                    <i class="bi bi-exclamation-triangle-fill stat-icon text-danger"></i>
                    <div class="stat-value text-danger">{{ $stats['total_open'] }}</div>
                    <span class="stat-label">Қарздориҳои кушод</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card stat-card accent-neutral">
                <div class="card-body">
                    <i class="bi bi-lightning-charge-fill stat-icon text-secondary"></i>
                    <div class="stat-value">{{ $stats['active'] }}</div>
                    <span class="stat-label">Фаъол</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card stat-card gradient-fx">
                <div class="card-body py-3">
                    <i class="bi bi-exclamation-octagon-fill stat-icon"></i>
                    <div class="stat-value">{{ $stats['fx_total'] }}</div>
                    <span class="stat-label">Fx қарз</span>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card stat-card gradient-f">
                <div class="card-body py-3">
                    <i class="bi bi-exclamation-circle-fill stat-icon"></i>
                    <div class="stat-value">{{ $stats['f_total'] }}</div>
                    <span class="stat-label">F қарз</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Филтр --}}
    <div class="card filter-card mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Ҷустуҷӯ</label>
                    <input type="text" name="search" class="form-control" placeholder="Ном..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Ҳолат</label>
                    <select name="status" class="form-select">
                        <option value="">Ҳама ҳолатҳо</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Фаъол</option>
                        <option value="retake_scheduled" {{ request('status') == 'retake_scheduled' ? 'selected' : '' }}>Такрорсупорӣ</option>
                        <option value="resolved" {{ request('status') == 'resolved' ? 'selected' : '' }}>Ҳалшуда</option>
                        <option value="escalated" {{ request('status') == 'escalated' ? 'selected' : '' }}>Комиссия</option>
                        <option value="repeat_course" {{ request('status') == 'repeat_course' ? 'selected' : '' }}>Дубора хондан</option>
                        <option value="expelled" {{ request('status') == 'expelled' ? 'selected' : '' }}>Хориҷшуда</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Навъи қарз</label>
                    <select name="debt_type" class="form-select">
                        <option value="">Ҳама навъҳо</option>
                        <option value="fx" {{ request('debt_type') == 'fx' ? 'selected' : '' }}>Fx</option>
                        <option value="f" {{ request('debt_type') == 'f' ? 'selected' : '' }}>F</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Гурӯҳ</label>
                    <select name="group_id" class="form-select">
                        <option value="">Ҳама гурӯҳҳо</option>
                        @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-search w-100"><i class="bi bi-search me-1"></i> Ҷустуҷӯ</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвал --}}
    <div class="card table-card mb-4">
        <div class="table-toolbar d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-semibold text-secondary"><i class="bi bi-list-check me-1"></i> Рӯйхати қарздориҳо</h6>
            <a href="{{ route('admin.debts.export-pdf', request()->query()) }}" class="btn btn-download text-white">
                <i class="bi bi-download"></i> Боргирӣ
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Донишҷӯ</th>
                        <th>Гурӯҳ</th>
                        <th>Фан</th>
                        <th>Сабаб</th>
                        <th>Баҳо</th>
                        <th>Санаи қарз</th>
                        <th>Кредит</th>
                        <th>Ҳолат</th>
                        <th class="text-end">Амалҳо</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debts as $debt)
                    <tr>
                        <td><a href="{{ route('admin.students.show', $debt->student) }}" class="student-link">{{ $debt->student?->user?->short_name }}</a></td>
                        <td><span class="badge-soft-info">{{ $debt->student?->group?->name }}</span></td>
                        <td><small>{{ $debt->subject?->name }}</small></td>
                        <td><small class="text-muted">{{ $debt->reason_label }}</small></td>
                        <td><span class="badge-grade">{{ $debt->original_grade }}</span> <small class="text-muted">({{ $debt->original_score }})</small></td>
                        <td><small class="text-muted">{{ $debt->debt_date?->format('d.m.Y') }}</small></td>
                        <td><span class="attempts-pill"><i class="bi bi-award"></i> {{ $debt->subject?->credits ?? '—' }} кредит</span></td>
                        <td><span class="badge {{ $debt->status->badgeClass() }} rounded-pill px-3 py-2">{{ $debt->status->label() }}</span></td>
                        <td class="text-end">
                            <a href="{{ route('admin.debts.show', $debt) }}" class="btn btn-sm btn-outline-info action-btn" title="Дидан"><i class="bi bi-eye"></i></a>
                            @if($debt->canRetake())
                            <form action="{{ route('admin.debts.schedule-retake', $debt) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning action-btn" title="Такрорсупорӣ"><i class="bi bi-arrow-repeat"></i></button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                Қарздорӣ ёфт нашуд.
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($debts->hasPages())
    <div class="card-footer bg-white border-0">{{ $debts->links() }}</div>
    @endif
</div>
@endsection