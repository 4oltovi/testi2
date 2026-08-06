@extends('layouts.app')
@section('title', 'Ҷадвали дарс')
@section('page-header', 'Ҷадвали дарс')
@section('page-description', 'Ҷадвали дарсҳои ҳафтагӣ')

@section('content')
@if(isset($schedules) && $schedules->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Вақт</th>
                        <th>Душанбе</th>
                        <th>Сешанбе</th>
                        <th>Чоршанбе</th>
                        <th>Панҷшанбе</th>
                        <th>Ҷумъа</th>
                        <th>Шанбе</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($schedules as $schedule)
                    <tr>
                        <td>{{ $schedule->time ?? '—' }}</td>
                        <td colspan="6">{{ $schedule->subject ?? '—' }}</td>
                    </tr>
                    @endforeach
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