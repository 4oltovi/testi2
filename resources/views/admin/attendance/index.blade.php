@extends('layouts.app')

@section('title', 'Ҳисоботи давамот')
@section('page-header', 'Ҳисоботи давамот')
@section('page-description', 'Омораи умумии давомоти донишҷӯён')

@section('page-actions')
<div class="btn-group">
    <a href="{{ route('admin.attendance.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm">
        <i class="bi bi-file-earmark-excel me-1"></i> Excel
    </a>
    <a href="{{ route('admin.attendance.export.pdf', request()->query()) }}" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-earmark-pdf me-1"></i> PDF
    </a>
</div>
@endsection

@section('content')

{{-- Филтр --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Факултет</label>
                <select name="faculty_id" class="form-select form-select-sm">
                    <option value="">— Ҳамаи факултетҳо —</option>
                    @foreach($faculties as $faculty)
                        <option value="{{ $faculty->id }}" {{ $facultyId == $faculty->id ? 'selected' : '' }}>{{ $faculty->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Ихтисос</label>
                <select name="specialty_id" class="form-select form-select-sm">
                    <option value="">— Ҳамаи ихтисосҳо —</option>
                    @foreach($specialties as $spec)
                        <option value="{{ $spec->id }}" {{ $specialtyId == $spec->id ? 'selected' : '' }}>{{ $spec->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Гурӯҳ</label>
                <select name="group_id" class="form-select form-select-sm">
                    <option value="">— Ҳамаи гурӯҳҳо —</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" {{ $groupId == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">Аз сана</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-semibold">То сана</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-sm w-100"><i class="bi bi-search me-1"></i> Нишон деҳ</button>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #198754;">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-check-circle text-success fs-3"></i></div>
                <h3 class="fw-bold mb-0 text-success">{{ $summary['present'] }}</h3>
                <small class="text-muted">Ҳозир</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #dc3545;">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-x-circle text-danger fs-3"></i></div>
                <h3 class="fw-bold mb-0 text-danger">{{ $summary['absent'] }}</h3>
                <small class="text-muted">Ғоиб</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #0d6efd;">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-graph-up text-primary fs-3"></i></div>
                <h3 class="fw-bold mb-0 text-primary">{{ $summary['percentage'] }}%</h3>
                <small class="text-muted">Фоизи давомот</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; border-left: 4px solid #fd7e14;">
            <div class="card-body text-center py-3">
                <div class="mb-2"><i class="bi bi-people text-warning fs-3"></i></div>
                <h3 class="fw-bold mb-0 text-warning">{{ $summary['groups_marked'] }}</h3>
                <small class="text-muted">Гурӯҳҳо муайян</small>
            </div>
        </div>
    </div>
</div>

{{-- Chart + Group Stats --}}
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e8edf5;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius: 14px 14px 0 0; padding: 1rem 1.25rem;">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-graph-up me-2 text-primary"></i>Диаграммаи ҳузур
                </h6>
                <div class="btn-group btn-group-sm" role="group">
                    <a href="{{ request()->fullUrlWithQuery(['trend_days' => 7]) }}" class="btn btn-outline-primary {{ $trendDays == 7 ? 'active' : '' }}">7 рӯз</a>
                    <a href="{{ request()->fullUrlWithQuery(['trend_days' => 30]) }}" class="btn btn-outline-primary {{ $trendDays == 30 ? 'active' : '' }}">30 рӯз</a>
                </div>
            </div>
            <div class="card-body">
                <canvas id="attendanceChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100" style="border-radius: 14px; border: 1px solid #e8edf5;">
            <div class="card-header bg-white" style="border-radius: 14px 14px 0 0; padding: 1rem 1.25rem;">
                <h6 class="mb-0 fw-bold">
                    <i class="bi bi-diagram-3 me-2 text-primary"></i>Рейтинги гурӯҳҳо
                </h6>
            </div>
            <div class="card-body">
                @forelse($groupStats as $group)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold small">{{ $group->name }}</span>
                            <span class="small fw-semibold {{ $group->percentage >= 80 ? 'text-success' : ($group->percentage >= 60 ? 'text-warning' : 'text-danger') }}">
                                {{ $group->percentage }}%
                            </span>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 4px; background-color: #e9ecef;">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ $group->percentage }}%; border-radius: 4px; background-color: {{ $group->percentage >= 80 ? '#198754' : ($group->percentage >= 60 ? '#fd7e14' : '#dc3545') }};">
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted text-center py-4">Маълумот ёфт нашуд.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Low Attendance Students --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius: 14px; border: 1px solid #e8edf5;">
    <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius: 14px 14px 0 0; padding: 1rem 1.25rem;">
        <h6 class="mb-0 fw-bold">
            <i class="bi bi-exclamation-triangle text-warning me-2"></i>Донишҷӯёни бо давомоти паст (< 75%)
        </h6>
        <small class="text-muted">Дар 30 рӯзи охир</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" style="font-size: 0.9rem;">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Донишҷӯ</th>
                        <th>Шиноса</th>
                        <th>Гурӯҳ</th>
                        <th class="text-center">Дарсҳо</th>
                        <th class="text-center">Ҳозир</th>
                        <th class="text-center">Фоиз</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowAttendanceStudents as $index => $student)
                        <tr>
                            <td style="color: var(--text-muted);">{{ $index + 1 }}</td>
                            <td>
                                <span class="fw-semibold">{{ $student->student_name }}</span>
                            </td>
                            <td><small class="text-muted">{{ $student->student_id_number ?? '—' }}</small></td>
                            <td>{{ $student->group_name }}</td>
                            <td class="text-center">{{ $student->total }}</td>
                            <td class="text-center text-success">{{ $student->present }}</td>
                            <td class="text-center">
                                <span class="badge rounded-pill {{ $student->percentage < 50 ? 'bg-danger' : 'bg-warning' }}" style="font-weight: 600;">
                                    {{ $student->percentage }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-check-circle fs-4 d-block mb-2 opacity-50"></i>
                                Донишҷӯёни бо давомоти паст ёфт нашданд.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Overall stats summary --}}
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm" style="border-radius: 14px; border: 1px solid #e8edf5;">
            <div class="card-body py-3">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold fs-5">{{ $summary['total'] }}</div>
                        <small class="text-muted">Ҳамаи давомоти сабтшуда</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-success">{{ $summary['present'] }}</div>
                        <small class="text-muted">Ҳозир</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold fs-5 text-danger">{{ $summary['absent'] }}</div>
                        <small class="text-muted">Ғоиб</small>
                    </div>
                </div>
                <hr class="my-2">
                <div class="text-center">
                    <div class="progress mx-auto" style="max-width: 600px; height: 12px; border-radius: 6px; background-color: #e9ecef;">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ $summary['percentage'] }}%; border-radius: 6px; background-color: {{ $summary['percentage'] >= 75 ? '#198754' : ($summary['percentage'] >= 60 ? '#fd7e14' : '#dc3545') }};">
                        </div>
                    </div>
                    <div class="mt-1">
                        <strong>{{ $summary['percentage'] }}%</strong>
                        <span class="text-muted"> — фоизи умумии давомот</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const labels = @json($trendData->pluck('attendance_date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d M')));
    const presentData = @json($trendData->pluck('present'));
    const absentData = @json($trendData->pluck('absent'));

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Ҳозир',
                    data: presentData,
                    backgroundColor: 'rgba(25, 135, 84, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                },
                {
                    label: 'Ғоиб',
                    data: absentData,
                    backgroundColor: 'rgba(220, 53, 69, 0.8)',
                    borderRadius: 6,
                    borderSkipped: false,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'rectRounded',
                        padding: 20,
                        font: { size: 12 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 } }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' },
                    ticks: { font: { size: 11 }, stepSize: 1 }
                }
            }
        }
    });
});
</script>
@endpush
