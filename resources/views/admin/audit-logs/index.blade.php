@extends('admin.layout.master')
@section('title', 'Audit Log')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">System Administration</span><h1>Audit Log</h1></div></div>
<div class="ltd-panel">
    <div class="mb-3"><h3 class="ltd-panel__title mb-1">Administrative Activity</h3><p class="text-muted mb-0">A read-only history of important user and question changes.</p></div>
    @include('admin.crud.partials.table-controls', [
        'items' => $logs,
        'sortOptions' => ['created_at' => 'Date', 'event' => 'Action', 'subject_type' => 'Resource', 'subject_label' => 'Item', 'id' => 'ID'],
        'filters' => [
            'event' => ['label' => 'Actions', 'options' => ['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'restored' => 'Restored', 'permanently_deleted' => 'Permanently deleted']],
            'subject_type' => ['label' => 'Resources', 'options' => ['User' => 'Users', 'Question' => 'Questions']],
        ],
    ])
    @if($logs->isEmpty())
        <div class="text-center py-5"><i class="fas fa-history fa-3x text-muted mb-3"></i><h4>No activity recorded</h4><p class="text-muted mb-0">New user and question changes will appear here.</p></div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Date</th><th>Administrator</th><th>Action</th><th>Resource</th><th>Item</th><th>Changes</th></tr></thead><tbody>
        @foreach($logs as $log)<tr>
            <td>{{ $log->created_at->format('M j, Y g:i A') }}</td><td>{{ $log->actor?->name ?? 'System' }}</td>
            <td><span class="badge badge-{{ $log->event === 'created' || $log->event === 'restored' ? 'success' : ($log->event === 'updated' ? 'info' : 'danger') }}">{{ str_replace('_', ' ', ucfirst($log->event)) }}</span></td>
            <td>{{ $log->subject_type }}</td><td>{{ $log->subject_label ?? '#'.$log->subject_id }}</td>
            <td>@if($log->event === 'updated')<small>{{ implode(', ', array_keys($log->new_values ?? [])) ?: 'No field changes' }}</small>@else<span class="text-muted">—</span>@endif</td>
        </tr>@endforeach
        </tbody></table></div>
        @include('admin.crud.partials.table-pagination', ['items' => $logs])
    @endif
</div>
@endsection
