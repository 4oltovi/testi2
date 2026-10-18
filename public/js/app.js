/**
 * ДОНИШЁР — JavaScript
 * Alpine.js + Bootstrap 5
 */

// ====== CSRF Token Setup ======
document.addEventListener('DOMContentLoaded', function () {
    // Sidebar Toggle (Mobile)
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });

        // Бастан бо клик берун аз sidebar
        const overlay = document.getElementById('sidebarOverlay');
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn && e.target !== overlay) {
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                }
            }
        });

        // Бастан бо клик ба overlay
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }

        // Автоматики бастан пас аз клик ба сӯи роҳи sidebar дар мобил
        const sidebarLinks = sidebar.querySelectorAll('.nav-link');
        sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('show');
                    if (overlay) overlay.classList.remove('show');
                }
            });
        });
    }

    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 5000);
    });

    // Confirm delete actions
    document.querySelectorAll('[data-confirm]').forEach(function (element) {
        element.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Шумо мутмаин ҳастед?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) {
        return new bootstrap.Tooltip(el);
    });
});

// ====== Alpine.js Global Components ======
document.addEventListener('alpine:init', () => {
    // Exam Timer Component
    Alpine.data('examTimer', (seconds) => ({
        remaining: seconds,
        interval: null,
        get formatted() {
            const h = Math.floor(this.remaining / 3600);
            const m = Math.floor((this.remaining % 3600) / 60);
            const s = this.remaining % 60;
            if (h > 0) {
                return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            }
            return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        },
        get isWarning() {
            return this.remaining < 300; // < 5 дақиқа
        },
        get isDanger() {
            return this.remaining < 60; // < 1 дақиқа
        },
        init() {
            this.interval = setInterval(() => {
                if (this.remaining > 0) {
                    this.remaining--;
                } else {
                    clearInterval(this.interval);
                    // Auto submit
                    document.getElementById('exam-form')?.submit();
                }
            }, 1000);
        },
        destroy() {
            clearInterval(this.interval);
        }
    }));

    // Auto-save Component
    Alpine.data('autoSave', (url, interval = 30000) => ({
        timer: null,
        saving: false,
        lastSaved: null,
        init() {
            this.timer = setInterval(() => this.save(), interval);
        },
        async save() {
            if (this.saving) return;
            this.saving = true;
            try {
                const form = this.$el.closest('form');
                const formData = new FormData(form);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                });
                if (response.ok) {
                    this.lastSaved = new Date().toLocaleTimeString('tg-TJ');
                }
            } catch (e) {
                console.error('Auto-save failed:', e);
            }
            this.saving = false;
        },
        destroy() {
            clearInterval(this.timer);
        }
    }));
});

// ====== Utility Functions ======

/**
 * Format number with separators
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
}

/**
 * Grade to CSS class
 */
function gradeColorClass(letter) {
    if (!letter) return '';
    if (letter.startsWith('A')) return 'grade-a';
    if (letter.startsWith('B')) return 'grade-b';
    if (letter.startsWith('C')) return 'grade-c';
    if (letter.startsWith('D')) return 'grade-d';
    return 'grade-f';
}

// ====== Session Expiry Manager ======

(function() {
    let sessionManager = null;
    let redirectInProgress = false;
    const CHECK_INTERVAL = 30000; // 30 seconds

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function getLoginUrl() {
        return '/login';
    }

    async function checkSession() {
        try {
            const resp = await fetch('/session-check', {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin'
            });

            if (resp.status === 401) {
                handleSessionExpired();
                return;
            }

            if (!resp.ok) {
                return;
            }

            const data = await resp.json();

            if (data.valid === false || data.remaining <= 0) {
                handleSessionExpired();
                return;
            }

            if (sessionManager) {
                sessionManager.updateRemaining(data.remaining);
            }
        } catch(e) {
            // Network error or unexpected response - don't redirect
        }
    }

    function handleSessionExpired() {
        if (redirectInProgress) return;
        redirectInProgress = true;

        // Show Tajik message if possible
        try {
            if (typeof bootstrap !== 'undefined') {
                const toastEl = document.createElement('div');
                toastEl.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastEl.innerHTML =
                    '<div class="toast show" role="alert">' +
                    '<div class="toast-header bg-warning text-white">' +
                    '<strong class="me-auto">Р”РѕРЅРёС€С‘СЂ</strong>' +
                    '</div>' +
                    '<div class="toast-body">' +
                    'РњСѓТіР»Р°С‚Рё СЃРµСЃСЃРёСЏРё С€СѓРјРѕ Р±Р° РѕС…РёСЂ СЂР°СЃРёРґ. Р›СѓС‚С„Р°РЅ, Р°Р· РЅР°РІ РІРѕСЂРёРґ С€Р°РІРµРґ.' +
                    '</div>' +
                    '</div>';
                document.body.appendChild(toastEl);
            }
        } catch(e) {}

        // Redirect after a brief delay
        setTimeout(() => {
            window.location.replace(getLoginUrl());
        }, 1500);
    }

    function initSessionManager() {
        if (sessionManager) return;
        sessionManager = {
            remaining: 0,
            timer: null,
            updateRemaining(sec) {
                if (this.timer) clearInterval(this.timer);
                this.remaining = sec;
                if (sec <= 0) {
                    handleSessionExpired();
                    return;
                }
                this.timer = setInterval(() => {
                    this.remaining--;
                    if (this.remaining <= 0) {
                        clearInterval(this.timer);
                        handleSessionExpired();
                    }
                }, 1000);
            }
        };

        // Initial check
        checkSession();

        // Periodic server check
        setInterval(() => {
            checkSession();
        }, CHECK_INTERVAL);
    }

    // Fetch interceptor
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            // Check for auth-related status codes
            if (response.status === 401 || response.status === 419) {
                // Only redirect if this is a same-origin request to our app
                const url = typeof args[0] === 'string' ? args[0] : (args[0] && args[0].url);
                if (url && typeof url === 'string' && url.startsWith('/')) {
                    handleSessionExpired();
                }
            }
            return response;
        });
    };

    // Initialize on DOMContentLoaded
    document.addEventListener('DOMContentLoaded', function() {
        // Only start session manager if user is authenticated (check for sidebar or navbar)
        const hasAuthElements = document.getElementById('sidebar') ||
            document.querySelector('.navbar') ||
            document.querySelector('[class*="dashboard"]');
        if (hasAuthElements) {
            initSessionManager();
        }
    });

    // Expose for testing
    window.DonishorSession = {
        checkSession: checkSession,
        init: initSessionManager
    };
})();
