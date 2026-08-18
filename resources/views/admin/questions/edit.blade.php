@extends('layouts.app')
@section('title', 'Таҳрири савол')
@section('page-header', 'Таҳрири савол')
@section('page-description', 'Савол, вариантҳо ва навъи онро навсоз кунед')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.exams.questions.update', $question) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Фан *</label>
                            <select name="subject_id" class="form-select @error('subject_id') is-invalid @enderror" required>
                                <option value="">— Интихоб —</option>
                                @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ old('subject_id', $question->subject_id) == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('subject_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label">Навъи савол *</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" id="questionType" required>
                                <option value="single_choice" {{ old('type', $question->type) == 'single_choice' ? 'selected' : '' }}>Якҷавобӣ</option>
                                <option value="multiple_choice" {{ old('type', $question->type) == 'multiple_choice' ? 'selected' : '' }}>Чандҷавобӣ</option>
                                <option value="true_false" {{ old('type', $question->type) == 'true_false' ? 'selected' : '' }}>Дуруст/Нодуруст</option>
                                <option value="matching" {{ old('type', $question->type) == 'matching' ? 'selected' : '' }}>Мувофиқоварӣ</option>
                            </select>
                            @error('type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-6 col-md-4">
                            <label class="form-label">Сатҳ (1-5)</label>
                            <input type="number" name="difficulty_level" class="form-control @error('difficulty_level') is-invalid @enderror" min="1" max="5" value="{{ old('difficulty_level', $question->difficulty_level) }}" required>
                            @error('difficulty_level')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Матни савол *</label>
                            <textarea name="question_text" class="form-control @error('question_text') is-invalid @enderror" rows="3" id="questionText" required>{{ old('question_text', $question->question_text) }}</textarea>
                            @error('question_text')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        @if($question->question_image)
                        <div class="col-12">
                            <label class="form-label">Акси ҷорӣ</label>
                            <div class="mb-2">
                                <img src="{{ asset($question->question_image) }}" alt="Акси савол" style="max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 4px;">
                            </div>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label">Акси савол (ихтиёрӣ)</label>
                            <input type="file" name="question_image" class="form-control" accept="image/png,image/jpeg,image/webp">
                            <small class="text-muted">Акс барои савол (png, jpg, webp), андозаи намуд: 2MB</small>
                        </div>

                        <div class="col-12" id="previewSection" style="display:none;">
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-1">Пешнамоиш:</small>
                                <div id="questionPreview" class="fs-6"></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Шарҳи ҷавоб</label>
                            <textarea name="explanation" class="form-control @error('explanation') is-invalid @enderror" rows="2" placeholder="Тавзеҳ (ихтиёрӣ)">{{ old('explanation', $question->explanation) }}</textarea>
                        </div>

                        @php
                        $options = $question->answerOptions->sortBy('sort_order')->values();
                        $isMatching = old('type', $question->type) === 'matching';
                        @endphp

                        {{-- === Вариантҳо (single/multiple/true_false) === --}}
                        <div class="col-12" id="optionsSection" style="{{ $isMatching ? 'display:none;' : '' }}">
                            <hr>
                            <h6><i class="bi bi-list-check me-2"></i> Вариантҳои ҷавоб</h6>
                            <p class="text-muted small mb-2">Ҳар як савол бояд 4 варианти ҷавоб дошта бошад.</p>

                            <div id="optionsContainer">
                                @for($i = 0; $i < 4; $i++)
                                    @php
                                    $option=$options->get($i);
                                    $text = old('options.' . $i . '.text', $option?->option_text ?? '');
                                    $checked = old('options.' . $i . '.is_correct', $option?->is_correct ?? false) ? 'checked' : '';
                                    @endphp
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-auto"><strong class="text-primary">{{ chr(65 + $i) }}</strong></div>
                                        <div class="col">
                                            <input type="text" name="options[{{ $i }}][text]" value="{{ $text }}" class="form-control form-control-sm" placeholder="Варианти {{ chr(65 + $i) }}">
                                        </div>
                                        <div class="col-auto">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="options[{{ $i }}][is_correct]" value="1" {{ $checked }}>
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
                        <div class="col-12" id="matchingSection" style="{{ $isMatching ? '' : 'display:none;' }}">
                            <hr>
                            <h6><i class="bi bi-arrows-angle-expand me-2"></i> Мувофиқоварӣ</h6>
                            <p class="text-muted small">Зерсаволҳо ва ҷавобҳои дурустро нависед.</p>

                            <div id="matchContainer">
                                @for($i = 0; $i < 4; $i++)
                                    @php
                                    $pair=$options->first(function($opt) use ($i) {
                                    return str_contains($opt->option_text ?? '', '|||');
                                    });
                                    $parts = $pair ? explode('|||', $pair->option_text) : ['', ''];
                                    @endphp
                                    <div class="row g-2 mb-2 align-items-center">
                                        <div class="col-auto"><strong>{{ $i+1 }}.</strong></div>
                                        <div class="col">
                                            <input type="text" name="sub_questions[{{ $i }}][text]" value="{{ old('sub_questions.' . $i . '.text', $parts[0] ?? '') }}" class="form-control form-control-sm" placeholder="Зерсавол {{ $i+1 }}">
                                        </div>
                                        <div class="col-auto"><i class="bi bi-arrow-right text-muted"></i></div>
                                        <div class="col">
                                            <input type="text" name="sub_questions[{{ $i }}][match]" value="{{ old('sub_questions.' . $i . '.match', $parts[1] ?? '') }}" class="form-control form-control-sm" placeholder="Ҷавоб {{ $i+1 }}">
                                        </div>
                                    </div>
                                    @endfor
                            </div>

                            <div class="mt-3">
                                <label class="form-label small text-danger">Ҷавоби иловагӣ (лағжанда):</label>
                                @php
                                $extraOption = $options->first(fn($opt) => !$opt->is_correct && !str_contains($opt->option_text ?? '', '|||'));
                                @endphp
                                <!-- МУҲИМ: Номи майдон ба matching_extra иваз шуд -->
                                <input type="text" name="matching_extra[0][text]" value="{{ old('matching_extra.0.text', $extraOption?->option_text ?? '') }}" class="form-control form-control-sm" placeholder="Ҷавоби нодуруст ки интихоб карда натавонанд">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between mt-4 gap-2">
                        <a href="{{ route('admin.exams.questions.index', ['subject_id' => $question->subject_id]) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Бозгашт
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i> Навсозӣ кардан
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
<script>
    const questionType = document.getElementById('questionType');
    const optionsSection = document.getElementById('optionsSection');
    const matchingSection = document.getElementById('matchingSection');

    // Функсияи асосӣ барои пинҳон/фаъол кардан ва DISABLE кардани майдонҳо
    function toggleQuestionType() {
        const isMatching = questionType.value === 'matching';

        if (isMatching) {
            optionsSection.style.display = 'none';
            // Майдонҳои якҷавобиро disabled мекунем, то ба сервер фиристода нашаванд
            optionsSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);

            matchingSection.style.display = 'block';
            matchingSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);
        } else {
            optionsSection.style.display = 'block';
            optionsSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = false);

            matchingSection.style.display = 'none';
            // Майдонҳои мувофиқовариро disabled мекунем, то ба сервер фиристода нашаванд
            matchingSection.querySelectorAll('input, textarea, select').forEach(el => el.disabled = true);
        }
    }

    function validateQuestionForm(event) {
        const type = questionType?.value;
        if (type === 'matching') return true;

        // Танҳо майдонҳои disabled-нашударо ҳисоб мекунем
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

    // Ҳангоми тағйири намуд ва ҳангоми боргирии саҳифа иҷро мешавад
    questionType?.addEventListener('change', toggleQuestionType);
    document.addEventListener('DOMContentLoaded', toggleQuestionType);

    let optionCount = 4;
    document.getElementById('addOption')?.addEventListener('click', function() {
        const nextLetter = String.fromCharCode(65 + optionCount);
        const html = `
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-auto"><strong class="text-primary">${nextLetter}</strong></div>
                <div class="col">
                    <input type="text" name="options[${optionCount}][text]" class="form-control form-control-sm" placeholder="Варианти ${nextLetter}">
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="options[${optionCount}][is_correct]" value="1">
                        <label class="form-check-label text-success d-none d-sm-inline">✓</label>
                    </div>
                </div>
            </div>`;
        document.getElementById('optionsContainer').insertAdjacentHTML('beforeend', html);
        optionCount++;
    });

    const textarea = document.getElementById('questionText');
    const previewSection = document.getElementById('previewSection');
    const previewDiv = document.getElementById('questionPreview');

    function updatePreview() {
        const text = textarea.value;
        if (text.includes('$')) {
            previewSection.style.display = 'block';
            previewDiv.innerHTML = text.replace(/\n/g, '<br>');
            if (window.MathJax) {
                MathJax.typesetPromise([previewDiv]);
            }
        } else {
            previewSection.style.display = 'none';
        }
    }

    textarea?.addEventListener('input', updatePreview);
    updatePreview();
    document.querySelector('form')?.addEventListener('submit', validateQuestionForm);
</script>
@endpush