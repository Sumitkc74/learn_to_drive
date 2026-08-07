<!-- Custom Topbar -->
<header class="ltd-topbar">
    <div class="ltd-topbar__left">
        <button class="ltd-topbar__toggle" id="ltdSidebarToggle" type="button" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>

        <form class="ltd-topbar__search" action="#" method="GET">
            <i class="fas fa-search"></i>
            <input type="search" name="q" placeholder="Search..." aria-label="Search">
        </form>
    </div>

    <div class="ltd-topbar__right">
        <button type="button" class="ltd-topbar__theme-toggle" id="ltdThemeToggle" aria-label="Toggle light and dark mode" aria-pressed="false">
            <i class="fas fa-moon" aria-hidden="true"></i>
            <span>Dark mode</span>
        </button>

        <a href="#" class="ltd-topbar__icon-btn" data-widget="fullscreen" role="button" title="Fullscreen">
            <i class="fas fa-expand-arrows-alt"></i>
        </a>

        <a href="{{ route('logout') }}" class="ltd-topbar__logout"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</header>