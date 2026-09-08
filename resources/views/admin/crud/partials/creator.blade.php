<div class="ltd-creator">
    <span class="ltd-creator__avatar" aria-hidden="true">{{ strtoupper(substr($record->creator?->name ?? ($record->created_by ? 'F' : 'L'), 0, 1)) }}</span>
    <span>
        <strong>{{ $record->creator?->name ?? ($record->created_by ? 'Former admin' : 'Legacy record') }}</strong>
        <small>{{ $record->created_at?->format('M j, Y') ?? 'Date unavailable' }}</small>
    </span>
</div>
