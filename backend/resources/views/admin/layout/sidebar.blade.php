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

    <nav aria-label="Admin sections">
    <ul class="ltd-nav">
        <li class="ltd-nav__section">Overview</li>
        <li><a href="{{ route('adminDashboard') }}" class="ltd-nav__item {{ Request::is('admin') ? 'is-active' : '' }}" @if(Request::is('admin')) aria-current="page" @endif><i class="fas fa-th-large" aria-hidden="true"></i> Dashboard</a></li>
        <li><a href="{{ route('adminAnalytics') }}" class="ltd-nav__item {{ Request::is('admin/analytics') ? 'is-active' : '' }}" @if(Request::is('admin/analytics')) aria-current="page" @endif><i class="fas fa-chart-bar" aria-hidden="true"></i> Analytics</a></li>

        @php($contentActive = Request::is('admin/questions*', 'admin/add-question', 'admin/edit-question/*', 'admin/exam-papers*', 'admin/add-exam-paper', 'admin/edit-exam-paper/*', 'admin/traffic-signs*', 'admin/add-traffic-sign', 'admin/edit-traffic-sign/*', 'admin/vision-tests*', 'admin/add-vision-test', 'admin/edit-vision-test/*', 'admin/exam-information*', 'admin/add-exam-information', 'admin/edit-exam-information/*', 'admin/tutorials*', 'admin/add-tutorial', 'admin/edit-tutorial/*', 'admin/notices*', 'admin/add-notice', 'admin/edit-notice/*'))
        <li>
            <button type="button" class="ltd-nav__item ltd-nav__toggle {{ $contentActive ? 'is-open' : '' }}" id="contentNavToggle" aria-expanded="{{ $contentActive ? 'true' : 'false' }}" aria-controls="contentNavItems">
                <i class="fas fa-layer-group" aria-hidden="true"></i><span>Content</span><i class="fas fa-chevron-down ltd-nav__toggle-icon" aria-hidden="true"></i>
            </button>
            <ul class="ltd-nav__submenu {{ $contentActive ? 'is-open' : '' }}" id="contentNavItems">
                <li><a href="{{ route('allQuestion') }}" class="ltd-nav__item {{ Request::is('admin/questions*', 'admin/add-question', 'admin/edit-question/*') ? 'is-active' : '' }}" @if(Request::is('admin/questions*', 'admin/add-question', 'admin/edit-question/*')) aria-current="page" @endif><i class="fas fa-question-circle" aria-hidden="true"></i> Questions</a></li>
                <li><a href="{{ route('allExamPaper') }}" class="ltd-nav__item {{ Request::is('admin/exam-papers*', 'admin/add-exam-paper', 'admin/edit-exam-paper/*') ? 'is-active' : '' }}" @if(Request::is('admin/exam-papers*', 'admin/add-exam-paper', 'admin/edit-exam-paper/*')) aria-current="page" @endif><i class="fas fa-file-alt" aria-hidden="true"></i> Question Banks</a></li>
                <li><a href="{{ route('allTrafficSign') }}" class="ltd-nav__item {{ Request::is('admin/traffic-signs*', 'admin/add-traffic-sign', 'admin/edit-traffic-sign/*') ? 'is-active' : '' }}" @if(Request::is('admin/traffic-signs*', 'admin/add-traffic-sign', 'admin/edit-traffic-sign/*')) aria-current="page" @endif><i class="fas fa-map-signs" aria-hidden="true"></i> Traffic Signs</a></li>
                <li><a href="{{ route('allVisionTest') }}" class="ltd-nav__item {{ Request::is('admin/vision-tests*', 'admin/add-vision-test', 'admin/edit-vision-test/*') ? 'is-active' : '' }}" @if(Request::is('admin/vision-tests*', 'admin/add-vision-test', 'admin/edit-vision-test/*')) aria-current="page" @endif><i class="fas fa-eye" aria-hidden="true"></i> Vision Tests</a></li>
                <li><a href="{{ route('allExamInformation') }}" class="ltd-nav__item {{ Request::is('admin/exam-information*', 'admin/add-exam-information', 'admin/edit-exam-information/*') ? 'is-active' : '' }}" @if(Request::is('admin/exam-information*', 'admin/add-exam-information', 'admin/edit-exam-information/*')) aria-current="page" @endif><i class="fas fa-info-circle" aria-hidden="true"></i> Exam Information</a></li>
                <li><a href="{{ route('allTutorial') }}" class="ltd-nav__item {{ Request::is('admin/tutorials*', 'admin/add-tutorial', 'admin/edit-tutorial/*') ? 'is-active' : '' }}" @if(Request::is('admin/tutorials*', 'admin/add-tutorial', 'admin/edit-tutorial/*')) aria-current="page" @endif><i class="fas fa-desktop" aria-hidden="true"></i> Tutorials</a></li>
                <li><a href="{{ route('allNotice') }}" class="ltd-nav__item {{ Request::is('admin/notices*', 'admin/add-notice', 'admin/edit-notice/*') ? 'is-active' : '' }}" @if(Request::is('admin/notices*', 'admin/add-notice', 'admin/edit-notice/*')) aria-current="page" @endif><i class="fas fa-bell" aria-hidden="true"></i> Notices</a></li>
            </ul>
        </li>
        <li class="ltd-nav__section">Review</li>
        <li><a href="{{ route('reviewOperations') }}" class="ltd-nav__item" @if(request()->routeIs('reviewOperations')) aria-current="page" @endif><i class="fas fa-tasks" aria-hidden="true"></i> Review Operations</a></li>
        <li><a href="{{ route('backups') }}" class="ltd-nav__item" @if(request()->routeIs('backups')) aria-current="page" @endif><i class="fas fa-database" aria-hidden="true"></i> Backups</a></li>
        <li><a href="{{ route('scrapingSources') }}" class="ltd-nav__item" @if(request()->routeIs('scrapingSources')) aria-current="page" @endif><i class="fas fa-cog" aria-hidden="true"></i> Scraping Sources</a></li>
        <li><a href="{{ route('importHistory') }}" class="ltd-nav__item {{ Request::is('admin/import-history') ? 'is-active' : '' }}" @if(Request::is('admin/import-history')) aria-current="page" @endif><i class="fas fa-history" aria-hidden="true"></i> Import History</a></li>
        <li><a href="{{ route('importProgress') }}" class="ltd-nav__item {{ Request::is('admin/import-progress') ? 'is-active' : '' }}" @if(Request::is('admin/import-progress')) aria-current="page" @endif><i class="fas fa-tasks" aria-hidden="true"></i> Import Progress</a></li>
        <li><a href="{{ route('learningContent') }}" class="ltd-nav__item {{ Request::is('admin/learning-content*', 'admin/website-import*', 'admin/pdf-questions*', 'admin/pdf-translations*', 'admin/extraction-runs*') ? 'is-active' : '' }}" @if(Request::is('admin/learning-content*', 'admin/website-import*', 'admin/pdf-questions*', 'admin/pdf-translations*', 'admin/extraction-runs*')) aria-current="page" @endif><i class="fas fa-book-reader" aria-hidden="true"></i> Learning Content</a></li>
        <li><a href="{{ route('contentReadiness') }}" class="ltd-nav__item {{ Request::is('admin/content-readiness*') ? 'is-active' : '' }}" @if(Request::is('admin/content-readiness*')) aria-current="page" @endif><i class="fas fa-clipboard-check" aria-hidden="true"></i> Question Drafts</a></li>
        <li><a href="{{ route('governmentNotices') }}" class="ltd-nav__item {{ Request::is('admin/government-notices*') ? 'is-active' : '' }}" @if(Request::is('admin/government-notices*')) aria-current="page" @endif><i class="fas fa-landmark" aria-hidden="true"></i> Government Notices</a></li>
        <li class="ltd-nav__section">People</li>
        <li><a href="{{ route('admin.schools.index') }}" class="ltd-nav__item {{ Request::is('admin/schools*') ? 'is-active' : '' }}" @if(Request::is('admin/schools*')) aria-current="page" @endif><i class="fas fa-school" aria-hidden="true"></i> Driving Schools</a></li>
        <li><a href="{{ route('admin.lessons.index') }}" class="ltd-nav__item {{ Request::is('admin/lessons*') ? 'is-active' : '' }}" @if(Request::is('admin/lessons*')) aria-current="page" @endif><i class="fas fa-calendar-alt" aria-hidden="true"></i> Lessons</a></li>
        <li><a href="{{ route('admin.lesson-bookings.index') }}" class="ltd-nav__item {{ Request::is('admin/lesson-bookings*') ? 'is-active' : '' }}" @if(Request::is('admin/lesson-bookings*')) aria-current="page" @endif><i class="fas fa-calendar-check" aria-hidden="true"></i> Lesson bookings</a></li>
        <li><a href="{{ route('allUser') }}" class="ltd-nav__item {{ Request::is('admin/users*', 'admin/add-user', 'admin/edit-user/*') ? 'is-active' : '' }}" @if(Request::is('admin/users*', 'admin/add-user', 'admin/edit-user/*')) aria-current="page" @endif><i class="fas fa-users" aria-hidden="true"></i> Users</a></li>
        <li><a href="{{ route('admin.premium-payments.index') }}" class="ltd-nav__item {{ Request::is('admin/premium-payments*') ? 'is-active' : '' }}" @if(Request::is('admin/premium-payments*')) aria-current="page" @endif><i class="fas fa-credit-card" aria-hidden="true"></i> Premium Payments</a></li>
        <li><a href="{{ route('admin.school-payments') }}" class="ltd-nav__item {{ Request::is('admin/school-payments*') ? 'is-active' : '' }}" @if(Request::is('admin/school-payments*')) aria-current="page" @endif><i class="fas fa-money-bill" aria-hidden="true"></i> School Payments</a></li>
        <li><a href="{{ route('support.index') }}" class="ltd-nav__item {{ Request::is('admin/support*') ? 'is-active' : '' }}" @if(Request::is('admin/support*')) aria-current="page" @endif><i class="fas fa-comments" aria-hidden="true"></i> Support &amp; Feedback</a></li>
        <li class="ltd-nav__section">System</li>
        <li><a href="{{ route('bulkImport') }}" class="ltd-nav__item {{ Request::is('admin/bulk-import*') ? 'is-active' : '' }}" @if(Request::is('admin/bulk-import*')) aria-current="page" @endif><i class="fas fa-file-import" aria-hidden="true"></i> CSV / Excel Imports</a></li>
        <li><a href="{{ route('mediaLibrary') }}" class="ltd-nav__item {{ Request::is('admin/media-library') ? 'is-active' : '' }}" @if(Request::is('admin/media-library')) aria-current="page" @endif><i class="fas fa-photo-video" aria-hidden="true"></i> Media Library</a></li>
        <li><a href="{{ route('auditLogs') }}" class="ltd-nav__item {{ Request::is('admin/audit-logs') ? 'is-active' : '' }}" @if(Request::is('admin/audit-logs')) aria-current="page" @endif><i class="fas fa-history" aria-hidden="true"></i> Audit Log</a></li>
        <li><a href="{{ route('appSettings') }}" class="ltd-nav__item {{ Request::is('admin/settings') ? 'is-active' : '' }}" @if(Request::is('admin/settings')) aria-current="page" @endif><i class="fas fa-sliders-h" aria-hidden="true"></i> Application Settings</a></li>

        <li class="ltd-nav__section">Account</li>
        <li><a href="{{ route('profileSettings') }}" class="ltd-nav__item {{ Request::is('admin/profile-settings*') ? 'is-active' : '' }}" @if(Request::is('admin/profile-settings*')) aria-current="page" @endif><i class="fas fa-user-cog" aria-hidden="true"></i> Profile Settings</a></li>
    </ul>
    </nav>

    <div class="ltd-sidebar__footer">
        &copy; {{ date('Y') }} Learn To Drive
    </div>
</aside>
