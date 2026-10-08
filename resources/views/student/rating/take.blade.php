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
        const end = {{ $endsAt }};
        const timer = document.getElementById('timer');
        const form = document.getElementById('ratingForm');
        let csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        const iv = setInterval(() => {
            let s = end - Math.floor(Date.now() / 1000);

            if (s <= 0) {
                clearInterval(iv);
                timer.textContent = '0:00';
                submitRatingForm();
                return;
            }

            const m = Math.floor(s / 60);
            const ss = s % 60;
            timer.textContent = m + ':' + (ss < 10 ? '0' : '') + ss;
        }, 1000);

        function refreshCsrfToken() {
            fetch("{{ route('student.rating.keep-alive', $attempt) }}", {
                headers: { 'Accept': 'application/json' }
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.token) {
                    csrfToken = data.token;
                    document.querySelector('meta[name="csrf-token"]').setAttribute('content', data.token);
                    document.querySelectorAll('input[name="_token"]').forEach(function(el) {
                        el.value = data.token;
                    });
                }
            })
            .catch(function() {});
        }

        setInterval(refreshCsrfToken, 240000);

        function saveToLocalStorage() {
            try {
                const answers = {};
                @foreach($questions as $q)
                const val = document.querySelector('input[name="answers[{{ $q->id }}]"]:checked');
                if (val) answers['{{ $q->id }}'] = val.value;
                @endforeach
                localStorage.setItem('rating_backup_{{ $attempt->id }}', JSON.stringify(answers));
            } catch (e) {}
        }

        function showSessionExpiredModal() {
            const existing = document.getElementById('sessionExpiredModal');
            if (existing) existing.remove();

            const modalHtml = `
                <div class="modal fade" id="sessionExpiredModal" tabindex="-1" data-bs-backdrop="static">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header border-0 pb-0">
                                <h5 class="modal-title text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Сессия тамом шуд</h5>
                            </div>
                            <div class="modal-body">
                                <p>Сессияи шумо ба анҷом расид. Ҷавобҳо дар хотираи локалӣ нигоҳ дошта шуданд.</p>
                                <p class="mb-0">Барои идомаи кор, лутфан азнав ворид шавед.</p>
                            </div>
                            <div class="modal-footer border-0 pt-0">
                                <button type="button" class="btn btn-primary" onclick="reLogin()">
                                    <i class="bi bi-box-arrow-in-right me-1"></i> Ворид шудан
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('sessionExpiredModal'));
            modal.show();
        }

        function reLogin() {
            const loginUrl = "{{ route('login') }}";
            const popup = window.open(loginUrl, '_blank', 'width=500,height=600');
            if (popup) {
                const checkClosed = setInterval(function() {
                    if (popup.closed) {
                        clearInterval(checkClosed);
                        refreshCsrfToken();
                        submitRatingForm();
                    }
                }, 500);
            } else {
                window.location.href = loginUrl;
            }
        }

        function submitRatingForm() {
            const formData = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(function(res) {
                if (res.status === 419 || res.status === 401) {
                    saveToLocalStorage();
                    showSessionExpiredModal();
                    return;
                }
                if (res.ok) {
                    return res.text().then(function() {
                        window.location.href = "{{ route('student.rating.result', $attempt) }}";
                    });
                }
                res.text().then(function(txt) {
                    alert('Хатогӣ! ' + res.status + '\n' + txt);
                });
            })
            .catch(function(err) {
                alert('Хатогӣ дар фиристодан!');
            });
        }

        // Intercept form submit
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            saveToLocalStorage();
            submitRatingForm();
        });

        // Periodic backup
        setInterval(saveToLocalStorage, 30000);

        // Refresh token on pageshow (bfcache)
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                refreshCsrfToken();
                const raw = localStorage.getItem('rating_backup_{{ $attempt->id }}');
                if (raw) {
                    try {
                        const answers = JSON.parse(raw);
                        Object.entries(answers).forEach(function([qid, val]) {
                            const input = document.querySelector('input[name="answers[' + qid + ']"][value="' + val + '"]');
                            if (input) input.checked = true;
                        });
                    } catch (e) {}
                }
            }
        });
    </script>
@endsection