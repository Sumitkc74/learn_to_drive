<div class="ltd-stats">
    @foreach([
        ['fa-users', $users['total'], 'Total users', route('allUser')],
        ['fa-crown', $users['premium'], 'Premium users', route('allUser', ['role' => 'PremiumUser'])],
        ['fa-user-plus', $users['new_this_month'], 'New this month', route('allUser', ['joined' => 'this_month'])],
        ['fa-school', $operations['schools'], 'Driving schools', route('admin.schools.index')],
        ['fa-clock', $operations['schools_pending'], 'Pending schools', route('admin.schools.index', ['status' => 'Pending'])],
        ['fa-calendar-check', $operations['lesson_bookings'], 'Lesson bookings', route('admin.lesson-bookings.index')],
    ] as [$icon, $value, $label, $url])
    <a href="{{ $url }}"><div class="ltd-stat-card"><div class="ltd-stat-card__icon"><i class="fas {{ $icon }}"></i></div><div><div class="ltd-stat-card__value">{{ $value ?? '—' }}</div><div class="ltd-stat-card__label">{{ $label }}</div></div></div></a>
    @endforeach
</div>
