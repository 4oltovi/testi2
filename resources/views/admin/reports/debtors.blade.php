<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рӯйхати қарздорон — Донишёр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard"><i class="bi bi-mortarboard-fill me-2"></i>ДОНИШЁР</a>
        </div>
    </nav>
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between mb-3">
            <h4><i class="bi bi-exclamation-triangle text-danger me-2"></i>Рӯйхати қарздорон</h4>
            <a href="/admin/reports" class="btn btn-outline-secondary btn-sm">← Ба ҳисоботҳо</a>
        </div>

        {{-- Омор аз рӯйи гурӯҳ --}}
        @if(isset($debtorsByGroup) && $debtorsByGroup->isNotEmpty())
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-header bg-danger bg-opacity-10"><h6 class="mb-0">Қарздорон аз рӯйи гурӯҳ</h6></div>
            <div class="card-body">
                <div class="row g-2">
                    @foreach($debtorsByGroup->take(12) as $item)
                        <div class="col-auto">
                            <span class="badge bg-danger">{{ $item->group_name }}: {{ $item->debtors_count }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Филтр --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">Ҳама гурӯҳҳо</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" {{ request('group_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm w-100">Филтр</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Ҷадвали қарздорон --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>Фан</th>
                            <th>Сабаб</th>
                            <th>Баҳо</th>
                            <th>Санаи қарз</th>
                            <th>Кӯшиш</th>
                            <th>Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($debts as $i => $debt)
                            <tr>
                                <td>{{ $debts->firstItem() + $i }}</td>
                                <td><strong>{{ $debt->student?->user?->short_name }}</strong></td>
                                <td><span class="badge bg-info">{{ $debt->student?->group?->name }}</span></td>
                                <td>{{ $debt->subject?->name }}</td>
                                <td><small>{{ $debt->reason_label }}</small></td>
                                <td><span class="badge bg-danger">{{ $debt->original_grade }}</span> {{ $debt->original_score }}%</td>
                                <td>{{ $debt->debt_date?->format('d.m.Y') }}</td>
                                <td>{{ $debt->retake_attempts_used }}/{{ $debt->max_retake_attempts }}</td>
                                <td><span class="badge {{ $debt->status->badgeClass() }}">{{ $debt->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">Қарздор нест! 🎉</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($debts->hasPages())
                <div class="card-footer bg-white">{{ $debts->links() }}</div>
            @endif
        </div>
    </div>
</body>
</html>
