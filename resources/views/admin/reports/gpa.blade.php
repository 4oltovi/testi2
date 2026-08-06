<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ҳисоботи GPA — Донишёр</title>
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
            <h4><i class="bi bi-bar-chart me-2"></i>Ҳисоботи GPA</h4>
            <a href="/admin/reports" class="btn btn-outline-secondary btn-sm">← Ба ҳисоботҳо</a>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>GPA</th>
                            <th>Кредитҳо</th>
                            <th>Қарздор</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gpaData as $i => $s)
                            <tr>
                                <td>{{ $gpaData->firstItem() + $i }}</td>
                                <td><strong>{{ $s->user?->full_name }}</strong></td>
                                <td>{{ $s->group?->name }}</td>
                                <td>
                                    <strong class="{{ $s->cumulative_gpa >= 3.0 ? 'text-success' : ($s->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                                        {{ number_format($s->cumulative_gpa, 2) }}
                                    </strong>
                                </td>
                                <td>{{ $s->total_credits_earned }} / {{ $s->specialty?->total_credits ?? '—' }}</td>
                                <td>
                                    @if($s->has_debts)
                                        <span class="badge bg-danger">Ҳа</span>
                                    @else
                                        <span class="badge bg-success">Не</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-3">Маълумот нест</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($gpaData->hasPages())
                <div class="card-footer bg-white">{{ $gpaData->links() }}</div>
            @endif
        </div>
    </div>
</body>
</html>
