@php
    $creatorLabel = $record->creator?->name;
    if (!$creatorLabel) {
        $creatorLabel = $record->created_by ? 'Former admin' : 'Legacy record';
        if (!$record->created_by && $record instanceof \App\Models\User) {
            $creatorLabel = $record->is_seed_admin ? 'System account'
                : (in_array($record->role, ['User', 'PremiumUser']) ? 'Self-registered' : 'Legacy record');
        }
    }
@endphp
<div class="ltd-creator">
    <span class="ltd-creator__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($creatorLabel, 0, 1)) }}</span>
    <span>
        <strong>{{ $creatorLabel }}</strong>
        <small>{{ $record->created_at?->format('M j, Y') ?? 'Date unavailable' }}</small>
    </span>
</div>

@if($versionType = \App\Support\VersionedContent::type($record))<a class="small" href="{{ route('contentVersions',[$versionType,$record->id]) }}">Content history</a>@endif
