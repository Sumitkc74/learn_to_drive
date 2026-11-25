<div class="ltd-panel mb-3"><h2>Import alerts</h2><div class="d-flex flex-wrap" style="gap:1rem">
<a href="{{ route('importHistory') }}">Failed sources: {{ $importAlerts['failed_sources'] ?? 'unavailable' }}</a>
<a href="{{ route('learningContent') }}">Sources needing re-review: {{ $importAlerts['needs_review'] ?? 'unavailable' }}</a>
<a href="{{ route('learningContent') }}">Failed AI checks: {{ $importAlerts['failed_ai'] ?? 'unavailable' }} (learning content and notices)</a>
<a href="{{ route('governmentNotices') }}">Review government notices</a>
<a href="{{ route('admin.schools.index') }}">Declined rate proposals: {{ $importAlerts['declined_rates'] ?? 'unavailable' }}</a>
<a href="{{ route('allNotice',['sort'=>'expires_at','direction'=>'asc','status'=>'Published']) }}">Notices expiring within 7 days: {{ $importAlerts['expiring'] ?? 'unavailable' }}</a>
</div></div>
