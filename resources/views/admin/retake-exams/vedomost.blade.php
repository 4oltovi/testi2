@extends('layouts.app')

@section('title', 'Ведомости такрорӣ — ' . $exam->title)
@section('page-header', 'Ведомости такрорӣ')
@section('page-description', $exam->subject->name ?? 'Фан' . ' | ' . $exam->exam_date?->format('d.m.Y') ?? '')

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Ведомости такрорӣ</h6>
                <div>
                    <a href="{{ route('admin.retake-exams.print-vedomost', $exam) }}" class="btn btn-sm btn-primary" target="_blank">
                        <i class="bi bi-printer me-1"></i> Чоп кардан
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Фан:</strong> {{ $exam->subject->name ?? '-' }} |
                    <strong>Семестр:</strong> {{ $exam->semester->name ?? '-' }} |
                    <strong>Сана:</strong> {{ $exam->exam_date?->format('d.m.Y') ?? '-' }} |
                    <strong>Умумӣ:</strong> {{ $rows->count() }} нафар
                </div>

                @foreach($groupedRows as $groupName => $groupRows)
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <strong>{{ $groupName }}</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;">№</th>
                                        <th>ID</th>
                                        <th>Номи донишҷӯ</th>
                                        <th>Баҳои аслӣ</th>
                                        <th>Холи аслӣ</th>
                                        <th>Кӯшиш</th>
                                        <th>Баҳои такрорӣ</th>
                                        <th>Холи такрорӣ</th>
                                        <th>Ҳолат</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($groupRows as $row)
                                    <tr>
                                        <td>{{ $row['n'] }}</td>
                                        <td>{{ $row['student_id'] }}</td>
                                        <td>{{ $row['fio'] }}</td>
                                        <td>{{ $row['original_score'] }}</td>
                                        <td>{{ $row['original_grade'] }}</td>
                                        <td>{{ $row['attempt'] }}</td>
                                        <td>
                                            @if($row['retake_score'] !== '-')
                                            <strong>{{ $row['retake_score'] }}</strong>
                                            @else
                                            {{ $row['retake_score'] }}
                                            @endif
                                        </td>
                                        <td>{{ $row['retake_grade'] }}</td>
                                        <td>
                                            @php
                                            $statusBadge = match($row['status']) {
                                                'pending' => 'bg-warning',
                                                'passed' => 'bg-success',
                                                'failed' => 'bg-danger',
                                                'absent' => 'bg-secondary',
                                                default => 'bg-secondary',
                                            };
                                            @endphp
                                            <span class="badge {{ $statusBadge }}">{{ $row['status'] }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endforeach

                <div class="mt-3">
                    <a href="{{ route('admin.retake-exams.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
