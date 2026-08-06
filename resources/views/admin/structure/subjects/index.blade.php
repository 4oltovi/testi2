@extends('layouts.app')

@section('title', 'Фанҳо')
@section('page-header', 'Фанҳо (Предметҳо)')
@section('page-description', 'Идоракунии фанҳои таълимӣ')

@section('page-actions')
    <a href="{{ route('admin.structure.subjects.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Фани нав
    </a>
@endsection

@section('content')
    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Ном ё рамзи фан..."
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="department_id" class="form-select">
                        <option value="">Ҳама кафедраҳо</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->faculty?->short_name }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="exam_type" class="form-select">
                        <option value="">Ҳама навъҳо</option>
                        <option value="exam" {{ request('exam_type') == 'exam' ? 'selected' : '' }}>Имтиҳон</option>
                        <option value="credit" {{ request('exam_type') == 'credit' ? 'selected' : '' }}>Синҷиш</option>
                        <option value="diff_credit" {{ request('exam_type') == 'diff_credit' ? 'selected' : '' }}>Синҷиши бо баҳо</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i></button>
                    <a href="{{ route('admin.structure.subjects.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Ҷадвал --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Фан</th>
                            <th>Рамз</th>
                            <th>Кафедра</th>
                            <th>Кредит</th>
                            <th>Соатҳо</th>
                            <th>Навъи санҷиш</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($subjects as $subject)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.structure.subjects.show', $subject) }}" class="fw-semibold text-decoration-none">
                                        {{ $subject->name }}
                                    </a>
                                    @if($subject->short_name)
                                        <br><small class="text-muted">{{ $subject->short_name }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $subject->code }}</code></td>
                                <td><small>{{ $subject->department?->short_name ?? $subject->department?->name }}</small></td>
                                <td><span class="badge bg-primary">{{ $subject->credits }}</span></td>
                                <td>{{ $subject->total_hours }} соат</td>
                                <td>
                                    @php
                                        $examLabel = match($subject->exam_type) {
                                            'exam' => ['Имтиҳон', 'bg-danger'],
                                            'credit' => ['Синҷиш', 'bg-info'],
                                            'diff_credit' => ['Синҷ. бо баҳо', 'bg-warning'],
                                            default => [$subject->exam_type, 'bg-secondary'],
                                        };
                                    @endphp
                                    <span class="badge {{ $examLabel[1] }}">{{ $examLabel[0] }}</span>
                                </td>
                                <td>
                                    @if($subject->is_active)
                                        <span class="badge bg-success">Фаъол</span>
                                    @else
                                        <span class="badge bg-secondary">Ғайрифаъол</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.structure.subjects.edit', $subject) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-book fs-1 d-block mb-2"></i>
                                    Фане ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($subjects->hasPages())
            <div class="card-footer bg-white">{{ $subjects->links() }}</div>
        @endif
    </div>
@endsection
