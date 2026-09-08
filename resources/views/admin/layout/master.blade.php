<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', 'Dashboard') &middot; Learn To Drive Admin</title>

        @include('admin.layout.css')
        <!-- Custom theme (built from scratch) -->
        <link rel="stylesheet" href="{{ asset('dist/css/admin-theme.css') }}">
        @yield('page-style')
    </head>

    <body class="ltd-body">
        <div class="ltd-app">

            @include('admin.layout.sidebar')

            <div class="ltd-main">

                @include('admin.layout.header')

                <main class="ltd-content">
                    @include('admin.layout.flash')
                    @yield('content')
                </main>

                @include('admin.layout.footer')
            </div>
        </div>

        @include('admin.layout.script')

        <script>
            (function () {
                const body = document.body;
                const sidebar = document.getElementById('ltdSidebar');
                const toggleButton = document.getElementById('ltdSidebarToggle');
                const themeButton = document.getElementById('ltdThemeToggle');
                const managementToggle = document.getElementById('managementNavToggle');
                const managementItems = document.getElementById('managementNavItems');
                const storageKey = 'ltd-theme';

                // Custom sidebar toggle (mobile) — independent of AdminLTE's pushmenu widget
                toggleButton?.addEventListener('click', function () {
                    sidebar?.classList.toggle('is-open');
                });

                if (managementToggle && managementItems) {
                    const savedManagementState = localStorage.getItem('ltd-management-open');
                    const containsActivePage = managementItems.querySelector('.is-active') !== null;
                    const shouldOpen = containsActivePage || savedManagementState === 'true';
                    managementToggle.classList.toggle('is-open', shouldOpen);
                    managementItems.classList.toggle('is-open', shouldOpen);
                    managementToggle.setAttribute('aria-expanded', String(shouldOpen));

                    managementToggle.addEventListener('click', function () {
                        const isOpen = !managementItems.classList.contains('is-open');
                        managementItems.classList.toggle('is-open', isOpen);
                        managementToggle.classList.toggle('is-open', isOpen);
                        managementToggle.setAttribute('aria-expanded', String(isOpen));
                        localStorage.setItem('ltd-management-open', String(isOpen));
                    });
                }

                const applyTheme = (theme) => {
                    const isDark = theme === 'dark';
                    body.classList.toggle('ltd-theme-dark', isDark);
                    body.classList.toggle('ltd-theme-light', !isDark);

                    if (themeButton) {
                        themeButton.setAttribute('aria-pressed', String(isDark));
                        const icon = themeButton.querySelector('i');
                        const label = themeButton.querySelector('span');

                        if (icon) {
                            icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                        }

                        if (label) {
                            label.textContent = isDark ? 'Light mode' : 'Dark mode';
                        }
                    }
                };

                const savedTheme = localStorage.getItem(storageKey);
                const preferredTheme = savedTheme || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                applyTheme(preferredTheme);

                themeButton?.addEventListener('click', function () {
                    const nextTheme = body.classList.contains('ltd-theme-dark') ? 'light' : 'dark';
                    localStorage.setItem(storageKey, nextTheme);
                    applyTheme(nextTheme);
                });
            })();
        </script>

        @yield('page-script')
    </body>
</html>
