@extends('admin.layout.master')
@section('title', 'Support Ticket')
@section('content')
<div class="ltd-page-header"><h1>Ticket #{{ $ticket->id }}</h1><a href="{{ route('support.index') }}" class="btn btn-outline-secondary">Back to Inbox</a></div>
<div class="ltd-panel mb-3" style="overflow-wrap:anywhere">
<h2>{{ $ticket->subject }}</h2><p>{{ $ticket->type }} · {{ $ticket->status }}</p>
<p>{{ $ticket->user?->name ?? 'Former user' }} · {{ $ticket->user?->email }}</p>
@if($ticket->content_type)<p>Related content: {{ $ticket->content_type }} #{{ $ticket->content_id }}
@php($related = \App\Support\ReportableContent::TYPES[$ticket->content_type] ?? null)
@if($related && $related[0]::whereKey($ticket->content_id)->exists())<a href="{{ route($related[1],$ticket->content_id) }}">Open content</a>@else<span class="text-muted">Content unavailable</span>@endif</p>@endif
<p style="white-space:pre-wrap">{{ $ticket->message }}</p>
</div>
@foreach($replies as $reply)<div class="ltd-panel mb-3" style="overflow-wrap:anywhere"><strong>{{ $reply->author_role }} · {{ $reply->created_at->format('M j, Y H:i') }}</strong><p style="white-space:pre-wrap">{{ $reply->message }}</p></div>@endforeach
{{ $replies->links() }}
<form class="ltd-panel" method="POST" action="{{ route('support.update', $ticket) }}">@csrf
<div class="form-group"><label for="reply">Reply to user (optional)</label><textarea id="reply" name="message" maxlength="5000" rows="5" class="form-control">{{ old('message') }}</textarea><small>Replies are visible to the user.</small></div>
<div class="form-group"><label for="status">Status</label><select id="status" name="status" class="form-control">@foreach(\App\Models\SupportTicket::STATUSES as $status)<option @selected(old('status', $ticket->status) === $status)>{{ $status }}</option>@endforeach</select></div>
<button class="btn btn-primary">Save Update</button>
</form>
@endsection
