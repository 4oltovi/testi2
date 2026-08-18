<!DOCTYPE html>
<html lang="tg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Натиҷаҳои имтиҳон — Донишёр</title>
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
            <h4><i class="bi bi-journal-check me-2"></i>Натиҷаҳои имтиҳон</h4>
            <a href="/admin/reports" class="btn btn-outline-secondary btn-sm">← Ба ҳисоботҳо</a>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">Ҳама гурӯҳҳо</option>
                            @foreach($groups as $g)
                            <option value="{{ $g->id }}" {{ $groupId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="semester_id" class="form-select form-select-sm">
                            @foreach($semesters as $sem)
                            <option value="{{ $sem->id }}" {{ $semesterId == $sem->id ? 'selected' : '' }}>
                                {{ $sem->name }} — {{ $sem->academicYear?->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm w-100">Нишон деҳ</button>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($results) && (is_object($results) ? $results->isNotEmpty() : !empty($results)))
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Гурӯҳ</th>
                            <th>Фан</th>
                            <th>R1</th>
                            <th>R2</th>
                            <th>КМ</th>
                            <th>Имт.</th>
                            <th>Ниҳоӣ</th>
                            <th>Баҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $i => $r)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $r->student?->user?->short_name }}</td>
                            <td>{{ $r->student?->group?->name }}</td>
                            <td>{{ $r->subjectAssignment?->subject?->name }}</td>
                            <td>{{ $r->rating1_score ?? '—' }}</td>
                            <td>{{ $r->rating2_score ?? '—' }}</td>
                            <td>{{ $r->independent_work_score ?? '—' }}</td>
                            <td>{{ $r->exam_score ?? '—' }}</td>
                            <td><strong>{{ $r->total_score ? number_format($r->total_score, 1) : '—' }}</strong></td>
                            <td>
                                @if($r->letter_grade)
                                <span class="badge {{ $r->isPassed() ? 'bg-success' : 'bg-danger' }}">{{ $r->letter_grade }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="text-center text-muted py-4">Натиҷае нест. Гурӯҳ ва семестрро интихоб кунед.</div>
        @endif
    </div>
</body>

</html>