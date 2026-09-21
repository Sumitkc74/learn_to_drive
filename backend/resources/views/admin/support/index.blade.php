@extends('admin.layout.master')
@section('title', 'Support & Feedback')
@section('content')
<div class="ltd-page-header"><div><h1>Support &amp; Feedback</h1><p class="text-muted">User complaints, feature requests, and content reports.</p></div></div>
<div class="ltd-panel">
@include('admin.crud.partials.table-controls', ['items' => $tickets, 'sortOptions' => ['created_at' => 'Submitted', 'updated_at' => 'Updated', 'subject' => 'Subject', 'status' => 'Status'], 'filters' => ['status' => ['label' => 'Status', 'options' => array_combine(\App\Models\SupportTicket::STATUSES, \App\Models\SupportTicket::STATUSES)], 'type' => ['label' => 'Type', 'options' => array_combine(\App\Models\SupportTicket::TYPES, \App\Models\SupportTicket::TYPES)]]])
<div class="table-responsive"><table class="table"><thead><tr><th>Ticket</th><th>User</th><th>Type</th><th>Status</th><th>Submitted</th></tr></thead><tbody>
@forelse($tickets as $ticket)
<tr><td><a href="{{ route('support.show', $ticket) }}">#{{ $ticket->id }} {{ $ticket->subject }}</a></td><td>{{ $ticket->user?->name ?? 'Former user' }}</td><td>{{ $ticket->type }}</td><td>{{ $ticket->status }}</td><td>{{ $ticket->created_at->format('M j, Y H:i') }}</td></tr>
@empty<tr><td colspan="5" class="text-muted text-center">No support tickets match your filters.</td></tr>@endforelse
</tbody></table></div>
@include('admin.crud.partials.table-pagination', ['items' => $tickets])
</div>
@endsection
