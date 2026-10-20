@extends('layouts.app')

@section('title', 'Саволномаҳои рейтинг')
@section('page-header', 'Саволномаҳои рейтинг')
@section('page-description', 'Саволҳои алоҳида барои рейтингҳои онлайн (R1/R2)')

@section('content')
@if(!$subjectId)
<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-lg-4 col-md-6">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" id="subjectSearch" class="form-control" placeholder="Ҷустуҷӯи фан...">
                </div>
            </div>
            <div class="col-lg-3 col-md-4">
                <select id="subjectFilter" class="form-select">
                    <option value="all">Ҳамаи фанҳо</option>
                    <option value="has">Саволдор</option>
                    <option value="none">Бе савол</option>
                </select>
            </div>
            <div class="col-lg-5 col-md-2 text-md-end">
                <a href="{{ route('admin.rating-questions.import-form') }}" class="btn btn-outline-primary">
                    <i class="bi bi-upload"></i> Импорт
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3" id="subjectsGrid">
    @foreach($subjects as $s)
    @php
        $cnt = $counts[$s->id] ?? 0;
    @endphp
    <div class="col-12 col-sm-6 col-md-4 col-lg-3 subject-card-wrap" data-name="{{ mb_strtolower($s->name) }}" data-count="{{ $cnt }}">
        <a href="{{ route('admin.rating-questions.index', ['subject_id' => $s->id]) }}" class="text-decoration-none text-dark d-block h-100">
            <div class="card border h-100 subject-card">
                <div class="card-body d-flex align-items-center">
                    <div class="me-2 text-primary"><i class="bi bi-journal-text fs-5"></i></div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold">{{ $s->name }}</div>
                    </div>
                    <span class="badge {{ $cnt > 0 ? 'bg-secondary' : 'bg-light text-dark border' }} rounded-pill">{{ $cnt }}</span>
                </div>
            </div>
        </a>
    </div>
    @endforeach
</div>
<div id="subjectsNoMatch" class="text-center text-muted py-5 d-none">Фан ёфт нашуд</div>

<style>
    .subject-card { transition: all .15s ease-in-out; }
    .subject-card:hover { border-color: var(--bs-primary) !important; background-color: rgba(var(--bs-primary-rgb), .02); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function(){
        const search = document.getElementById('subjectSearch');
        const filter = document.getElementById('subjectFilter');
        const cards = document.querySelectorAll('.subject-card-wrap');
        const noMatch = document.getElementById('subjectsNoMatch');
        function apply(){
            if(!cards.length) return;
            const q = (search && search.value ? search.value : '').toLowerCase().trim();
            const f = filter ? filter.value : 'all';
            let visible = 0;
            cards.forEach(c => {
                const name = (c.getAttribute('data-name') || '').toLowerCase().trim();
                const cnt = parseInt(c.getAttribute('data-count') || '0');
                let ok = true;
                if(q && name.indexOf(q) === -1) ok = false;
                if(ok){
                    if(f === 'has' && cnt <= 0) ok = false;
                    if(f === 'none' && cnt > 0) ok = false;
                }
                if(ok){
                    c.classList.remove('d-none');
                    visible++;
                }else{
                    c.classList.add('d-none');
                }
            });
            if(noMatch) noMatch.classList.toggle('d-none', visible !== 0);
        }
        if(search) search.addEventListener('input', apply);
        if(filter) filter.addEventListener('change', apply);
        apply();
    });
</script>
@else
@php
    $selectedSubject = $subjects->firstWhere('id', $subjectId);
@endphp
<div class="mb-3">
    <a href="{{ route('admin.rating-questions.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Ба рӯйхати фанҳо
    </a>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-0"><i class="bi bi-journal-text me-2"></i>{{ $selectedSubject->name }}</h6>
            <small class="text-muted">Саволҳои рейтинг</small>
        </div>
        <div class="btn-group">
            <a href="{{ route('admin.rating-questions.export', ['subject_id' => $subjectId]) }}" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Excel
            </a>
            <a href="{{ route('admin.rating-questions.import-form') }}" class="btn btn-sm btn-outline-warning">
                <i class="bi bi-file-earmark-excel"></i> Импорти Excel
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.rating-questions.store') }}" class="row g-3">
            @csrf
            <input type="hidden" name="subject_id" value="{{ $subjectId }}">

            <div class="col-md-12">
                <label class="form-label">Матни савол <span class="text-danger">*</span></label>
                <textarea name="question_text" rows="2" class="form-control" required>{{ old('question_text') }}</textarea>
            </div>

            <div class="col-md-12">
                <label class="form-label">4 вариант <span class="text-danger">*</span> <small class="text-muted">(радиои дурустро интихоб кунед)</small></label>
                @for($i = 0; $i < 4; $i++)
                    <div class="input-group mb-2">
                        <div class="input-group-text">
                            <input class="form-check-input mt-0" type="radio" name="correct" value="{{ $i }}"
                                {{ old('correct') == $i ? 'checked' : '' }} required>
                        </div>
                        <input type="text" name="options[{{ $i }}]" class="form-control"
                            placeholder="Варианти {{ $i + 1 }}" required value="{{ old("options.$i") }}">
                    </div>
                @endfor
            </div>

            <div class="col-md-6">
                <label class="form-label">Душворӣ</label>
                <select name="difficulty_level" class="form-select">
                    <option value="1">Осон</option>
                    <option value="2">Миёна</option>
                    <option value="3">Душвор</option>
                </select>
            </div>

            <div class="col-md-12">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Илова кардан
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <h6 class="mb-0"><i class="bi bi-list-check me-2"></i>Саволҳои фан</h6>
            <span class="badge bg-primary">{{ $counts[$subjectId] ?? 0 }} савол</span>
        </div>
        <div style="min-width: 220px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="questionSearch" class="form-control" placeholder="Ҷустуҷӯи савол...">
            </div>
        </div>
    </div>
    <div class="card-body p-0" id="questionsList">
        @forelse($questions as $q)
        <div class="p-3 border-bottom question-item" data-question="{{ mb_strtolower($q->question_text) }}">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <p class="mb-1">{{ $q->question_text }}</p>
                    <small class="text-muted">
                        @foreach($q->answerOptions as $opt)
                            <span class="me-3 {{ $opt->is_correct ? 'text-success fw-bold' : 'text-muted' }}">
                                {{ $opt->sort_order }}. {{ $opt->option_text }}
                            </span>
                        @endforeach
                    </small>
                </div>
                <form method="POST" action="{{ route('admin.rating-questions.destroy', $q) }}"
                    onsubmit="return confirm('Савол нест шавад?')" class="ms-2">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
        @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-hand-index fs-1 d-block mb-2"></i>
            Ҳанӯз савол нест.
        </div>
        @endforelse
    </div>
    <div id="questionsNoMatch" class="text-center text-muted py-4 d-none">Савол ёфт нашуд</div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function(){
        const search = document.getElementById('questionSearch');
        const items = document.querySelectorAll('.question-item');
        const noMatch = document.getElementById('questionsNoMatch');
        if(!search) return;
        search.addEventListener('input', function(){
            const q = (this.value || '').toLowerCase().trim();
            let visible = 0;
            items.forEach(item => {
                const text = (item.getAttribute('data-question') || '').toLowerCase().trim();
                const ok = q === '' || text.indexOf(q) !== -1;
                if(ok){
                    item.classList.remove('d-none');
                    visible++;
                }else{
                    item.classList.add('d-none');
                }
            });
            if(noMatch){
                noMatch.classList.toggle('d-none', visible !== 0 || items.length === 0);
            }
        });
    });
</script>
@endif
@endsection
