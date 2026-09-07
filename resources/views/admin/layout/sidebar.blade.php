<!-- Custom Sidebar -->
<aside class="ltd-sidebar" id="ltdSidebar">
    <a href="/admin" class="ltd-sidebar__brand">
        <img src="{{ asset('dist/img/logo.svg') }}" alt="Learn To Drive Logo">
        <span>Learn To Drive</span>
    </a>

    @php
        $sidebarUser = Auth::user();
        $sidebarUserName = $sidebarUser?->name ?? 'Guest';
        $sidebarUserRole = $sidebarUser?->role ?? 'Admin';
    @endphp

    <div class="ltd-sidebar__user">
        <img src="{{ $sidebarUser?->avatar_url ?? asset('dist/img/avatar.png') }}" alt="{{ $sidebarUserName }} profile photo">
        <div>
            <span class="ltd-sidebar__user-name">{{ $sidebarUserName }}</span>
            <small>{{ $sidebarUserRole }}</small>
        </div>
    </div>

    <ul class="ltd-nav">
        <li>
            <a href="/admin" class="ltd-nav__item {{ Request::is('admin') ? 'is-active' : '' }}">
                <i class="fas fa-th-large"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="/admin/profile-settings" class="ltd-nav__item {{ Request::is('admin/profile-settings') ? 'is-active' : '' }}">
                <i class="fas fa-user-cog"></i> Profile Settings
            </a>
        </li>
        <li>
            <a href="{{ route('auditLogs') }}" class="ltd-nav__item {{ Request::is('admin/audit-logs') ? 'is-active' : '' }}">
                <i class="fas fa-history"></i> Audit Log
            </a>
        </li>
        <li><a href="{{ route('appSettings') }}" class="ltd-nav__item {{ Request::is('admin/settings') ? 'is-active' : '' }}"><i class="fas fa-sliders-h"></i> Application Settings</a></li>
    </ul>

    <div class="ltd-sidebar__footer">
        &copy; {{ date('Y') }} Learn To Drive
    </div>
</aside>
