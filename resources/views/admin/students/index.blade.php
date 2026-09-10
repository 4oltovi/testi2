@extends('layouts.app')

@section('title', 'Донишҷӯён')
@section('page-header', 'Донишҷӯён')
@section('page-description', 'Идоракунии донишҷӯён')

@section('page-actions')
    <a href="{{ route('admin.students.import-form') }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Импорт аз Excel
    </a>
    <a href="{{ route('admin.students.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Донишҷӯи нав
    </a>
@endsection

@section('content')
    @if(session('generated_passwords') && count(session('generated_passwords')) > 0)
        <div class="card border-0 shadow-sm mb-4 border-start border-warning border-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-key me-2 text-warning"></i>
                    Паролҳои сохташуда
                    <span class="badge bg-warning text-dark ms-2">{{ count(session('generated_passwords')) }}</span>
                </h6>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="copyPasswords()">
                        <i class="bi bi-clipboard me-1"></i> Нусхабардорӣ
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#passwordList">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>
            </div>
            <div class="collapse show" id="passwordList">
                <div class="card-body p-0">
                    <div class="alert alert-warning rounded-0 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Ин паролҳоро ҳозир нусхабардорӣ кунед! Барои бехатарӣ донишҷӯён ҳангоми воридшавӣ бояд паролашонро иваз кунанд (<code>must_change_password = true</code>).
                    </div>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th>ID донишҷӯӣ</th>
                                    <th>Ном</th>
                                    <th>Парол</th>
                                </tr>
                            </thead>
                            <tbody id="passwordTable">
                                @foreach(session('generated_passwords') as $index => $entry)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><code>{{ $entry['student_id'] }}</code></td>
                                        <td>{{ $entry['name'] }}</td>
                                        <td>
                                            <code class="text-primary fw-bold">{{ $entry['password'] }}</code>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function copyPasswords() {
                const rows = document.querySelectorAll('#passwordTable tr');
                let text = 'ID донишҷӯӣ\tНом\tПарол\n';
                rows.forEach(row => {
                    const cols = row.querySelectorAll('td');
                    if (cols.length === 4) {
                        text += cols[1].innerText.trim() + '\t' + cols[2].innerText.trim() + '\t' + cols[3].innerText.trim() + '\n';
                    }
                });
                navigator.clipboard.writeText(text).then(() => {
                    alert('Паролҳо ба clipboard нусхабардорӣ шуданд!');
                });
            }
        </script>
    @endif

    {{-- Филтрҳо --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" class="row g-2 align-items-end" id="filterForm">
                <div class="col-md-3" x-data="{ search: '{{ request('search') }}', results: [], showResults: false, loading: false }" @click.away="showResults = false">
                    <label class="form-label small text-muted">Ҷустуҷӯ</label>
                    <input type="text" x-model="search" @input.debounce.300ms="
                        if (search.length >= 2) {
                            loading = true;
                            fetch('{{ route('admin.students.search') }}?' + new URLSearchParams({ search: search, group_id: '{{ request('group_id') }}', course_id: '{{ request('course_id') }}', status: '{{ request('status') }}' }))
                                .then(r => r.json())
                                .then(data => { results = data; showResults = true; loading = false; })
                                .catch(() => { loading = false; });
                        } else {
                            results = []; showResults = false;
                        }
                    " class="form-control" placeholder="Ном, насаб ё рақами дон...">
                    <div x-show="loading" class="text-muted small mt-1">Ҷустуҷӯ...</div>
                    <div x-show="showResults && results.length > 0" class="dropdown-menu show position-absolute w-100 mt-1" style="z-index: 1000; max-height: 300px; overflow-y: auto;">
                        <template x-for="item in results" :key="item.id">
                            <a :href="item.url" class="dropdown-item">
                                <strong x-text="item.name"></strong>
                                <small class="text-muted d-block" x-text="item.student_id + ' | ' + item.group"></small>
                            </a>
                        </template>
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="group_id" class="form-select">
                        <option value="">Ҳама гурӯҳҳо</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="course_id" class="form-select">
                        <option value="">Ҳама курсҳо</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Ҳама ҳолатҳо</option>
                        @foreach(\App\Enums\StudentStatus::cases() as $s)
                            <option value="{{ $s->value }}" {{ request('status') == $s->value ? 'selected' : '' }}>{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-outline-primary w-100"><i class="bi bi-search"></i></button>
                </div>
                <div class="col-md-1">
                    <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg"></i></a>
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
                            <th>#</th>
                            <th>Донишҷӯ</th>
                            <th>Рақами дон.</th>
                            <th>Гурӯҳ</th>
                            <th>Курс</th>
                            <th>GPA</th>
                            <th>Шакл</th>
                            <th>Ҳолат</th>
                            <th class="text-end">Амалҳо</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr>
                                <td>{{ $student->id }}</td>
                                <td>
                                    <a href="{{ route('admin.students.show', $student) }}" class="text-decoration-none fw-semibold">
                                        {{ $student->user?->full_name }}
                                    </a>
                                </td>
                                <td><code>{{ $student->student_id_number }}</code></td>
                                <td><span class="badge bg-info">{{ $student->group?->name }}</span></td>
                                <td>{{ $student->course?->number }}</td>
                                <td>
                                    <strong class="{{ $student->cumulative_gpa >= 3.0 ? 'text-success' : ($student->cumulative_gpa >= 2.0 ? 'text-warning' : 'text-danger') }}">
                                        {{ number_format($student->cumulative_gpa, 2) }}
                                    </strong>
                                </td>
                                <td>
                                    <small>{{ $student->education_form === 'budget' ? 'Б' : 'Ш' }}</small>
                                </td>
                                <td>
                                    <span class="badge {{ $student->status->badgeClass() }}">
                                        {{ $student->status->label() }}
                                    </span>
                                    @if($student->has_debts)
                                        <span class="badge bg-danger">Қарздор</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.students.show', $student) }}" class="btn btn-sm btn-outline-info" title="Дидан">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-sm btn-outline-primary" title="Таҳрир">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                                    Донишҷӯе ёфт нашуд.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($students->hasPages())
            <div class="card-footer bg-white">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
