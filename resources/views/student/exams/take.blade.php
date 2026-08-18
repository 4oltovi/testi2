<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $exam->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            min-height: 100vh;
            transition: background .3s, color .3s;
        }

        body.dark {
            background: #0F172A;
            color: #F1F5F9;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 24px 16px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .header h1 {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .header-meta {
            font-size: 0.85rem;
            color: #6B7280;
        }

        body.dark .header-meta {
            color: #94A3B8;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .timer {
            font-size: 1.1rem;
            font-weight: 600;
            background: #FEF3C7;
            color: #92400E;
            padding: 6px 14px;
            border-radius: 8px;
        }

        .timer.danger {
            background: #FEE2E2;
            color: #DC2626;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .6;
            }
        }

        .dark-toggle {
            cursor: pointer;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            font-size: 1.1rem;
        }

        body.dark .dark-toggle {
            background: #1E293B;
            border-color: #334155;
        }

        .progress-wrap {
            width: 100%;
            height: 6px;
            background: #E5E7EB;
            border-radius: 3px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        body.dark .progress-wrap {
            background: #334155;
        }

        .progress-bar {
            height: 100%;
            background: #4F46E5;
            border-radius: 3px;
            transition: width .3s;
        }

        .nav-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 20px;
        }

        .nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            background: #fff;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
        }

        body.dark .nav-btn {
            background: #1E293B;
            border-color: #334155;
            color: #F1F5F9;
        }

        .nav-btn.active {
            background: #4F46E5;
            color: #fff;
            border-color: #4F46E5;
        }

        .nav-btn.answered {
            background: #10B981;
            color: #fff;
            border-color: #10B981;
        }

        .card {
            background: #fff;
            border-radius: 12px;
            padding: 28px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
            border: 1px solid #E5E7EB;
            margin-bottom: 20px;
        }

        body.dark .card {
            background: #1E293B;
            border-color: #334155;
        }

        .question-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4F46E5;
            margin-bottom: 8px;
        }

        .question-text {
            font-size: 1.05rem;
            font-weight: 500;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .question-points {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 16px;
        }

        body.dark .question-points {
            color: #94A3B8;
        }

        .option-card {
            padding: 14px 18px;
            border-radius: 10px;
            border: 2px solid #E5E7EB;
            cursor: pointer;
            margin-bottom: 10px;
            transition: all .2s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        body.dark .option-card {
            border-color: #334155;
        }

        .option-card:hover {
            border-color: #4F46E5;
            background: #EEF2FF;
        }

        body.dark .option-card:hover {
            background: #312E81;
        }

        .option-card.selected {
            border-color: #4F46E5;
            background: #EEF2FF;
        }

        body.dark .option-card.selected {
            border-color: #6366F1;
            background: #312E81;
        }

        .option-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid #D1D5DB;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all .2s;
        }

        .option-card.selected .option-indicator {
            border-color: #4F46E5;
            background: #4F46E5;
        }

        .option-card.selected .option-indicator::after {
            content: '';
            width: 8px;
            height: 8px;
            background: #fff;
            border-radius: 50%;
        }

        .option-card.multi .option-indicator {
            border-radius: 4px;
        }

        .option-card.multi.selected .option-indicator::after {
            content: '✓';
            font-size: 11px;
            color: #fff;
            width: auto;
            height: auto;
            background: none;
            border-radius: 0;
        }

        .open-textarea {
            width: 100%;
            min-height: 120px;
            border-radius: 10px;
            border: 2px solid #E5E7EB;
            padding: 14px;
            font-family: inherit;
            font-size: 0.95rem;
            resize: vertical;
            outline: none;
            transition: border-color .2s;
        }

        .open-textarea:focus {
            border-color: #4F46E5;
        }

        body.dark .open-textarea {
            background: #0F172A;
            border-color: #334155;
            color: #F1F5F9;
        }

        .btn-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all .2s;
        }

        .btn-secondary {
            background: #E5E7EB;
            color: #374151;
        }

        body.dark .btn-secondary {
            background: #334155;
            color: #F1F5F9;
        }

        .btn-primary {
            background: #4F46E5;
            color: #fff;
        }

        .btn-primary:hover {
            background: #4338CA;
        }

        .btn-danger {
            background: #EF4444;
            color: #fff;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: #10B981;
            color: #fff;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            opacity: 0;
            transform: translateY(10px);
            transition: all .3s;
            z-index: 9999;
            pointer-events: none;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        @media (max-width: 640px) {
            .container {
                padding: 16px 12px;
            }

            .card {
                padding: 20px 16px;
            }

            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>{{ $exam->title }}</h1>
                <div class="header-meta">{{ $exam->subjectAssignment->subject->name ?? '' }}</div>
            </div>
            <div class="controls">
                <div class="timer" id="timer">00:00</div>
                <div class="dark-toggle" id="darkToggle" title="Тема">🌙</div>
            </div>
        </div>

        <div class="progress-wrap">
            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
        </div>

        <div class="nav-grid" id="navGrid">
            @foreach($examQuestions as $idx => $eq)
            <button class="nav-btn" data-index="{{ $idx }}" onclick="goToQuestion({{ $idx }})">{{ $idx + 1 }}</button>
            @endforeach
        </div>

        <form id="examForm" method="POST" action="{{ route('student.exams.submit', [$exam, $attempt]) }}">
            @csrf
            @foreach($examQuestions as $idx => $eq)
            <div class="card question-card" id="question-{{ $idx }}" style="display: {{ $idx === 0 ? 'block' : 'none' }}">
                <div class="question-label">Савол {{ $idx + 1 }} аз {{ $examQuestions->count() }}</div>
                <div class="question-points">{{ $eq->points }} балл</div>
                <div class="question-text">{!! nl2br(e($eq->question->question_text)) !!}</div>

                @php
                $type = $eq->question->type;
                $options = $eq->question->answerOptions;
                $existing = $existingAnswers[$eq->id] ?? [];
                if($exam->shuffle_answers && in_array($type, ['single_choice','multiple_choice'])) {
                $options = $options->shuffle();
                }
                @endphp

                @if($type === 'open_text')
                <textarea class="open-textarea" name="answers[{{ $eq->id }}][text]" data-eq="{{ $eq->id }}" data-type="open_text" placeholder="Ҷавоби худро нависед...">{{ is_array($existing) ? ($existing['text'] ?? '') : '' }}</textarea>
                @elseif($type === 'matching')
                {{-- МУВОФИҚОВАРӢ: 4 зерсавол + dropdown бо 5 ҷавоб --}}
                @php
                $subQuestions = $options->filter(fn($o) => $o->is_correct && str_contains($o->option_text, '|||'));
                $allAnswers = [];
                foreach($subQuestions as $sq) {
                $parts = explode('|||', $sq->option_text);
                $allAnswers[] = trim($parts[1]);
                }
                // Ҷавобҳои нодуруст (лағжанда)
                $decoys = $options->filter(fn($o) => !$o->is_correct);
                foreach($decoys as $d) { $allAnswers[] = trim($d->option_text); }
                shuffle($allAnswers);
                @endphp
                <div class="matching-container" data-eq="{{ $eq->id }}">
                    @foreach($subQuestions as $sqIdx => $sq)
                    @php $parts = explode('|||', $sq->option_text); $subText = trim($parts[0]); @endphp
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;padding:12px 16px;border:2px solid #E5E7EB;border-radius:10px;background:var(--card-bg,#fff);">
                        <span style="font-weight:600;min-width:30px;color:#4F46E5">{{ $sqIdx + 1 }}.</span>
                        <span style="flex:1;font-size:0.95rem">{{ $subText }}</span>
                        <span style="margin:0 8px;color:#9CA3AF">→</span>
                        <select class="matching-select" data-eq="{{ $eq->id }}" data-sub="{{ $sq->id }}"
                            style="padding:8px 12px;border:2px solid #E5E7EB;border-radius:8px;font-size:0.85rem;min-width:180px;background:#F9FAFB;cursor:pointer">
                            <option value="">— Интихоб —</option>
                            @foreach($allAnswers as $ans)
                            <option value="{{ $ans }}">{{ $ans }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endforeach
                    <p style="font-size:0.75rem;color:#6B7280;margin-top:8px">
                        ⚠️ Як ҷавоб иловагӣ (нодуруст) мавҷуд аст
                    </p>
                </div>
                @else
                @foreach($options as $option)
                @php
                $isMulti = in_array($type, ['multiple_choice']);
                $isSelected = in_array($option->id, (array)$existing);
                $optionText = $option->option_text;
                @endphp
                <div class="option-card {{ $isMulti ? 'multi' : '' }} {{ $isSelected ? 'selected' : '' }}"
                    data-eq="{{ $eq->id }}"
                    data-option="{{ $option->id }}"
                    data-type="{{ $type }}"
                    onclick="selectOption(this)">
                    <div class="option-indicator"></div>
                    <span>{{ $optionText }}</span>
                </div>
                @endforeach
                @endif
            </div>
            @endforeach

            <div class="btn-row">
                <button type="button" class="btn btn-secondary" id="prevBtn" onclick="prevQuestion()" disabled>← Қаблӣ</button>
                <button type="button" class="btn btn-primary" id="nextBtn" onclick="nextQuestion()">Навбатӣ →</button>
                <button type="submit" class="btn btn-danger" id="submitBtn" onclick="return confirm('Тестро супоред? Бозгашт нест.')">✓ Анҷом</button>
            </div>
        </form>
    </div>

    <div class="toast" id="toast">✓ Ҷавоб сабт шуд</div>

    <script>
        const totalQuestions = {{ $examQuestions->count() }};
        const allowBack = @json($exam->allow_back_navigation ?? true);
        const saveUrl = "{{ route('student.exams.save-answer', [$exam, $attempt]) }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        let currentIndex = 0;
        let answeredSet = new Set();

        // Initialize answered set from existing answers
        const existingAnswersJson = @json($existingAnswers->filter(fn($v) => !empty($v))->keys()->values());
        const eqIdsOrder = @json($examQuestions->pluck('id')->values());
        existingAnswersJson.forEach(function(eqId) {
            const idx = eqIdsOrder.indexOf(eqId);
            if (idx >= 0) answeredSet.add(idx);
        });

        // Dark mode
        const body = document.body;
        const darkToggle = document.getElementById('darkToggle');
        if (localStorage.getItem('dark_mode') === 'true') {
            body.classList.add('dark');
            darkToggle.textContent = '☀️';
        }
        darkToggle.addEventListener('click', function() {
            body.classList.toggle('dark');
            const isDark = body.classList.contains('dark');
            localStorage.setItem('dark_mode', isDark);
            darkToggle.textContent = isDark ? '☀️' : '🌙';
        });

        // Timer
        let remainingSeconds = {{ $remainingSeconds > 0 ? $remainingSeconds : ($exam->duration_minutes * 60) }};
        const timerEl = document.getElementById('timer');

        function updateTimer() {
            if (remainingSeconds <= 0) {
                timerEl.textContent = '00:00';
                document.getElementById('examForm').submit();
                return;
            }
            remainingSeconds--;
            const m = Math.floor(remainingSeconds / 60);
            const s = remainingSeconds % 60;
            timerEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            if (remainingSeconds < 60) {
                timerEl.classList.add('danger');
            }
        }
        updateTimer();
        setInterval(updateTimer, 1000);

        // Navigation
        function goToQuestion(idx) {
            if (!allowBack && idx < currentIndex) return;
            document.getElementById('question-' + currentIndex).style.display = 'none';
            currentIndex = idx;
            document.getElementById('question-' + currentIndex).style.display = 'block';
            updateNavState();
        }

        function nextQuestion() {
            if (currentIndex < totalQuestions - 1) goToQuestion(currentIndex + 1);
        }

        function prevQuestion() {
            if (allowBack && currentIndex > 0) goToQuestion(currentIndex - 1);
        }

        function updateNavState() {
            const navBtns = document.querySelectorAll('.nav-btn');
            navBtns.forEach(function(btn, i) {
                btn.classList.remove('active');
                if (i === currentIndex) btn.classList.add('active');
                if (answeredSet.has(i)) btn.classList.add('answered');
            });
            document.getElementById('prevBtn').disabled = !allowBack || currentIndex === 0;
            document.getElementById('nextBtn').disabled = currentIndex === totalQuestions - 1;
            updateProgress();
        }

        function updateProgress() {
            const pct = Math.round((answeredSet.size / totalQuestions) * 100);
            document.getElementById('progressBar').style.width = pct + '%';
        }

        // Select option
        function selectOption(card) {
            const eqId = card.dataset.eq;
            const optionId = card.dataset.option;
            const type = card.dataset.type;
            const parent = card.parentElement;

            if (type === 'single_choice' || type === 'true_false' || type === 'matching') {
                parent.querySelectorAll('.option-card[data-eq="' + eqId + '"]').forEach(function(c) {
                    c.classList.remove('selected');
                });
                card.classList.add('selected');
            } else {
                card.classList.toggle('selected');
            }

            answeredSet.add(currentIndex);
            updateNavState();
            saveAnswer(eqId);
        }

        // Save answer via AJAX
        function saveAnswer(eqId) {
            const questionCard = document.querySelector('.question-card[style*="block"]');
            let data = {
                exam_question_id: parseInt(eqId)
            };

            const textarea = questionCard.querySelector('textarea[data-eq="' + eqId + '"]');
            if (textarea) {
                data.text_answer = textarea.value;
                data.selected_options = null;
            } else {
                const selected = questionCard.querySelectorAll('.option-card.selected[data-eq="' + eqId + '"]');
                data.selected_options = [];
                selected.forEach(function(c) {
                    data.selected_options.push(parseInt(c.dataset.option));
                });
                data.text_answer = null;
            }

            fetch(saveUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            }).then(function(res) {
                if (res.ok) showToast('✓ Ҷавоб сабт шуд');
            }).catch(function() {
                showToast('Хатогӣ!');
            });
        }

        // Open text auto-save with debounce
        let saveTimeout = null;
        document.querySelectorAll('.open-textarea').forEach(function(ta) {
            ta.addEventListener('input', function() {
                const eqId = ta.dataset.eq;
                answeredSet.add(currentIndex);
                updateNavState();
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    saveAnswer(eqId);
                }, 1000);
            });
        });

        // Matching dropdown save
        document.querySelectorAll('.matching-select').forEach(function(sel) {
            sel.addEventListener('change', function() {
                const eqId = this.dataset.eq;
                // Ҳамаи dropdown-ҳои ин савол
                const container = this.closest('.matching-container');
                const allSelects = container.querySelectorAll('.matching-select');
                let selectedOptions = [];
                allSelects.forEach(function(s) {
                    if (s.value) selectedOptions.push(s.dataset.sub + ':' + s.value);
                });

                answeredSet.add(currentIndex);
                updateNavState();

                // Save as text (matching answers)
                fetch(saveUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            exam_question_id: parseInt(eqId),
                            selected_options: null,
                            text_answer: selectedOptions.join('||')
                        })
                    }).then(function(res) {
                        if (res.ok) showToast('✓ Мувофиқат сабт шуд');
                    })
                    .catch(function() {
                        showToast('Хатогӣ!');
                    });
            });
        });

        // Toast
        function showToast(msg) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(function() {
                toast.classList.remove('show');
            }, 2000);
        }

        // Init
        updateNavState();
    </script>
</body>

</html>