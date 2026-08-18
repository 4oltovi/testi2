@extends('layouts.app')

@section('title', 'Рейтинг: ' . $attempt->subject?->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0">{{ $attempt->subject?->name }}</h5>
        <small class="text-muted">Кӯшиши {{ $attempt->attempt_number }} аз {{ $session->max_attempts }}</small>
    </div>
    <span id="timer" class="badge bg-danger fs-5 px-3 py-2">--:--</span>
</div>

<form id="ratingForm" method="POST" action="{{ route('student.rating.submit', $attempt) }}">
    @csrf

    @foreach($questions as $i => $q)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <p class="fw-semibold mb-3">{{ $i + 1 }}. {{ $q->question_text }}</p>

            @foreach($q->answerOptions as $opt)
            <div class="form-check mb-2">
                <input class="form-check-input" type="radio"
                    name="answers[{{ $q->id }}]" id="opt_{{ $opt->id }}"
                    value="{{ $opt->id }}" required>
                <label class="form-check-label" for="opt_{{ $opt->id }}">{{ $opt->option_text }}</label>
            </div>
            @endforeach
        </div>
    </div>
    @endforeach

    <button type="submit" class="btn btn-success btn-lg w-100">
        <i class="bi bi-check-lg me-1"></i> Супоридан
    </button>
</form>

<script>
    const end = {
        {
            $endsAt
        }
    };
    const timer = document.getElementById('timer');
    const form = document.getElementById('ratingForm');

    const iv = setInterval(() => {
        let s = end - Math.floor(Date.now() / 1000);

        if (s <= 0) {
            clearInterval(iv);
            timer.textContent = '0:00';
            form.submit(); // автоматӣ супоридан
            return;
        }

        const m = Math.floor(s / 60);
        const ss = s % 60;
        timer.textContent = m + ':' + (ss < 10 ? '0' : '') + ss;
    }, 1000);
</script>
@endsection