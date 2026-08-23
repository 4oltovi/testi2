{{-- Навори боло (Top Navbar) --}}
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-2">
    <div class="container-fluid">
        <!-- Left Side: Toggle + Brand -->
        <div class="d-flex align-items-center">
            <button class="btn btn-outline-secondary btn-sm me-2 d-lg-none" id="toggleSidebar" type="button">
                <i class="bi bi-list"></i>
            </button>
            @php
                $user = auth()->user();
                $dashboardUrl = match(true) {
                    $user?->hasRole('admin') || $user?->hasRole('super_admin') => '/admin/dashboard',
                    $user?->hasRole('teacher') => '/teacher/dashboard',
                    default => '/student/dashboard',
                };
            @endphp
            <a href="{{ $dashboardUrl }}" class="text-decoration-none text-dark d-flex align-items-center">
                @php $logoPath = \App\Models\Setting::get('institution_logo'); @endphp
                @if($logoPath && file_exists(public_path($logoPath)))
                <img src="{{ asset($logoPath) }}" alt="Логотип" style="height:32px; width:32px; object-fit:contain;" class="me-2">
                @else
                <i class="bi bi-mortarboard-fill fs-4 me-2 text-primary"></i>
                @endif
                <span class="fs-5 fw-bold">ДОНИШЁР</span>
            </a>
        </div>

        <!-- Семестри ҷорӣ -->
        @php $currentSemester = \App\Models\Semester::where('is_current', true)->first(); @endphp
        @if($currentSemester)
        <span class="badge bg-info ms-3 d-none d-sm-inline">
            <i class="bi bi-calendar3 me-1"></i>
            {{ $currentSemester->name }} | {{ $currentSemester->academicYear?->name }}
        </span>
        @endif

        <!-- Right Side -->
        <div class="ms-auto d-flex align-items-center">
            <!-- Профил -->
            <span class="me-3 d-none d-sm-inline">
                <i class="bi bi-person-circle me-1"></i>
                <strong>{{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? '' }}</strong>
                <small class="text-muted">({{ auth()->user()->login ?? '' }})</small>
            </span>
            <form action="/logout" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Баромад
                </button>
            </form>
        </div>
    </div>
</nav>
