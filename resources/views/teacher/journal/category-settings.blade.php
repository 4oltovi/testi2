@extends('layouts.app')

@section('title', 'Танзимоти категорияҳо')
@section('page-header', 'Танзимоти категорияҳои баҳо')
@section('page-description')
    {{ $subjectAssignment->curriculum?->subject?->name }} | {{ $subjectAssignment->group?->name }}
@endsection

@section('content')
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0">
                <i class="bi bi-gear me-2"></i>
                Танзимоти ҳадди аксари баҳо дар ҳар категория
            </h6>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Шумо метавонед ҳадди аксари баҳоро дар ҳар категория мутобиқи фани худ танзим кунед.
                «Савод» ҳамеша бояд баландтарин бошад.
            </div>

            <form method="POST" action="{{ route((request()->is('admin/*') ? 'admin.journal' : 'teacher.journal') . '.category-settings.update', $subjectAssignment) }}">
                @csrf
                @method('PUT')

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Категория</th>
                            <th style="width: 200px;">Ҳадди аксари балл</th>
                            <th style="width: 100px;">Icon</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorySettings as $index => $cs)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <input type="hidden" name="settings[{{ $index }}][category]" value="{{ $cs->category->value }}">
                                    <strong class="text-{{ $cs->category->colorClass() }}">
                                        <i class="bi {{ $cs->category->icon() }} me-2"></i>
                                        {{ $cs->category->label() }}
                                    </strong>
                                </td>
                                <td>
                                    <input type="number" name="settings[{{ $index }}][max_score]"
                                           class="form-control" min="0.5" max="100" step="0.5"
                                           value="{{ $cs->max_score }}">
                                </td>
                                <td class="text-center">
                                    <i class="bi {{ $cs->category->icon() }} fs-4 text-{{ $cs->category->colorClass() }}"></i>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2"><strong>Маҷмӯъи ҳадди аксар (барои як дарс):</strong></td>
                            <td><strong id="totalMax">{{ $categorySettings->sum('max_score') }}</strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="text-end">
                    <a href="{{ route((request()->is('admin/*') ? 'admin.journal' : 'teacher.journal') . '.category-scores', $subjectAssignment) }}" class="btn btn-outline-secondary me-2">
                        <i class="bi bi-arrow-left me-1"></i> Бозгашт
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i> Сабт
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="number"]');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            let total = 0;
            inputs.forEach(i => { total += parseInt(i.value) || 0; });
            document.getElementById('totalMax').textContent = total;
        });
    });
});
</script>
@endpush
