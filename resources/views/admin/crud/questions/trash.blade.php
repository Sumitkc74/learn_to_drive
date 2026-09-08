@extends('admin.layout.master')
@section('title', 'Question Trash')
@section('content')
@include('admin.layout.flash')
<div class="ltd-page-header">
    <div><span class="ltd-page-header__eyebrow">Question Bank</span><h1>Trash</h1></div>
    <a href="{{ route('allQuestion') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-2"></i>Back to Questions</a>
</div>
<div class="ltd-panel">
    <div class="mb-3"><h3 class="ltd-panel__title mb-1">Deleted Questions</h3><p class="text-muted mb-0">Restore questions or permanently remove them. Permanent deletion cannot be undone.</p></div>
    @include('admin.crud.partials.table-controls', [
        'items' => $questions,
        'sortOptions' => ['deleted_at' => 'Date deleted', 'question' => 'Question', 'category' => 'Category', 'difficulty' => 'Difficulty', 'status' => 'Previous status', 'id' => 'ID'],
    ])
    @if($questions->isEmpty())
        <div class="text-center py-5"><i class="fas fa-trash-restore fa-3x text-muted mb-3"></i><h4>Trash is empty</h4><p class="text-muted mb-0">Deleted questions will appear here.</p></div>
    @else
        <div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>Question</th><th>Category</th><th>Difficulty</th><th>Deleted</th><th>Actions</th></tr></thead><tbody>
        @foreach($questions as $row)<tr><td>{{ $row->question }}</td><td>{{ $row->category }}</td><td>{{ $row->difficulty }}</td><td>{{ $row->deleted_at->format('M j, Y g:i A') }}</td><td>
            <form action="{{ route('restoreQuestion', $row->id) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-success" type="submit"><i class="fas fa-trash-restore mr-1"></i>Restore</button></form>
            <form action="{{ route('forceDeleteQuestion', $row->id) }}" method="POST" class="d-inline">@csrf @method('DELETE')<button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Permanently delete this question? This cannot be undone.')"><i class="fas fa-times mr-1"></i>Delete permanently</button></form>
        </td></tr>@endforeach
        </tbody></table></div>
        @include('admin.crud.partials.table-pagination', ['items' => $questions])
    @endif
</div>
@endsection
