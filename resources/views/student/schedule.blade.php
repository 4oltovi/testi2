@extends('layouts.app')

@section('title', 'Ҷадвали дарс')
@section('page-header', 'Ҷадвали дарс')
@section('page-description', 'Ҷадвали дарсҳои ҳафтагӣ')

@section('content')
@php
    $days = [1 => 'Душанбе', 2 => 'Сешанбе', 3 => 'Чоршанбе', 4 => 'Панҷшанбе', 5 => 'Ҷумъа', 6 => 'Шанбе'];
    $maxLesson = $schedules->max('lesson_number') ?? 0;
@endphp

@if($schedules->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 80px;">#</th>
                        @foreach($days as $day)
                        <th class="text-center">{{ $day }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @for($lesson = 1; $lesson <= max($maxLesson, 6); $lesson++)
                    <tr>
                        <td class="text-center fw-bold">{{ $lesson }}</td>
                        @foreach($days as $dayNum => $dayName)
                            @php
                                $items = $schedules->where('day_of_week', $dayNum)->where('lesson_number', $lesson);
                            @endphp
                            <td class="p-1">
                                @foreach($items as $item)
                                    <div class="p-2 rounded bg-light border h-100">
                                        <div class="fw-bold text-primary small">
                                            {{ $item->subjectAssignment?->subject?->name ?? '—' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->start_time?->format('H:i') ?? '' }} - {{ $item->end_time?->format('H:i') ?? '' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->classroom?->name ?? '' }}
                                        </div>
                                    </div>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                    @endfor
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="text-center py-5 text-muted">
    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
    <p>Ҷадвали дарс ҳанӯз ворид нашудааст.</p>
    <small>Бо деканат тамос гиред.</small>
</div>
@endif
@endsection
