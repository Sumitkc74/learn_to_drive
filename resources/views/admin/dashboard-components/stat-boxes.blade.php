<div class="ltd-stats">
    @foreach([
        [route('allUser'), 'fa-users', $users['total'], 'Total users'],
        [route('allUser', ['verification' => 'verified']), 'fa-user-check', $users['verified'], 'Fully verified'],
        [route('allUser', ['role' => 'PremiumUser']), 'fa-crown', $users['premium'], 'Premium users'],
        [route('allUser', ['joined' => 'this_month']), 'fa-user-plus', $users['new_this_month'], 'New this month'],
        [route('allQuestion', ['status' => 'Published']), 'fa-question-circle', $questions['published'], 'Published questions'],
        [route('allQuestion', ['status' => 'Draft']), 'fa-pencil-alt', $questions['draft'], 'Question drafts'],
        [route('adminAnalytics').'#learning-performance', 'fa-clipboard-check', $performance['attempts'], 'Exam attempts'],
        [route('adminAnalytics').'#learning-performance', 'fa-chart-line', $performance['average'].'%', 'Average score'],
    ] as [$link, $icon, $value, $label])
    <a href="{{ $link }}" class="ltd-stat-card"><div class="ltd-stat-card__icon"><i class="fas {{ $icon }}"></i></div><div><div class="ltd-stat-card__value">{{ $value }}</div><div class="ltd-stat-card__label">{{ $label }}</div></div></a>
    @endforeach
</div>
