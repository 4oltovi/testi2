{{-- Навори боло (Top Navbar) --}}
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm px-4 py-2">
    <div class="container-fluid">
        <!-- Семестри ҷорӣ -->
        @php $currentSemester = \App\Models\Semester::where('is_current', true)->first(); @endphp
        @if($currentSemester)
        <span class="badge bg-info me-3">
            <i class="bi bi-calendar3 me-1"></i>
            {{ $currentSemester->name }} | {{ $currentSemester->academicYear?->name }}
        </span>
        @endif

        <!-- Right Side -->
        <div class="ms-auto d-flex align-items-center">
            <!-- Профил -->
            <span class="me-3">
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
