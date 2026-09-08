<section class="ltd-panel ltd-management">
    <div class="ltd-section-heading">
        <div><h2 class="ltd-panel__title">Management</h2><p>Open and manage each part of the learner application.</p></div>
    </div>
    <div class="ltd-management__grid">
        @foreach([
            [route('allUser'), 'fa-users', 'Users', 'Accounts, roles and learning history'],
            [route('allQuestion'), 'fa-question-circle', 'Questions', 'Question bank and publishing'],
            [route('allExamPaper'), 'fa-file-alt', 'Exam Papers', 'Practice exam documents'],
            [route('allTrafficSign'), 'fa-map-signs', 'Traffic Signs', 'Road-sign learning content'],
            [route('allVisionTest'), 'fa-eye', 'Vision Tests', 'Eyesight test material'],
            [route('allExamInformation'), 'fa-info-circle', 'Exam Information', 'Test guidance and requirements'],
            [route('allTutorial'), 'fa-desktop', 'Tutorials', 'Learning videos and guides'],
            [route('allNotice'), 'fa-bell', 'Notices', 'Announcements and schedules'],
        ] as [$link, $icon, $title, $description])
            <a href="{{ $link }}" class="ltd-management-card">
                <span class="ltd-management-card__icon"><i class="fas {{ $icon }}"></i></span>
                <span class="ltd-management-card__body"><strong>{{ $title }}</strong><small>{{ $description }}</small></span>
                <i class="fas fa-chevron-right ltd-management-card__arrow" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
</section>
