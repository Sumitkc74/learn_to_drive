<div class="ltd-stats">
    @foreach([
        ['fa-users', $users['total'], 'Total users'],
        ['fa-crown', $users['premium'], 'Premium users'],
        ['fa-user-plus', $users['new_this_month'], 'New this month'],
        ['fa-school', $operations['schools'], 'Driving schools'],
        ['fa-clock', $operations['schools_pending'], 'Pending schools'],
        ['fa-calendar-check', $operations['lesson_bookings'], 'Lesson bookings'],
    ] as [$icon, $value, $label])
    <div class="ltd-stat-card"><div class="ltd-stat-card__icon"><i class="fas {{ $icon }}"></i></div><div><div class="ltd-stat-card__value">{{ $value ?? '—' }}</div><div class="ltd-stat-card__label">{{ $label }}</div></div></div>
    @endforeach
</div>
