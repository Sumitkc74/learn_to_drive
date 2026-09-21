<div class="ltd-stats">
    @foreach([
        ['fa-users', $users['total'], 'Total users'],
        ['fa-user-check', $users['verified'], 'Fully verified'],
        ['fa-crown', $users['premium'], 'Premium users'],
        ['fa-user-plus', $users['new_this_month'], 'New this month'],
        ['fa-question-circle', $questions['published'], 'Published questions'],
        ['fa-pencil-alt', $questions['draft'], 'Question drafts'],
        ['fa-clipboard-check', $performance['attempts'], 'Exam attempts'],
        ['fa-chart-line', $performance['average'].'%', 'Average score'],
    ] as [$icon, $value, $label])
    <div class="ltd-stat-card"><div class="ltd-stat-card__icon"><i class="fas {{ $icon }}"></i></div><div><div class="ltd-stat-card__value">{{ $value }}</div><div class="ltd-stat-card__label">{{ $label }}</div></div></div>
    @endforeach
</div>
