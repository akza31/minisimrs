(function () {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const appShell = document.getElementById('appShell');
    const sidebarClose = document.getElementById('sidebarClose');
    const sidebarBackdrop = document.getElementById('sidebarBackdrop');
    const isMobileView = function () {
        return window.innerWidth <= 980;
    };

    if (appShell && localStorage.getItem('simrs-sidebar-collapsed') === '1' && !isMobileView()) {
        appShell.classList.add('sidebar-collapsed');
    }

    const hideMobileSidebar = function () {
        if (sidebar) {
            sidebar.classList.remove('open');
        }
    };

    const toggleSidebar = function () {
        if (!sidebar || !appShell) {
            return;
        }

        if (isMobileView()) {
            appShell.classList.remove('sidebar-collapsed');
            sidebar.classList.toggle('open');
            return;
        }

        appShell.classList.toggle('sidebar-collapsed');
        localStorage.setItem('simrs-sidebar-collapsed', appShell.classList.contains('sidebar-collapsed') ? '1' : '0');
    };

    if (toggle && sidebar) {
        toggle.addEventListener('click', toggleSidebar);

        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (isMobileView()) {
                    hideMobileSidebar();
                }
            });
        });
    }

    if (sidebarClose) {
        sidebarClose.addEventListener('click', function () {
            if (isMobileView()) {
                hideMobileSidebar();
                return;
            }

            if (appShell) {
                appShell.classList.add('sidebar-collapsed');
                localStorage.setItem('simrs-sidebar-collapsed', '1');
            }
        });
    }

    if (sidebarBackdrop) {
        sidebarBackdrop.addEventListener('click', hideMobileSidebar);
    }

    window.addEventListener('resize', function () {
        if (!appShell) {
            return;
        }

        if (isMobileView()) {
            appShell.classList.remove('sidebar-collapsed');
        } else if (localStorage.getItem('simrs-sidebar-collapsed') === '1') {
            appShell.classList.add('sidebar-collapsed');
            hideMobileSidebar();
        }
    });

    const navGroups = document.querySelectorAll('[data-nav-group]');
    const savedGroups = JSON.parse(localStorage.getItem('simrs-open-menu') || '[]');

    navGroups.forEach(function (group) {
        const key = group.getAttribute('data-nav-group');
        const button = group.querySelector('.nav-toggle');

        if (savedGroups.includes(key) || group.querySelector('a.active')) {
            group.classList.add('open');
            if (button) {
                button.setAttribute('aria-expanded', 'true');
            }
        }

        if (button) {
            button.addEventListener('click', function () {
                group.classList.toggle('open');
                button.setAttribute('aria-expanded', group.classList.contains('open') ? 'true' : 'false');

                const openKeys = Array.from(navGroups)
                    .filter(function (item) {
                        return item.classList.contains('open');
                    })
                    .map(function (item) {
                        return item.getAttribute('data-nav-group');
                    });

                localStorage.setItem('simrs-open-menu', JSON.stringify(openKeys));
            });
        }
    });

    document.querySelectorAll('[data-confirm]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            if (!confirm(link.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    const chart = document.getElementById('visitChart');
    if (chart) {
        const ctx = chart.getContext('2d');
        const labels = JSON.parse(chart.dataset.labels || '[]');
        const values = JSON.parse(chart.dataset.values || '[]');
        const max = Math.max(1, ...values);
        const width = chart.width = chart.offsetWidth;
        const height = chart.height = 220;
        const gap = 22;
        const barWidth = (width - gap * (values.length + 1)) / Math.max(1, values.length);

        ctx.clearRect(0, 0, width, height);
        ctx.font = '13px Segoe UI, Arial';
        ctx.textBaseline = 'middle';

        values.forEach(function (value, index) {
            const barHeight = (height - 62) * (value / max);
            const x = gap + index * (barWidth + gap);
            const y = height - 38 - barHeight;

            ctx.fillStyle = ['#0f766e', '#2563eb', '#b45309', '#7c3aed'][index % 4];
            ctx.fillRect(x, y, barWidth, barHeight);
            ctx.fillStyle = '#152033';
            ctx.fillText(String(value), x + barWidth / 2 - 4, y - 12);
            ctx.fillStyle = '#69758a';
            ctx.fillText(labels[index] || '', x, height - 18);
        });
    }
})();
