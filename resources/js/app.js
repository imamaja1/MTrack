window.setTheme = function (theme) {
    document.documentElement.setAttribute('data-theme', theme);
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: theme } }));
};

window.toggleTheme = function () {
    var current = document.documentElement.getAttribute('data-theme');
    window.setTheme(current === 'dark' ? 'light' : 'dark');
};

document.addEventListener('DOMContentLoaded', function () {
    var stored = localStorage.getItem('theme');
    if (!stored) {
        var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        window.setTheme(prefersDark ? 'dark' : 'light');
    }

    var themeObserver = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                var stored = localStorage.getItem('theme');
                var current = document.documentElement.getAttribute('data-theme');
                if (stored && current !== stored) {
                    document.documentElement.setAttribute('data-theme', stored);
                }
            }
        });
    });
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });

    window.addEventListener('theme-changed', function () {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            var sunIcon = btn.querySelector('[data-icon="sun"]');
            var moonIcon = btn.querySelector('[data-icon="moon"]');
            if (sunIcon) sunIcon.style.display = isDark ? 'none' : '';
            if (moonIcon) moonIcon.style.display = isDark ? '' : 'none';
        });
    });

    document.dispatchEvent(new CustomEvent('theme-changed'));

    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[wire\\:navigate], a[wire\\:navigate\\.hover]');
        if (!link || !link.href || link.href === window.location.href) return;

        if (link.closest('details')) {
            e.preventDefault();
            e.stopPropagation();
            document.documentElement.setAttribute('data-navigating', '1');
            var ov = document.getElementById('page-loading-overlay');
            if (ov) ov.style.display = 'flex';
            if (window.Livewire && window.Livewire.navigate) {
                window.Livewire.navigate(link.href);
            }
            return;
        }

        document.documentElement.setAttribute('data-navigating', '1');
        var overlay = document.getElementById('page-loading-overlay');
        if (overlay) overlay.style.display = 'flex';
    }, true);

    document.addEventListener('livewire:navigating', function () {
        document.documentElement.setAttribute('data-navigating', '1');
        var drawer = document.getElementById('main-drawer');
        if (drawer) drawer.checked = false;
        var overlay = document.getElementById('page-loading-overlay');
        if (overlay) overlay.style.display = 'flex';
    });

    document.addEventListener('livewire:navigated', function () {
        document.documentElement.removeAttribute('data-navigating');
        var overlay = document.getElementById('page-loading-overlay');
        if (overlay) overlay.style.display = 'none';

        var stored = localStorage.getItem('theme');
        if (stored) {
            document.documentElement.setAttribute('data-theme', stored);
            if (stored === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }

        window.dispatchEvent(new CustomEvent('theme-changed'));
    });
});
