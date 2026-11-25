{{-- Shared admin breadcrumb: one consistent trail on every admin page, built
     from the URL segments so pages do not need their own copy. Record IDs
     (numeric segments) are skipped; the last crumb is the current page. --}}
@php
    $breadcrumbLabels = [
        'add-exam-information' => 'Add Exam Information',
        'add-exam-paper' => 'Add Question Bank',
        'add-notice' => 'Add Notice',
        'add-question' => 'Add Question',
        'add-traffic-sign' => 'Add Traffic Sign',
        'add-tutorial' => 'Add Tutorial',
        'add-user' => 'Add User',
        'add-vision-test' => 'Add Vision Test',
        'analytics' => 'Analytics',
        'audit-logs' => 'Audit Log',
        'backups' => 'Backups',
        'bulk-import' => 'CSV / Excel Imports',
        'content-readiness' => 'Question Drafts',
        'delete' => 'Delete',
        'edit-exam-information' => 'Edit Exam Information',
        'edit-exam-paper' => 'Edit Question Bank',
        'edit-notice' => 'Edit Notice',
        'edit-question' => 'Edit Question',
        'edit-traffic-sign' => 'Edit Traffic Sign',
        'edit-tutorial' => 'Edit Tutorial',
        'edit-user' => 'Edit User',
        'edit-vision-test' => 'Edit Vision Test',
        'exam-information' => 'Exam Information',
        'exam-papers' => 'Question Banks',
        'extraction-runs' => 'Extraction Runs',
        'government-notices' => 'Government Notices',
        'import' => 'Import',
        'import-history' => 'Import History',
        'import-progress' => 'Import Progress',
        'learning-content' => 'Learning Content',
        'lesson-bookings' => 'Lesson bookings',
        'lessons' => 'Lessons',
        'media-library' => 'Media Library',
        'notices' => 'Notices',
        'payments' => 'Premium Payments',
        'pdf-questions' => 'Question Extraction',
        'pdf-translations' => 'Translations',
        'premium-payments' => 'Premium Payments',
        'profile-settings' => 'Profile Settings',
        'questions' => 'Questions',
        'review-operations' => 'Review Operations',
        'school-payments' => 'School Payments',
        'schools' => 'Driving Schools',
        'scraping-sources' => 'Scraping Sources',
        'search' => 'Search',
        'settings' => 'Application Settings',
        'support' => 'Support & Feedback',
        'traffic-signs' => 'Traffic Signs',
        'trash' => 'Trash',
        'tutorials' => 'Tutorials',
        'users' => 'Users',
        'vision-tests' => 'Vision Tests',
        'website-import' => 'Import from Website',
    ];
    $breadcrumbCrumbs = [];
    $breadcrumbPath = '';
    foreach (request()->segments() as $segment) {
        if (is_numeric($segment)) {
            continue;
        }
        $breadcrumbPath .= '/' . $segment;
        if ($segment === 'admin') {
            $breadcrumbCrumbs[] = ['label' => 'Home', 'url' => '/admin'];
            continue;
        }
        $breadcrumbCrumbs[] = [
            'label' => $breadcrumbLabels[$segment] ?? ucfirst(str_replace('-', ' ', $segment)),
            'url' => $breadcrumbPath,
        ];
    }
    if (count($breadcrumbCrumbs) === 1) {
        $breadcrumbCrumbs[] = ['label' => 'Dashboard', 'url' => null];
    }
@endphp
@if(count($breadcrumbCrumbs) > 1)
<nav aria-label="Breadcrumb" class="ltd-global-breadcrumb">
    <ol class="ltd-breadcrumb">
        @foreach($breadcrumbCrumbs as $index => $crumb)
            @if($index === count($breadcrumbCrumbs) - 1)
                <li aria-current="page">{{ $crumb['label'] }}</li>
            @else
                <li><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
