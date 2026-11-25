{{-- Bulk-delete bar for admin content lists. The row checkboxes live in the
     table below and point at this form with form="bulk-delete-form", so the
     bar can sit outside the table without nesting forms. The Delete button
     asks for confirmation with the browser's native confirm(), the same
     pattern as the per-row deletes. Without JavaScript the bar still works:
     the button stays enabled and the server validates the selection. --}}
<form id="bulk-delete-form" action="{{ $action }}" method="POST" class="d-flex align-items-center flex-wrap gap-2 mb-3">
    @csrf
    @method('DELETE')
    <span class="text-muted"><strong class="bulk-delete-count" aria-live="polite">0</strong> {{ __('selected') }}</span>
    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('{{ $confirm }}')"><i class="nav-icon fas fa-trash" aria-hidden="true"></i> {{ __('Delete selected') }}</button>
    @error('ids')<span class="text-danger">{{ $message }}</span>@enderror
</form>
