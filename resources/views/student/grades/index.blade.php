@extends('layouts.app')
@section('title', 'Баҳоҳои ман')
@section('page-header', 'Баҳоҳои ман')
@section('page-description', 'Натиҷаҳои таҳсил дар ҳамаи солҳо')

@section('content')
@forelse($grades as $yearId => $semestersGrades)
@php
    $firstSem = $semestersGrades->first()['semester'] ?? null;
    $year = $firstSem?->academicYear ?? null;
    $isCurrent = ($year?->id ?? $yearId) === $currentYearId;
    $collapseId = 'gradesYear' . $yearId;
@endphp
<div class="accordion-item border-0 shadow-sm mb-3" style="border-radius: 16px; border: 1px solid #e8edf5;">
    <h2 class="accordion-header" id="heading{{ $yearId }}">
        <button class="accordion-button {{ $isCurrent ? '' : 'collapsed' }}"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#{{ $collapseId }}"
                aria-expanded="{{ $isCurrent ? 'true' : 'false' }}"
                aria-controls="{{ $collapseId }}"
                style="border-radius: 16px 16px 0 0;">
            <div class="d-flex align-items-center justify-content-between w-100">
                <span class="fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-journal-bookmark text-primary"></i>
                    {{ $year?->name ?? ('Соли ' . $yearId) }}
                    @if($isCurrent)
                        <span class="badge bg-primary text-white" style="font-size: 0.7rem;">Соли ҷорӣ</span>
                    @endif
                </span>
                <i class="bi bi-chevron-down accordion-chevron"></i>
            </div>
        </button>
    </h2>
    <div id="{{ $collapseId }}"
         class="accordion-collapse collapse {{ $isCurrent ? 'show' : '' }}"
         data-bs-parent="#gradesAccordion"
         aria-labelledby="heading{{ $yearId }}">
        <div class="accordion-body p-0">
            @foreach($semestersGrades as $semesterId => $semGrades)
            @php $sem = $semGrades->first()['semester'] ?? null; @endphp
            <div class="px-3 pt-3">
                <h6 class="mb-2 text-muted small fw-semibold">
                    <i class="bi bi-calendar3 me-1"></i>{{ $sem?->name ?? "Семестр #{$semesterId}" }}
                </h6>
            </div>
            <div class="table-responsive mb-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Фан</th>
                            <th class="text-center">R1</th>
                            <th class="text-center">R2</th>
                            <th class="text-center">Имтиҳон</th>
                            <th class="text-center">Ниҳоии</th>
                            <th class="text-center">Баҳо</th>
                            <th class="text-center">Ҳолат</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($semGrades as $item)
                        @php
                            $grade = $item['semester_grade'];
                            $calc = $item;
                        @endphp
                        <tr>
                            <td>{{ $item['subject']?->name ?? '—' }}</td>
                            <td class="text-center">{{ $calc['rating1'] !== null ? number_format($calc['rating1'], 0) : '—' }}</td>
                            <td class="text-center">{{ $calc['rating2'] !== null ? number_format($calc['rating2'], 0) : '—' }}</td>
                            <td class="text-center">{{ $calc['exam'] !== null ? number_format($calc['exam'], 0) : '—' }}</td>
                            <td class="text-center"><strong>{{ $calc['total_score'] !== null ? number_format($calc['total_score'], 1) : '—' }}</strong></td>
                            <td class="text-center">
                                @if($calc['letter_grade'])
                                    @php $g = \App\Enums\GradeScale::tryFrom($calc['letter_grade']); @endphp
                                    <span class="badge {{ $g?->badgeClass() ?? 'bg-secondary' }}">{{ $calc['letter_grade'] }}</span>
                                @else — @endif
                            </td>
                            <td class="text-center">
                                @php
                                    $statusLabel = match($calc['status']) {
                                        'passed' => ['Гузашт', 'success'],
                                        'failed' => ['Нагузашт', 'danger'],
                                        'retake' => ['Такрорсупорӣ', 'warning'],
                                        'in_progress' => ['Дар ҷараён', 'info'],
                                        default => [$calc['status'] ?? '—', 'secondary'],
                                    };
                                @endphp
                                <span class="badge bg-{{ $statusLabel[1] }}">{{ $statusLabel[0] }}</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>
    </div>
</div>
@empty
<div class="text-center py-5 text-muted">
    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
    <p>Баҳое ҳоло мавҷуд нест.</p>
</div>
@endforelse
@push('scripts')
<script>
document.querySelectorAll('.accordion-button[data-bs-toggle="collapse"]').forEach(function(btn) {
    var chevron = btn.querySelector('.accordion-chevron');
    if (!chevron) return;
    function update() {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        chevron.style.transform = expanded ? 'rotate(180deg)' : '';
    }
    btn.addEventListener('click', update);
    update();
});
</script>
@endpush
@endsection