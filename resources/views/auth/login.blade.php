<!DOCTYPE html>
<html lang="tg" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Воридшавӣ — Донишёр</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #1a237e 0%, #283593 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .login-header h3 {
            margin: 0;
            font-weight: 700;
        }
        .login-header p {
            margin: 0.5rem 0 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            font-size: 1rem;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #5568d4 0%, #684199 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .input-group-text {
            background: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <!-- Header -->
        <div class="login-header">
            <i class="bi bi-mortarboard-fill fs-1 mb-2 d-block"></i>
            <h3>ДОНИШЁР</h3>
            <p>Системаи идоракунии таълим</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            {{-- Паёмҳо --}}
            @if(session('success'))
                <div class="alert alert-success alert-sm py-2 px-3 mb-3">
                    <small><i class="bi bi-check-circle me-1"></i> {{ session('success') }}</small>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-sm py-2 px-3 mb-3">
                    <small><i class="bi bi-exclamation-circle me-1"></i> {{ session('error') }}</small>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-sm py-2 px-3 mb-3">
                    @foreach($errors->all() as $error)
                        <small><i class="bi bi-exclamation-circle me-1"></i> {{ $error }}</small><br>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <div class="mb-3">
                    <label for="login" class="form-label fw-semibold">
                        <i class="bi bi-person me-1"></i> Логин ё Email
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text"
                               class="form-control @error('login') is-invalid @enderror"
                               id="login"
                               name="login"
                               value="{{ old('login') }}"
                               placeholder="Логини худро ворид кунед"
                               required
                               autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">
                        <i class="bi bi-lock me-1"></i> Парол
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               id="password"
                               name="password"
                               placeholder="Пароли худро ворид кунед"
                               required>
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Маро дар хотир нигоҳ дор</label>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-2"></i> Ворид шудан
                </button>
            </form>

            <div class="text-center mt-3">
                <small class="text-muted">
                    Паролро фаромӯш кардед? Бо администратор тамос гиред.
                </small>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            if (password.type === 'password') {
                password.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                password.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
    </script>
</body>
</html>
