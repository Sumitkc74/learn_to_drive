@php
    // Pull the most recent record from each content type and merge into one feed
    $activity = collect();

    foreach (\App\Models\User::latest()->take(5)->get() as $item) {
        $activity->push(['type' => 'User', 'icon' => 'fa-user-plus', 'label' => $item->name, 'time' => $item->created_at, 'link' => route('editUser', $item->id)]);
    }
    foreach (\App\Models\Question::latest()->take(5)->get() as $item) {
        $activity->push(['type' => 'Question', 'icon' => 'fa-question', 'label' => \Illuminate\Support\Str::limit($item->question, 45), 'time' => $item->created_at, 'link' => route('editQuestion', $item->id)]);
    }
    foreach (\App\Models\Notice::latest()->take(5)->get() as $item) {
        $activity->push(['type' => 'Notice', 'icon' => 'fa-bell', 'label' => $item->title, 'time' => $item->created_at, 'link' => route('editNotice', $item->id)]);
    }
    foreach (\App\Models\Tutorial::latest()->take(5)->get() as $item) {
        $activity->push(['type' => 'Tutorial', 'icon' => 'fa-desktop', 'label' => $item->title ?? ('Tutorial #' . $item->id), 'time' => $item->created_at, 'link' => route('editTutorial', $item->id)]);
    }

    $activity = $activity->sortByDesc('time')->take(8);
@endphp

<div class="ltd-panel ltd-activity">
    <h2 class="ltd-panel__title">Recent Activity</h2>

    @if($activity->isEmpty())
        <p class="ltd-activity__empty">Nothing added yet.</p>
    @else
        <ul class="ltd-activity__list">
            @foreach($activity as $entry)
                <li>
                    <a href="{{ $entry['link'] }}" class="ltd-activity__item">
                        <span class="ltd-activity__icon"><i class="fas {{ $entry['icon'] }}"></i></span>
                        <span class="ltd-activity__body">
                            <span class="ltd-activity__type">{{ $entry['type'] }}</span>
                            <span class="ltd-activity__label">{{ $entry['label'] }}</span>
                        </span>
                        <span class="ltd-activity__time">{{ $entry['time']?->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>