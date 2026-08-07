<div class="ltd-stats">

    <a href="/admin/users" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-user-plus"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\User::count() }}</div>
            <div class="ltd-stat-card__label">User Registrations</div>
        </div>
    </a>

    <a href="/admin/traffic-signs" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-map-signs"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\TrafficSign::count() }}</div>
            <div class="ltd-stat-card__label">Traffic Signs</div>
        </div>
    </a>

    <a href="/admin/exam-papers" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-file-alt"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\ExamPaper::count() }}</div>
            <div class="ltd-stat-card__label">Exam Papers</div>
        </div>
    </a>

    <a href="/admin/vision-tests" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-low-vision"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\VisionTest::count() }}</div>
            <div class="ltd-stat-card__label">Vision Tests</div>
        </div>
    </a>

    <a href="/admin/exam-information" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-info"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\ExamInformation::count() }}</div>
            <div class="ltd-stat-card__label">Exam Information</div>
        </div>
    </a>

    <a href="/admin/questions" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-question"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\Question::count() }}</div>
            <div class="ltd-stat-card__label">Exam Questions</div>
        </div>
    </a>

    <a href="/admin/tutorials" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-desktop"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\Tutorial::count() }}</div>
            <div class="ltd-stat-card__label">Tutorials</div>
        </div>
    </a>

    <a href="/admin/notices" class="ltd-stat-card">
        <div class="ltd-stat-card__icon"><i class="fas fa-bell"></i></div>
        <div>
            <div class="ltd-stat-card__value">{{ \App\Models\Notice::count() }}</div>
            <div class="ltd-stat-card__label">Notices</div>
        </div>
    </a>

</div>