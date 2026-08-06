<!DOCTYPE html>
<html lang="tg">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панели идоракунӣ — Донишёр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    {{-- Навори боло --}}
    <nav class="navbar navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">
                <i class="bi bi-mortarboard-fill me-2"></i> ДОНИШЁР
            </a>
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    {{ auth()->user()->first_name ?? 'Корбар' }} {{ auth()->user()->last_name ?? '' }}
                </span>
                <form action="/logout" method="POST" class="d-inline">
                    @csrf
                    <button class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i> Баромад
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        {{-- Хуш омадед --}}
        <div class="mb-4">
            <h3>Хуш омадед, {{ auth()->user()->first_name ?? '' }}!</h3>
            <p class="text-muted">Панели идоракунии системаи «Донишёр»</p>
        </div>

        {{-- Карточкаҳо --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-people-fill fs-4 text-primary"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['total_students'] ?? 0 }}</h3>
                            <small class="text-muted">Донишҷӯён</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-person-workspace fs-4 text-success"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['total_teachers'] ?? 0 }}</h3>
                            <small class="text-muted">Омӯзгорон</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-collection fs-4 text-info"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['total_groups'] ?? 0 }}</h3>
                            <small class="text-muted">Гурӯҳҳо</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card border-0 shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3">
                            <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                        </div>
                        <div>
                            <h3 class="mb-0">{{ $stats['students_with_debts'] ?? 0 }}</h3>
                            <small class="text-muted">Қарздорон</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Маълумот --}}
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i> Маълумоти система</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0">
                            <tr>
                                <td class="text-muted">Система:</td>
                                <td>Донишёр v1.0</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Корбар:</td>
                                <td>{{ auth()->user()->login ?? '?' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Низоми баҳо:</td>
                                <td>Кредитии Тоҷикистон (A-F)</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Формула:</td>
                                <td>R1×0.15 + R2×0.15 + КМ×0.30 + Имт.×0.40</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="bi bi-link-45deg me-2"></i> Линкҳои зуд</h6>
                    </div>
                    <div class="card-body">
                        <a href="/admin/users" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-people"></i> Корбарон</a>
                        <a href="/admin/structure/faculties" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-building"></i> Факултетҳо</a>
                        <a href="/admin/students" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-person-badge"></i> Донишҷӯён</a>
                        <a href="/admin/teachers" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-person-workspace"></i> Омӯзгорон</a>
                        <a href="/admin/journal" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-journal-text"></i> Журнал</a>
                        <a href="/admin/ratings" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-bar-chart"></i> Рейтинг</a>
                        <a href="/admin/debts" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-exclamation-triangle"></i> Қарздорӣ</a>
                        <a href="/admin/reports" class="btn btn-outline-primary btn-sm me-1 mb-1"><i class="bi bi-file-earmark-bar-graph"></i> Ҳисобот</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>