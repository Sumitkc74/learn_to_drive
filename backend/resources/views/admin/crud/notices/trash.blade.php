@extends('admin.layout.master')
@section('title', 'Notice Trash')
@section('content')
<div class="ltd-page-header"><div><span class="ltd-page-header__eyebrow">Announcements</span><h1>Notice Trash</h1></div><a href="{{ route('allNotice') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to Notices</a></div>
<div class="ltd-panel">
    <div class="mb-3"><h3 class="ltd-panel__title mb-1">Deleted Notices</h3><p class="text-muted mb-0">Restore an announcement or permanently remove it. Permanent deletion cannot be undone.</p></div>
    @include('admin.crud.partials.table-controls', ['items' => $notices, 'sortOptions' => ['deleted_at' => 'Date deleted', 'title' => 'English title', 'nepaliTitle' => 'Nepali title', 'status' => 'Previous status', 'id' => 'ID']])
    @if($notices->isEmpty())
        <div class="text-center py-5"><i class="fas fa-trash-restore fa-3x text-muted mb-3"></i><h4>Trash is empty</h4><p class="text-muted mb-0">Deleted notices will appear here.</p></div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>English title</th><th>Nepali title</th><th>Previous status</th><th>Deleted</th><th>Actions</th></tr></thead><tbody>
        @foreach($notices as $notice)<tr><td>{{ $notice->title }}</td><td>{{ $notice->nepaliTitle }}</td><td>{{ $notice->status }}</td><td>{{ $notice->deleted_at->format('M j, Y g:i A') }}</td><td>
            <form action="{{ route('restoreNotice', $notice->id) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success" type="submit"><i class="fas fa-trash-restore mr-1"></i>Restore</button></form>
            <form action="{{ route('forceDeleteNotice', $notice->id) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Permanently delete this notice? This cannot be undone.')"><i class="fas fa-times mr-1"></i>Delete permanently</button></form>
        </td></tr>@endforeach
        </tbody></table></div>@include('admin.crud.partials.table-pagination', ['items' => $notices])
    @endif
</div>
@endsection
