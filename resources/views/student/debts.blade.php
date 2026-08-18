@extends('layouts.app')
@section('title', 'Қарздориҳо')
@section('page-header', 'Қарздориҳои академӣ')
@section('page-description', 'Фанҳое ки бояд такрор супорида шаванд')

@section('content')
@if(isset($debts) && $debts->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Фан</th>
                        <th>Семестр</th>
                        <th>Баҳо</th>
                        <th>Ҳолат</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($debts as $index => $debt)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $debt->semesterGrade?->subject?->name ?? '—' }}</td>
                        <td>{{ $debt->semesterGrade?->semester?->name ?? '—' }}</td>
                        <td>
                            <span class="badge bg-danger">{{ $debt->semesterGrade?->letter_grade ?? 'F' }}</span>
                        </td>
                        <td>
                            @php
                            $st = match($debt->status) {
                            'active' => ['Фаъол', 'danger'],
                            'scheduled' => ['Барномарезӣ', 'warning'],
                            'resolved' => ['Ҳал шуд', 'success'],
                            default => [$debt->status, 'secondary'],
                            };
                            @endphp
                            <span class="badge bg-{{ $st[1] }}">{{ $st[0] }}</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@else
<div class="text-center py-5">
    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle mb-3" style="width:80px;height:80px;">
        <i class="bi bi-check-lg text-success" style="font-size:2.5rem;"></i>
    </div>
    <h5 class="text-success">Қарздорие надоред!</h5>
    <p class="text-muted">Шумо дар ҳамаи фанҳо гузаштаед. Офарин!</p>
</div>
@endif
@endsection