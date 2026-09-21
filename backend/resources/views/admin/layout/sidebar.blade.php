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
        <li class="ltd-nav__section">Overview</li>
        <li><a href="{{ route('adminDashboard') }}" class="ltd-nav__item {{ Request::is('admin') ? 'is-active' : '' }}"><i class="fas fa-th-large"></i> Dashboard</a></li>
        <li><a href="{{ route('adminAnalytics') }}" class="ltd-nav__item {{ Request::is('admin/analytics') ? 'is-active' : '' }}"><i class="fas fa-chart-bar"></i> Analytics</a></li>

        @php($contentActive = Request::is('admin/questions*', 'admin/add-question', 'admin/edit-question/*', 'admin/exam-papers*', 'admin/add-exam-paper', 'admin/edit-exam-paper/*', 'admin/traffic-signs*', 'admin/add-traffic-sign', 'admin/edit-traffic-sign/*', 'admin/vision-tests*', 'admin/add-vision-test', 'admin/edit-vision-test/*', 'admin/exam-information*', 'admin/add-exam-information', 'admin/edit-exam-information/*', 'admin/tutorials*', 'admin/add-tutorial', 'admin/edit-tutorial/*', 'admin/notices*', 'admin/add-notice', 'admin/edit-notice/*'))
        <li>
            <button type="button" class="ltd-nav__item ltd-nav__toggle {{ $contentActive ? 'is-open' : '' }}" id="contentNavToggle" aria-expanded="{{ $contentActive ? 'true' : 'false' }}" aria-controls="contentNavItems">
                <i class="fas fa-layer-group" aria-hidden="true"></i><span>Content</span><i class="fas fa-chevron-down ltd-nav__toggle-icon" aria-hidden="true"></i>
            </button>
            <ul class="ltd-nav__submenu {{ $contentActive ? 'is-open' : '' }}" id="contentNavItems">
                <li><a href="{{ route('allQuestion') }}" class="ltd-nav__item {{ Request::is('admin/questions*', 'admin/add-question', 'admin/edit-question/*') ? 'is-active' : '' }}"><i class="fas fa-question-circle"></i> Questions</a></li>
                <li><a href="{{ route('allExamPaper') }}" class="ltd-nav__item {{ Request::is('admin/exam-papers*', 'admin/add-exam-paper', 'admin/edit-exam-paper/*') ? 'is-active' : '' }}"><i class="fas fa-file-alt"></i> Question Banks</a></li>
                <li><a href="{{ route('allTrafficSign') }}" class="ltd-nav__item {{ Request::is('admin/traffic-signs*', 'admin/add-traffic-sign', 'admin/edit-traffic-sign/*') ? 'is-active' : '' }}"><i class="fas fa-map-signs"></i> Traffic Signs</a></li>
                <li><a href="{{ route('allVisionTest') }}" class="ltd-nav__item {{ Request::is('admin/vision-tests*', 'admin/add-vision-test', 'admin/edit-vision-test/*') ? 'is-active' : '' }}"><i class="fas fa-eye"></i> Vision Tests</a></li>
                <li><a href="{{ route('allExamInformation') }}" class="ltd-nav__item {{ Request::is('admin/exam-information*', 'admin/add-exam-information', 'admin/edit-exam-information/*') ? 'is-active' : '' }}"><i class="fas fa-info-circle"></i> Exam Information</a></li>
                <li><a href="{{ route('allTutorial') }}" class="ltd-nav__item {{ Request::is('admin/tutorials*', 'admin/add-tutorial', 'admin/edit-tutorial/*') ? 'is-active' : '' }}"><i class="fas fa-desktop"></i> Tutorials</a></li>
                <li><a href="{{ route('allNotice') }}" class="ltd-nav__item {{ Request::is('admin/notices*', 'admin/add-notice', 'admin/edit-notice/*') ? 'is-active' : '' }}"><i class="fas fa-bell"></i> Notices</a></li>
            </ul>
        </li>
        <li class="ltd-nav__section">Review</li>
        <li><a href="{{ route('reviewOperations') }}" class="ltd-nav__item"><i class="fas fa-tasks"></i> Review Operations</a></li>
        <li><a href="{{ route('backups') }}" class="ltd-nav__item"><i class="fas fa-database"></i> Backups</a></li>
        <li><a href="{{ route('scrapingSources') }}" class="ltd-nav__item"><i class="fas fa-cog"></i> Scraping Sources</a></li>
        <li><a href="{{ route('importHistory') }}" class="ltd-nav__item {{ Request::is('admin/import-history') ? 'is-active' : '' }}"><i class="fas fa-history"></i> Import History</a></li>
        <li><a href="{{ route('importProgress') }}" class="ltd-nav__item {{ Request::is('admin/import-progress') ? 'is-active' : '' }}"><i class="fas fa-tasks"></i> Import Progress</a></li>
        <li><a href="{{ route('learningContent') }}" class="ltd-nav__item {{ Request::is('admin/learning-content*', 'admin/website-import*', 'admin/pdf-questions*', 'admin/pdf-translations*', 'admin/extraction-runs*') ? 'is-active' : '' }}"><i class="fas fa-book-reader"></i> Learning Content</a></li>
        <li><a href="{{ route('contentReadiness') }}" class="ltd-nav__item {{ Request::is('admin/content-readiness*') ? 'is-active' : '' }}"><i class="fas fa-clipboard-check"></i> Question Drafts</a></li>
        <li><a href="{{ route('governmentNotices') }}" class="ltd-nav__item {{ Request::is('admin/government-notices*') ? 'is-active' : '' }}"><i class="fas fa-landmark"></i> Government Notices</a></li>
        <li class="ltd-nav__section">People</li>
        <li><a href="{{ route('allUser') }}" class="ltd-nav__item {{ Request::is('admin/users*', 'admin/add-user', 'admin/edit-user/*') ? 'is-active' : '' }}"><i class="fas fa-users"></i> Users</a></li>
        <li><a href="{{ route('support.index') }}" class="ltd-nav__item {{ Request::is('admin/support*') ? 'is-active' : '' }}"><i class="fas fa-comments"></i> Support &amp; Feedback</a></li>
        <li class="ltd-nav__section">System</li>
        <li><a href="{{ route('mediaLibrary') }}" class="ltd-nav__item {{ Request::is('admin/media-library') ? 'is-active' : '' }}"><i class="fas fa-photo-video"></i> Media Library</a></li>
        <li><a href="{{ route('auditLogs') }}" class="ltd-nav__item {{ Request::is('admin/audit-logs') ? 'is-active' : '' }}"><i class="fas fa-history"></i> Audit Log</a></li>
        <li><a href="{{ route('appSettings') }}" class="ltd-nav__item {{ Request::is('admin/settings') ? 'is-active' : '' }}"><i class="fas fa-sliders-h"></i> Application Settings</a></li>

        <li class="ltd-nav__section">Account</li>
        <li><a href="{{ route('profileSettings') }}" class="ltd-nav__item {{ Request::is('admin/profile-settings*') ? 'is-active' : '' }}"><i class="fas fa-user-cog"></i> Profile Settings</a></li>
    </ul>

    <div class="ltd-sidebar__footer">
        &copy; {{ date('Y') }} Learn To Drive
    </div>
</aside>
