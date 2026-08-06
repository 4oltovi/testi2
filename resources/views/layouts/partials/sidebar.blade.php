{{-- Менюи паҳлӯӣ (Sidebar) — мутобиқи нақши корбар --}}
<div class="bg-dark text-white sidebar" id="sidebar" style="min-width: 260px; max-width: 260px; min-height: 100vh;">
    <!-- Logo -->
    <div class="sidebar-header p-3 border-bottom border-secondary">
        <a href="{{ auth()->user()?->hasRole('admin') ? '/admin/dashboard' : (auth()->user()?->hasRole('teacher') ? '/teacher/dashboard' : '/student/dashboard') }}" class="text-decoration-none text-white d-flex align-items-center">
            <i class="bi bi-mortarboard-fill fs-4 me-2 text-primary"></i>
            <span class="fs-5 fw-bold">ДОНИШЁР</span>
        </a>
        <small class="text-muted d-block mt-1">Системаи идоракунии таълим</small>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav p-2" style="overflow-y: auto; max-height: calc(100vh - 80px);">
        <ul class="nav flex-column">

            @if(auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('super_admin') || request()->is('admin/*'))
            {{-- ===== SIDEBAR АДМИН ===== --}}
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/dashboard') ? 'active bg-primary rounded' : '' }}" href="/admin/dashboard">
                    <i class="bi bi-speedometer2 me-2"></i> Панели асосӣ
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Корбарон</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/users*') ? 'active bg-primary rounded' : '' }}" href="/admin/users">
                    <i class="bi bi-people me-2"></i> Корбарон
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Сохтор</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/faculties*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/faculties">
                    <i class="bi bi-building me-2"></i> Факултетҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/departments*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/departments">
                    <i class="bi bi-diagram-3 me-2"></i> Кафедраҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/groups*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/groups">
                    <i class="bi bi-people-fill me-2"></i> Гурӯҳҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/specialties*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/specialties">
                    <i class="bi bi-bookmark-star me-2"></i> Ихтисосҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/subjects*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/subjects">
                    <i class="bi bi-book me-2"></i> Фанҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/classrooms*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/classrooms">
                    <i class="bi bi-door-open me-2"></i> Аудиторияҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/academic-years*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/academic-years">
                    <i class="bi bi-calendar3 me-2"></i> Солҳои таҳсилӣ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/structure/curriculum*') ? 'active bg-primary rounded' : '' }}" href="/admin/structure/curriculum">
                    <i class="bi bi-list-check me-2"></i> Нақшаи таълимӣ
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Таълим</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/students*') ? 'active bg-primary rounded' : '' }}" href="/admin/students">
                    <i class="bi bi-person-badge me-2"></i> Донишҷӯён
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/teachers*') ? 'active bg-primary rounded' : '' }}" href="/admin/teachers">
                    <i class="bi bi-person-workspace me-2"></i> Омӯзгорон
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/journal*') ? 'active bg-primary rounded' : '' }}" href="/admin/journal">
                    <i class="bi bi-journal-text me-2"></i> Журнали электронӣ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('operator/attendance*') ? 'active bg-primary rounded' : '' }}" href="/operator/attendance">
                    <i class="bi bi-check2-square me-2"></i> Давомот
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Аналитика</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/ratings*') ? 'active bg-primary rounded' : '' }}" href="/admin/ratings">
                    <i class="bi bi-bar-chart-line me-2"></i> Рейтингҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/exams*') ? 'active bg-primary rounded' : '' }}" href="/admin/exams">
                    <i class="bi bi-pencil-square me-2"></i> Имтиҳонҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/exams/questions*') ? 'active bg-primary rounded' : '' }}" href="/admin/exams/questions">
                    <i class="bi bi-card-list me-2"></i> Саволномаҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/debts*') ? 'active bg-primary rounded' : '' }}" href="/admin/debts">
                    <i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/transcript*') ? 'active bg-primary rounded' : '' }}" href="/admin/transcript">
                    <i class="bi bi-file-earmark-text me-2"></i> Transcript / GPA
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Система</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/reports*') ? 'active bg-primary rounded' : '' }}" href="/admin/reports">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i> Ҳисоботҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/import*') ? 'active bg-primary rounded' : '' }}" href="/admin/import">
                    <i class="bi bi-upload me-2"></i> Импорт
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/settings*') ? 'active bg-primary rounded' : '' }}" href="/admin/settings">
                    <i class="bi bi-gear me-2"></i> Танзимот
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('admin/audit*') ? 'active bg-primary rounded' : '' }}" href="/admin/audit">
                    <i class="bi bi-shield-check me-2"></i> Аудит
                </a>
            </li>

            @elseif(auth()->user()?->hasRole('teacher'))
            {{-- ===== SIDEBAR ОМӮЗГОР (МАҲДУД — танҳо журнал) ===== --}}
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('teacher/dashboard') ? 'active bg-primary rounded' : '' }}" href="/teacher/dashboard">
                    <i class="bi bi-speedometer2 me-2"></i> Панели асосӣ
                </a>
            </li>

            <li class="nav-item mt-2">
                <small class="nav-link text-muted text-uppercase fw-bold px-3">Журнал</small>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('teacher/journal*') ? 'active bg-primary rounded' : '' }}" href="/teacher/journal">
                    <i class="bi bi-journal-text me-2"></i> Журнали электронӣ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('teacher/schedule*') ? 'active bg-primary rounded' : '' }}" href="/teacher/schedule">
                    <i class="bi bi-calendar-week me-2"></i> Ҷадвали дарс
                </a>
            </li>

            @elseif(auth()->user()?->hasRole('operator'))
            {{-- ===== SIDEBAR ОПЕРАТОР ===== --}}
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('operator/attendance*') ? 'active bg-primary rounded' : '' }}" href="/operator/attendance">
                    <i class="bi bi-check2-square me-2"></i> Давомот
                </a>
            </li>

            @elseif(auth()->user()?->hasRole('student'))
            {{-- ===== SIDEBAR ДОНИШҶӮ ===== --}}
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/dashboard') ? 'active bg-primary rounded' : '' }}" href="/student/dashboard">
                    <i class="bi bi-speedometer2 me-2"></i> Панели асосӣ
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/grades*') ? 'active bg-primary rounded' : '' }}" href="/student/grades">
                    <i class="bi bi-journal-text me-2"></i> Баҳоҳои ман
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/exams*') ? 'active bg-primary rounded' : '' }}" href="/student/exams">
                    <i class="bi bi-pencil-square me-2"></i> Тестҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/schedule*') ? 'active bg-primary rounded' : '' }}" href="/student/schedule">
                    <i class="bi bi-calendar-week me-2"></i> Ҷадвал
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/debts*') ? 'active bg-primary rounded' : '' }}" href="/student/debts">
                    <i class="bi bi-exclamation-triangle me-2"></i> Қарздориҳо
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->is('student/transcript*') ? 'active bg-primary rounded' : '' }}" href="/student/transcript">
                    <i class="bi bi-file-earmark-text me-2"></i> Transcript
                </a>
            </li>
            @endif

        </ul>
    </nav>
</div>