<!DOCTYPE html>
<html lang="tg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ҳисоботи давомот — Донишёр</title>
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
            <h4><i class="bi bi-check2-square me-2"></i>Ҳисоботи давомот</h4>
            <a href="/admin/reports" class="btn btn-outline-secondary btn-sm">← Ба ҳисоботҳо</a>
        </div>

        {{-- Филтр --}}
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Гурӯҳ</label>
                        <select name="group_id" class="form-select form-select-sm">
                            <option value="">— Интихоб кунед —</option>
                            @foreach($groups as $g)
                                <option value="{{ $g->id }}" {{ $groupId == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Семестр</label>
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

        {{-- Ҷадвали давомот --}}
        @if($attendanceData->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Натиҷа: {{ $attendanceData->count() }} донишҷӯ</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Умумӣ дарс</th>
                            <th>Ҳозир</th>
                            <th>Ғоиб</th>
                            <th>Фоизи давомот</th>
                            <th>Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendanceData->values() as $i => $item)
                            <tr class="{{ $item['percentage'] < 75 ? 'table-danger' : '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $item['student_name'] }}</td>
                                <td>{{ $item['total'] }}</td>
                                <td class="text-success">{{ $item['present'] }}</td>
                                <td class="text-danger">{{ $item['absent'] }}</td>
                                <td>
                                    <div class="progress" style="height: 20px; min-width: 100px;">
                                        <div class="progress-bar {{ $item['percentage'] >= 75 ? 'bg-success' : 'bg-danger' }}"
                                             style="width: {{ $item['percentage'] }}%">{{ $item['percentage'] }}%</div>
                                    </div>
                                </td>
                                <td>
                                    @if($item['percentage'] >= 75)
                                        <span class="badge bg-success">Иҷозат</span>
                                    @else
                                        <span class="badge bg-danger">Ба имтиҳон иҷозат НЕСТ</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="bi bi-info-circle fs-1 d-block mb-2"></i>
                Лутфан гурӯҳ ва семестрро интихоб кунед.
            </div>
        @endif
    </div>
</body>
</html>
