@extends('layouts.app')
@section('title', 'Илова кардани савол')
@section('page-header', 'Илова кардани савол')
@section('page-description')
@if($selectedSubject)
Фан: {{ $selectedSubject->name }}
@endif
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.exams.questions.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Фан *</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">— Интихоб —</option>
                                @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ ($selectedSubject?->id == $s->id || old('subject_id') == $s->id) ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Навъи савол *</label>
                            <select name="type" class="form-select" id="questionType" required>
                                <option value="single_choice">Якҷавобӣ</option>
                                <option value="multiple_choice">Чандҷавобӣ</option>
                                <option value="true_false">Дуруст/Нодуруст</option>
                                <option value="matching">Мувофиқоварӣ</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label class="form-label">Сатҳ (1-5)</label>
                            <input type="number" name="difficulty_level" class="form-control" min="1" max="5" value="1" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Матни савол *</label>
                            <textarea name="question_text" class="form-control" rows="3" id="questionText" required
                                placeholder="Саволро нависед... Барои формула: $x^2 + 2x = 0$ ё $$\frac{a}{b}$$"></textarea>
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Барои формула: <code>$x^2$</code> = дар сатр, <code>$$\frac{a}{b}$$</code> = алоҳида.
                                Намунаҳо: <code>$H_2O$</code>, <code>$\sqrt{16}$</code>, <code>$\sum_{i=1}^{n}$</code>
                            </small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Акси савол (ихтиёрӣ)</label>
                            <input type="file" name="question_image" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Акс барои савол (png, jpg, webp), андозаи намуд: 2MB</small>
                        </div>

                        {{-- Пешнамоиши формула --}}
                        <div class="col-12" id="previewSection" style="display:none;">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Пешнамоиш:</small>
                                <div id="questionPreview" class="fs-6"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Шарҳи ҷавоб</label>
                            <textarea name="explanation" class="form-control" rows="2" placeholder="Тавзеҳ (ихтиёрӣ)"></textarea>
                        </div>

                        {{-- === Вариантҳо (single/multiple/true_false) === --}}
                        <div class="col-12" id="optionsSection">
                            <hr>
                            <h6><i class="bi bi-list-check me-2"></i> Вариантҳои ҷавоб</h6>
                            <p class="text-muted small mb-2">авоби дурустро ✓ кунед. Формула истифода кардан мумкин.</p>
                            <div id="optionsContainer">
                                @for($i = 0; $i < 4; $i++)
                                    <div class="row g-2 mb-2 align-items-center">
                                    <div class="col-auto"><strong class="text-primary">{{ chr(65+$i) }}</strong></div>
                                    <div class="col">
                                        <input type="text" name="options[{{ $i }}][text]" class="form-control form-control-sm"
                                            placeholder="Варианти {{ chr(65+$i) }} (формула: $...$)">
                                    </div>
                                    <div class="col-auto">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="options[{{ $i }}][is_correct]" value="1">
                                            <label class="form-check-label text-success d-none d-sm-inline">✓</label>
                                        </div>
                                    </div>
                            </div>
                            @endfor
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="addOption">
                            <i class="bi bi-plus me-1"></i> Варианти дигар
                        </button>
                    </div>

                    {{-- === Мувофиқоварӣ (matching) === --}}
                    <div class="col-12" id="matchingSection" style="display:none;">
                        <hr>
                        <h6><i class="bi bi-arrows-angle-expand me-2"></i> Мувофиқоварӣ</h6>
                        <p class="text-muted small">Зерсаволҳо ва ҷавобҳои дурустро нависед + як ҷавоби иловагӣ.</p>

                        <div id="matchContainer">
                            @for($i = 0; $i < 4; $i++)
                                <div class="row g-2 mb-2 align-items-center">
                                <div class="col-auto"><strong>{{ $i+1 }}.</strong></div>
                                <div class="col">
                                    <input type="text" name="sub_questions[{{ $i }}][text]" class="form-control form-control-sm" placeholder="Зерсавол {{ $i+1 }}">
                                </div>
                                <div class="col-auto"><i class="bi bi-arrow-right text-muted"></i></div>
                                <div class="col">
                                    <input type="text" name="sub_questions[{{ $i }}][match]" class="form-control form-control-sm" placeholder="Ҷавоб {{ $i+1 }}">
                                </div>
                        </div>
                        @endfor
                    </div>

                    <div class="mt-3">
                        <label class="form-label small text-danger">Ҷавоби иловагӣ (лағжанда):</label>
                        <input type="text" name="matching_extra[0][text]" class="form-control form-control-sm" placeholder="Ҷавоби нодуруст ки интихоб карда натавонанд">
                    </div>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between mt-4 gap-2">
            <a href="{{ route('admin.exams.questions.index', ['subject_id' => $selectedSubject?->id]) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Бозгашт
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg me-1"></i> Сабт кардан
            </button>
        </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection

@push('styles')
<style>
    @media (max-width: 576px) {
        .card-body {
            padding: 1rem;
        }

        textarea {
            font-size: 14px;
        }
    }
</style>
@endpush

@push('scripts')
{{-- MathJax барои формулаҳо (алгебра, химия, физика) --}}
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>

<script>
    // Toggle навъ ва DISABLE кардани майдонҳои пинҳон
    document.getElementById('questionType')?.addEventListener('change', function() {
        const isMatching = this.value === 'matching';
        const optionsSection = document.getElementById('optionsSection');
        const matchingSection = document.getElementById('matchingSection');

        if (isMatching) {
            optionsSection.style.display = 'none';
            // Майдонҳои якҷавобиро disabled мекунем, то фиристода нашаванд
            optionsSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);

            matchingSection.style.display = 'block';
            matchingSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);
        } else {
            optionsSection.style.display = 'block';
            optionsSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);

            matchingSection.style.display = 'none';
            // Майдонҳои мувофиқовариро disabled мекунем, то фиристода нашаванд
            matchingSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);
        }
    });

    // Ҳангоми боргирии саҳифа ҳолати дурустро фаъол мекунем
    document.addEventListener('DOMContentLoaded', function() {
        const typeSelect = document.getElementById('questionType');
        if (typeSelect) {
            typeSelect.dispatchEvent(new Event('change'));
        }
    });

    function validateQuestionForm(event) {
        const type = document.getElementById('questionType')?.value;
        if (type === 'matching') return true;

        // Ҳисоб кардани вариантҳои пурра (танҳо онҳое, ки disabled нестанд)
        const filled = [...document.querySelectorAll('#optionsSection input[name$="[text]"]:not([disabled])')]
            .map(el => (el.value || '').trim())
            .filter(Boolean);

        if (filled.length < 4) {
            event.preventDefault();
            alert('Ҳар як саволи якҷавобӣ/чандҷавобӣ бояд ҳадди аққал 4 варианти ҷавоб дошта бошад.');
            return false;
        }

        const correctCount = [...document.querySelectorAll('#optionsSection input[name$="[is_correct]"]:not([disabled])')]
            .filter(el => el.checked).length;

        if (['single_choice', 'true_false'].includes(type) && correctCount !== 1) {
            event.preventDefault();
            alert('Саволи якҷавобӣ/дуруст-нодуруст бояд танҳо 1 ҷавоби дуруст дошта бошад.');
            return false;
        }

        return true;
    }

    let optionCount = 4;
    document.getElementById('addOption')?.addEventListener('click', function() {
        const l = String.fromCharCode(65 + optionCount);
        const html = `<div class="row g-2 mb-2 align-items-center"><div class="col-auto"><strong class="text-primary">${l}</strong></div><div class="col"><input type="text" name="options[${optionCount}][text]" class="form-control form-control-sm" placeholder="Варианти ${l}"></div><div class="col-auto"><div class="form-check"><input class="form-check-input" type="checkbox" name="options[${optionCount}][is_correct]" value="1"><label class="form-check-label text-success d-none d-sm-inline">✓</label></div></div></div>`;
        document.getElementById('optionsContainer').insertAdjacentHTML('beforeend', html);
        optionCount++;
    });

    const form = document.querySelector('form');
    form?.addEventListener('submit', validateQuestionForm);

    // Пешнамоиши формула (live)
    const textarea = document.getElementById('questionText');
    const previewSection = document.getElementById('previewSection');
    const previewDiv = document.getElementById('questionPreview');

    textarea?.addEventListener('input', function() {
        const text = this.value;
        if (text.includes('$')) {
            previewSection.style.display = 'block';
            previewDiv.innerHTML = text.replace(/\n/g, '<br>');
            if (window.MathJax) {
                MathJax.typesetPromise([previewDiv]);
            }
        } else {
            previewSection.style.display = 'none';
        }
    });
</script>
@endpush