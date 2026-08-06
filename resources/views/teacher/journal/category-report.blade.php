@extends('layouts.app')

@section('title', 'Гузориши журнал')
@section('page-header', 'Гузориши баҳоҳои категориявӣ')
@section('page-description')
    {{ $subjectAssignment->curriculum?->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i> Гузориши ҳамаи дарсҳо</h6>
            <a href="{{ route((request()->is('admin/*') ? 'admin.journal' : 'teacher.journal') . '.category-scores', $subjectAssignment) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered journal-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th rowspan="2" style="width: 40px;">#</th>
                            <th rowspan="2" style="min-width: 160px;">Донишҷӯ</th>
                            @foreach($lessons as $lesson)
                                <th class="text-center" style="min-width: 50px;">
                                    <small>{{ \Carbon\Carbon::parse($lesson['date'])->format('d.m') }}</small>
                                    <br><small class="text-muted">Д{{ $lesson['lesson_number'] }}</small>
                                </th>
                            @endforeach
                            <th rowspan="2" class="text-center">Ҷамъ</th>
                            <th rowspan="2" class="text-center">%</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            @php $totals = $studentTotals[$student->id] ?? ['total_score' => 0, 'total_max' => 0, 'percentage' => 0]; @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="text-start">{{ $student->user?->short_name ?? $student->user?->full_name }}</td>
                                @foreach($lessons as $lesson)
                                    @php
                                        $key = $lesson['date'] . '_' . $lesson['lesson_number'];
                                        $lessonScores = $scoreMatrix[$student->id][$key] ?? [];
                                        $lessonTotal = array_sum($lessonScores);
                                    @endphp
                                    <td class="text-center">
                                        @if(!empty($lessonScores))
                                            <span class="{{ $lessonTotal > 0 ? 'text-success' : 'text-danger' }}" title="@foreach($lessonScores as $cat => $s){{ ucfirst($cat) }}: {{ $s }}&#10;@endforeach">
                                                {{ $lessonTotal }}
                                            </span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center"><strong>{{ $totals['total_score'] }}</strong></td>
                                <td class="text-center">
                                    <span class="{{ $totals['percentage'] >= 50 ? 'text-success' : 'text-danger' }}">
                                        <strong>{{ $totals['percentage'] }}%</strong>
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Шарҳи категорияҳо --}}
    <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
            <h6><i class="bi bi-info-circle me-2"></i> Категорияҳо:</h6>
            <div class="d-flex gap-3 flex-wrap">
                @foreach($categorySettings->where('is_active', true) as $cs)
                    <span class="badge bg-{{ $cs->category->colorClass() }} px-3 py-2">
                        <i class="bi {{ $cs->category->icon() }} me-1"></i>
                        {{ $cs->category->label() }} (макс: {{ $cs->max_score }})
                    </span>
                @endforeach
            </div>
        </div>
    </div>
@endsection
