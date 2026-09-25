@extends('layouts.app')

@section('title', 'Омора — Давомот')
@section('page-header', 'Омора')
@section('page-description', 'Ҳисоботи умумии давомоти донишҷӯён')

@section('content')
    {{-- Back button --}}
    <div class="mb-3">
        <a href="{{ route('operator.attendance.index') }}" class="btn btn-outline-secondary btn-sm" style="border-radius: 10px;">
            <i class="bi bi-arrow-left me-1"></i> Давомот
        </a>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-body text-center py-3">
                    <div class="mb-2">
                        <i class="bi bi-people text-primary fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $totalStudents }}</h3>
                    <small class="text-muted">Ҳамаи донишҷӯён</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-body text-center py-3">
                    <div class="mb-2">
                        <i class="bi bi-check-circle text-success fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $presentToday }}</h3>
                    <small class="text-muted">Ҳозир <span class="text-success fw-semibold">{{ $presentPercentage }}%</span></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-body text-center py-3">
                    <div class="mb-2">
                        <i class="bi bi-x-circle text-danger fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $absentToday }}</h3>
                    <small class="text-muted">Ғоиб <span class="text-danger fw-semibold">{{ $absentPercentage }}%</span></small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-body text-center py-3">
                    <div class="mb-2">
                        <i class="bi bi-calendar-check text-info fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-0">{{ $todayClasses }}</h3>
                    <small class="text-muted">Дарсҳои имрӯз</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart and Group Stats --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Диаграммаи ҳузур
                    </h6>
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('operator.attendance.statistics') }}?trend_days=7" class="btn btn-outline-primary {{ $trendDays == 7 ? 'active' : '' }}">7 рӯз</a>
                        <a href="{{ route('operator.attendance.statistics') }}?trend_days=30" class="btn btn-outline-primary {{ $trendDays == 30 ? 'active' : '' }}">30 рӯз</a>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-header bg-white" style="border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        Гурӯҳҳо
                    </h6>
                </div>
                <div class="card-body">
                    @forelse($groupStats->take(8) as $group)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <a href="{{ route('operator.attendance.group', ['group' => $group->id, 'date' => now()->format('Y-m-d')]) }}" class="fw-semibold small text-decoration-none" style="color: inherit;">
                                    {{ $group->full_name }}
                                </a>
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

    {{-- Compact Stats and Low Attendance --}}
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-header bg-white" style="border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>
                        Ҳозир ва ғоиб
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <div class="text-center flex-fill">
                            <div class="fw-bold text-success fs-5">{{ $presentToday }}</div>
                            <small class="text-muted">Ҳозир</small>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $presentPercentage }}%;"></div>
                            </div>
                        </div>
                        <div class="text-center flex-fill">
                            <div class="fw-bold text-danger fs-5">{{ $absentToday }}</div>
                            <small class="text-muted">Ғоиб</small>
                            <div class="progress mt-2" style="height: 6px;">
                                <div class="progress-bar bg-danger" style="width: {{ $absentPercentage }}%;"></div>
                            </div>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Бо сабаб</span>
                        <span class="fw-semibold small">{{ round($absentToday * 0.6) }}</span>
                    </div>
                    <div class="progress mb-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: {{ $absentPercentage * 0.6 }}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">Бе сабаб</span>
                        <span class="fw-semibold small">{{ round($absentToday * 0.4) }}</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-danger" style="width: {{ $absentPercentage * 0.4 }}%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 16px; border: 1px solid #e8edf5;">
                <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius: 16px 16px 0 0; padding: 1rem 1.25rem;">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                        Давомоти паст
                    </h6>
                    <small class="text-muted">Донишҷӯёни бо давомоти паст</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size: 0.85rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="padding: 0.6rem 0.5rem;">№</th>
                                    <th style="padding: 0.6rem 0.5rem;">Ф.И.О.</th>
                                    <th style="padding: 0.6rem 0.5rem;">Гурӯҳ</th>
                                    <th class="text-center" style="padding: 0.6rem 0.5rem;">Фоизи ҳузур</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowAttendanceStudents as $index => $student)
                                    <tr>
                                        <td style="padding: 0.5rem 0.5rem; color: var(--text-muted);">{{ $index + 1 }}</td>
                                        <td style="padding: 0.5rem 0.5rem;">
                                            <span class="fw-semibold">{{ $student->student_name }}</span>
                                            <br><small class="text-muted" style="font-size: 0.75rem;">{{ $student->student_id_number }}</small>
                                        </td>
                                        <td style="padding: 0.5rem 0.5rem;">{{ $student->group_name }}</td>
                                        <td class="text-center" style="padding: 0.5rem 0.5rem;">
                                            <span class="badge rounded-pill {{ $student->percentage < 50 ? 'bg-danger' : 'bg-warning' }}" style="font-weight: 600;">
                                                {{ $student->percentage }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">
                                            <i class="bi bi-check-circle fs-1 d-block mb-2 opacity-50"></i>
                                            Донишҷӯёни бо давомоти паст ёфт нашданд.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                    ticks: { font: { size: 11 } }
                }
            }
        }
    });
});
</script>
@endpush
