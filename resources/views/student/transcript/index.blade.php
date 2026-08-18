@extends('layouts.app')
@section('title', 'Transcript')
@section('page-header', 'Transcript')
@section('page-description', 'Натиҷаҳои таҳсил')

@section('content')
@if($grades->isNotEmpty())
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Фан</th>
                        <th class="text-center">Семестр</th>
                        <th class="text-center">Кредит</th>
                        <th class="text-center">Фоиз</th>
                        <th class="text-center">Баҳо</th>
                        <th class="text-center">GPA</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grades as $index => $grade)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $grade->subject?->name ?? '—' }}</td>
                        <td class="text-center">{{ $grade->semester?->name ?? '—' }}</td>
                        <td class="text-center">{{ $grade->credits_earned }}</td>
                        <td class="text-center">{{ $grade->total_score ? number_format($grade->total_score, 0) . '%' : '—' }}</td>
                        <td class="text-center">
                            @if($grade->letter_grade)
                            @php $g = \App\Enums\GradeScale::tryFrom($grade->letter_grade); @endphp
                            <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $grade->letter_grade }}</span>
                            @else — @endif
                        </td>
                        <td class="text-center">{{ $grade->grade_point ? number_format($grade->grade_point, 2) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="3"><strong>Маҷмӯъ:</strong></td>
                        <td class="text-center"><strong>{{ $grades->sum('credits_earned') }}</strong></td>
                        <td class="text-center"><strong>{{ number_format($grades->avg('total_score'), 0) }}%</strong></td>
                        <td></td>
                        <td class="text-center"><strong>{{ number_format($grades->avg('grade_point'), 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@else
<div class="text-center py-5 text-muted">
    <i class="bi bi-file-earmark-text fs-1 d-block mb-2"></i>
    <p>Transcript ҳоло тайёр нест.</p>
    <small>Пас аз тасдиқи баҳоҳои ниҳоӣ тайёр мешавад.</small>
</div>
@endif
@endsection