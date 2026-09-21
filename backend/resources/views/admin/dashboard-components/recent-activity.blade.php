<div class="ltd-panel ltd-activity">
    <div class="d-flex justify-content-between align-items-center"><h2 class="ltd-panel__title">Recent Admin Activity</h2><a href="{{ route('auditLogs') }}" class="small">View all</a></div>
    @if($recentActivity->isEmpty())<p class="ltd-activity__empty">No administrative changes recorded yet.</p>@else
    <ul class="ltd-activity__list">@foreach($recentActivity as $entry)<li><a href="{{ route('auditLogs', ['search' => $entry->subject_label]) }}" class="ltd-activity__item"><span class="ltd-activity__icon"><i class="fas fa-history"></i></span><span class="ltd-activity__body"><span class="ltd-activity__type">{{ $entry->actor?->name ?? 'System' }} · {{ str_replace('_', ' ', $entry->event) }}</span><span class="ltd-activity__label">{{ $entry->subject_type }}: {{ $entry->subject_label }}</span></span><span class="ltd-activity__time">{{ $entry->created_at?->diffForHumans() }}</span></a></li>@endforeach</ul>
    @endif
</div>
