<div class="border rounded p-3 mb-3">
    <p class="small">Assigned to: {{ $screened->assigned_to ? (\App\Models\User::find($screened->assigned_to)?->name ?? 'Former admin') : 'Unassigned' }} <a href="{{ route('reviewOperations',['type'=>$screeningKind==='notice'?'government':'learning']) }}">Manage assignment</a></p>
    <strong>Scraping → AI screening → Human review</strong>
    <p class="mb-1">AI: {{ $screened->ai_status ?? 'Not screened (older import)' }}</p>
    <p class="small text-muted">Automatic retries: {{ $screened->ai_retry_count ?? 0 }}/3. Failed or stalled checks are retried after 10 minutes.</p>
    @if($screened->ai_report)<p class="small" style="white-space: pre-wrap">{{ $screened->ai_report }}</p>@endif
    <p class="small text-muted">AI suggestions can be incorrect. Inspect the original before approving. Ready and Flagged both require human review.</p>
    @if($screened->status === 'Pending')
    <form method="POST" action="{{ route('contentScreening.retry', [$screeningKind, $screened->id]) }}">@csrf<button class="btn btn-sm btn-outline-primary">Run / retry AI screening</button></form>
    @endif
</div>
