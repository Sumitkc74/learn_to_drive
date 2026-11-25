<!-- Custom Topbar -->
<header class="ltd-topbar">
    <div class="ltd-topbar__left">
        <button class="ltd-topbar__toggle" id="ltdSidebarToggle" type="button" aria-label="Toggle menu" aria-controls="ltdSidebar" aria-expanded="false">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>

        <form class="ltd-topbar__search" action="{{ route('adminSearch') }}" method="GET" role="search">
            <button type="submit" aria-label="Search all sections"><i class="fas fa-search" aria-hidden="true"></i></button>
            <input type="search" name="q" value="{{ request()->routeIs('adminSearch') ? request('q') : '' }}" maxlength="100" placeholder="Search all sections..." aria-label="Search all sections">
        </form>
    </div>

    <div class="ltd-topbar__right">
        <button type="button" class="ltd-topbar__theme-toggle" id="ltdThemeToggle" aria-label="Toggle light and dark mode" aria-pressed="false">
            <i class="fas fa-moon" aria-hidden="true"></i>
            <span>Dark mode</span>
        </button>

        <a href="#" class="ltd-topbar__icon-btn" data-widget="fullscreen" role="button" aria-label="Toggle fullscreen">
            <i class="fas fa-expand-arrows-alt" aria-hidden="true"></i>
        </a>

        <a href="{{ route('logout') }}" class="ltd-topbar__logout"
            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <i class="fas fa-sign-out-alt" aria-hidden="true"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>
    </div>
</header>
