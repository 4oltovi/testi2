<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Рӯйхати донишҷӯён — Донишёр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard"><i class="bi bi-mortarboard-fill me-2"></i>ДОНИШЁР</a>
            <span class="text-white">{{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? '' }}</span>
        </div>
    </nav>
    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between mb-3">
            <h4><i class="bi bi-people me-2"></i>Рӯйхати донишҷӯён</h4>
            <a href="/admin/reports" class="btn btn-outline-secondary btn-sm">← Ба ҳисоботҳо</a>
        </div>

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
                    <div class="col-md-3">
                        <select name="faculty_id" class="form-select form-select-sm">
                            <option value="">Ҳама факултетҳо</option>
                            @foreach($faculties as $f)
                                <option value="{{ $f->id }}" {{ request('faculty_id') == $f->id ? 'selected' : '' }}>{{ $f->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm w-100">Филтр</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Ҷадвал --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Насаб ва ном</th>
                            <th>Рақами дон.</th>
                            <th>Гурӯҳ</th>
                            <th>Факултет</th>
                            <th>Курс</th>
                            <th>GPA</th>
                            <th>Шакл</th>
                            <th>Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $i => $s)
                            <tr>
                                <td>{{ $students->firstItem() + $i }}</td>
                                <td><strong>{{ $s->user?->full_name }}</strong></td>
                                <td><code>{{ $s->student_id_number }}</code></td>
                                <td>{{ $s->group?->name }}</td>
                                <td>{{ $s->specialty?->department?->faculty?->short_name }}</td>
                                <td>{{ $s->course?->number }}</td>
                                <td><strong class="{{ $s->cumulative_gpa >= 3.0 ? 'text-success' : ($s->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">{{ number_format($s->cumulative_gpa, 2) }}</strong></td>
                                <td>{{ $s->education_form == 'budget' ? 'Б' : 'Ш' }}</td>
                                <td><span class="badge {{ $s->status->badgeClass() }}">{{ $s->status->label() }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted py-3">Маълумот нест</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($students->hasPages())
                <div class="card-footer bg-white">{{ $students->links() }}</div>
            @endif
        </div>
    </div>
</body>
</html>
