@extends('layouts.app')

@section('title', 'Маълумоти фан: ' . $subject->name)
@section('page-header', 'Маълумоти фан')
@section('page-description', $subject->name)

@section('content')
<div class="row">
    <div class="col-lg-8">
        {{-- Маълумоти асосӣ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-book me-2"></i> Маълумоти асосӣ</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tbody>
                        <tr>
                            <th class="text-muted" style="width: 200px;">Рамз</th>
                            <td>{{ $subject->code }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ихтисор</th>
                            <td>{{ $subject->short_name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кафедра</th>
                            <td>{{ $subject->department?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Факултет</th>
                            <td>{{ $subject->department?->faculty?->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Кредит</th>
                            <td>{{ $subject->credits }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Соатҳои умумӣ</th>
                            <td>{{ $subject->total_hours }}</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Навъи санҷиш</th>
                            <td>
                                @switch($subject->exam_type)
                                @case('exam') Имтиҳон @break
                                @case('credit') Синҷиш @break
                                @case('diff_credit') Синҷиши бо баҳо @break
                                @default {{ $subject->exam_type }}
                                @endswitch
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Ҳолат</th>
                            <td>
                                @if($subject->is_active)
                                <span class="badge bg-success">Фаъол</span>
                                @else
                                <span class="badge bg-secondary">Ғайрифаъол</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="text-muted">Тавсиф</th>
                            <td>{{ $subject->description ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Нақшаи таълимӣ --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent">
                <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i> Нақшаи таълимӣ</h6>
            </div>
            <div class="card-body">
                @if($subject->subjectAssignments && $subject->subjectAssignments->count() > 0)
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Ихтисос</th>
                            <th>Семестр</th>
                            <th>Кредитҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subject->subjectAssignments as $assignment)
                        <tr>
                            <td>{{ $assignment->specialty->name }}</td>
                            <td>{{ $assignment->semester->name }}</td>
                            <td>{{ $subject->credits }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted mb-0">Дар нақшаи таълимӣ ёфт нашуд</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <a href="{{ route('admin.structure.subjects.edit', $subject) }}" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-pencil me-1"></i> Таҳрир кардан
                </a>
                <a href="{{ route('admin.structure.subjects.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i> Бозгашт
                </a>
            </div>
        </div>
    </div>
</div>
@endsection