<!DOCTYPE html>
<html lang="tg" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Донишёр') — Системаи идоракунии таълим</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">
    @auth
        <div class="d-flex" id="wrapper">
            <!-- Sidebar -->
            @include('layouts.partials.sidebar')

            <!-- Page Content -->
            <div class="flex-grow-1" id="page-content-wrapper">
                <!-- Top Navbar -->
                @include('layouts.partials.navbar')

                <!-- Main Content -->
                <main class="container-fluid py-4 px-4">
                    <!-- Паёмҳо -->
                    @include('layouts.partials.alerts')

                    <!-- Сарлавҳаи саҳифа -->
                    @hasSection('page-header')
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h4 class="mb-1">@yield('page-header')</h4>
                                @hasSection('page-description')
                                    <p class="text-muted mb-0">@yield('page-description')</p>
                                @endif
                            </div>
                            <div>
                                @yield('page-actions')
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>
    @else
        @yield('content')
    @endauth

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- MathJax барои формулаҳо (алгебра, химия, физика) -->
    <script>
        window.MathJax = { tex: { inlineMath: [['$', '$'], ['\\(', '\\)']] } };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
    <!-- Custom JS -->
    <script src="{{ asset('js/app.js') }}"></script>

    @stack('scripts')
</body>
</html>
