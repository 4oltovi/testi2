<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $retakeExam->title }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #F8FAFC;
            color: #1F2937;
            min-height: 100vh;
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
                opacity: 0.6;
            }
        }

        .question-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .question-text {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 16px;
        }

        .option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .option:hover {
            background: #F3F4F6;
        }

        .option input {
            width: 18px;
            height: 18px;
            accent-color: #2563EB;
        }

        .submit-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #E5E7EB;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-primary {
            background: #2563EB;
            color: white;
        }

        .btn-success {
            background: #059669;
            color: white;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>{{ $retakeExam->title }}</h1>
                <div class="header-meta">
                    Имтиҳони такрорӣ |
                    {{ $retakeExam->subject->name ?? 'Фан' }} |
                    Кӯшиш: {{ $attempt->attempt_number }}
                </div>
            </div>
            <div class="timer" id="timer">{{ gmdate('H:i:s', $remainingSeconds) }}</div>
        </div>

        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('student.retake-exams.submit', [$retakeExam, $attempt]) }}" id="examForm">
            @csrf
            <div id="questions">
                @foreach($examQuestions as $eq)
                @php
                $question = $eq->question;
                $answer = $existingAnswers[$eq->id] ?? null;
                @endphp
                <div class="question-card">
                    <div class="question-text">
                        {{ $loop->iteration }}. {{ $question->question_text ?? 'Савол #' . $question->id }}
                    </div>
                    @if($question->type === 'single_choice' || $question->type === 'true_false')
                    @foreach($question->answerOptions as $option)
                    <label class="option">
                        <input type="radio" name="answers[{{ $eq->id }}]" value="{{ $option->id }}"
                        {{ $answer && json_decode($answer, true) == [$option->id] ? 'checked' : '' }}>
                        {{ $option->option_text }}
                    </label>
                    @endforeach
                    @elseif($question->type === 'multiple_choice')
                    @foreach($question->answerOptions as $option)
                    @php
                    $selected = $answer ? json_decode($answer, true) : [];
                    $selected = is_array($selected) ? $selected : [];
                    @endphp
                    <label class="option">
                        <input type="checkbox" name="answers[{{ $eq->id }}][]" value="{{ $option->id }}"
                        {{ in_array($option->id, $selected) ? 'checked' : '' }}>
                        {{ $option->option_text }}
                    </label>
                    @endforeach
                    @elseif($question->type === 'matching')
                    @foreach($question->answerOptions->where('is_correct', true) as $option)
                    @php
                    $selected = $answer ? json_decode($answer, true) : [];
                    $selected = is_array($selected) ? $selected : [];
                    @endphp
                    <div class="mb-2">
                        <label class="form-label">{{ $option->option_text }}</label>
                        <input type="text" name="answers[{{ $eq->id }}][]" class="form-control"
                        value="{{ $selected[$loop->parent->index] ?? '' }}"
                        placeholder="Ҷавобро ворид кунед">
                    </div>
                    @endforeach
                    @else
                    <textarea name="answers[{{ $eq->id }}]" class="form-control" rows="3"
                    placeholder="Ҷавоби худро нависед">{{ $answer ?? '' }}</textarea>
                    @endif
                </div>
                @endforeach
            </div>

            <div class="submit-area">
                <button type="button" class="btn btn-primary" onclick="autoSave()">
                    <i class="bi bi-save me-1"></i> Захира кардан
                </button>
                <button type="submit" class="btn btn-success"
                onclick="return confirm('Имтиҳонро супоридан мехоҳед?')">
                <i class="bi bi-check-lg me-1"></i> Супоридан
            </button>
        </div>
    </form>
</div>

<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    let timerInterval;
    let remainingSeconds = {{ $remainingSeconds }};

    function updateTimer() {
        const timerEl = document.getElementById('timer');
        if (remainingSeconds <= 0) {
            clearInterval(timerInterval);
            timerEl.textContent = '00:00:00';
            timerEl.classList.add('danger');
            document.getElementById('examForm').submit();
            return;
        }
        remainingSeconds--;
        timerEl.textContent = new Date(remainingSeconds * 1000).toISOString().substr(11, 8);
        if (remainingSeconds <= 60) {
            timerEl.classList.add('danger');
        }
    }

    timerInterval = setInterval(updateTimer, 1000);

    async function autoSave() {
        const formData = new FormData();
        formData.append('_token', csrfToken);

        document.querySelectorAll('.question-card').forEach((card, index) => {
            const questionId = card.querySelector('input, textarea')?.name?.match(/\d+/)?.[0];
            if (!questionId) return;

            if (card.querySelector('input[type="radio"]:checked')) {
                formData.append(`answers[${questionId}]`, card.querySelector('input[type="radio"]:checked').value);
            } else if (card.querySelector('input[type="checkbox"]:checked')) {
                const values = Array.from(card.querySelectorAll('input[type="checkbox"]:checked')).map(cb => cb
                    .value);
                formData.append(`answers[${questionId}]`, JSON.stringify(values));
            } else if (card.querySelector('textarea')) {
                formData.append(`answers[${questionId}]`, card.querySelector('textarea').value);
            }
        });

        try {
            const response = await fetch('{{ route('student.retake-exams.save-answer', [$retakeExam, $attempt]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });
            const result = await response.json();
            if (result.status === 'saved') {
                const btn = document.querySelector('.btn-primary');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check me-1"></i> Захира шуд';
                setTimeout(() => btn.innerHTML = originalText, 1500);
            }
        } catch (e) {
            console.error('Auto-save failed:', e);
        }
    }

    setInterval(autoSave, 30000);
</script>
</body>

</html>
