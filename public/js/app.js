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
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                if (!sidebar.contains(e.target) && e.target !== toggleBtn) {
                    sidebar.classList.remove('show');
                }
            }
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
