@extends('admin.layout.master')

@section('title', 'Notices')

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Notices</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Notices</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Notices</h3>
            <div><a href="{{ route('noticeExport', ['format' => 'csv']) }}" class="btn btn-sm btn-outline-primary mr-1"><i class="fas fa-file-csv"></i> Download CSV</a><a href="{{ route('noticeExport', ['format' => 'xlsx']) }}" class="btn btn-sm btn-outline-primary mr-2"><i class="fas fa-file-excel"></i> Download Excel</a><a href="{{ route('noticeImport') }}" class="btn btn-sm btn-primary mr-2"><i class="fas fa-file-import"></i> Import from CSV / Excel</a><a href="{{ route('governmentNotices') }}" class="btn btn-sm btn-outline-primary mr-2"><i class="fas fa-landmark"></i> Government Imports</a><a href="{{ route('noticeTrash') }}" class="btn btn-sm btn-outline-secondary mr-2"><i class="fas fa-trash-restore"></i> Trash</a><a href="{{ URL::to('/admin/add-notice/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Notice
            </a></div>
        </div>

        <div class="mb-3"><a class="btn btn-sm btn-outline-primary" href="{{ route('allNotice') }}">Current notices</a> <a class="btn btn-sm btn-outline-secondary" href="{{ route('allNotice', ['status' => 'Archived']) }}">Archived notices</a></div>
        <p class="small text-muted">Expired notices are archived daily. Notices without an expiry date are archived {{ config('notices.archive_after_days') }} days after publication. Archived notices remain available here and are hidden from learners.</p>
        @include('admin.crud.partials.table-controls' , [
            'items' => $notices,
            'sortOptions' => ['created_at' => 'Date added', 'title' => 'English title', 'nepaliTitle' => 'Nepali title', 'status' => 'Status', 'publish_at' => 'Publish date', 'expires_at' => 'Expiration', 'id' => 'ID'],
            'filters' => ['status' => ['label' => 'Statuses', 'options' => ['Draft' => 'Draft', 'Published' => 'Published', 'Archived' => 'Archived']]],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteNotices'),
            'confirm' => __('Delete the selected notices? They will be moved to Trash.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle ltd-list-table">
                <thead>
                <tr>
                    <th><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                    <th>ID</th>
                    <th>Notice</th>
                    <th>Status</th>
                    <th>{{ __('Dates') }}</th>
                    <th>Added by</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($notices as $row)
                    <tr>
                        <td class="ltd-col-narrow"><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                        <td class="ltd-id">{{ $row->id }}</td>
                        <td class="ltd-col-wide">
                            <span class="ltd-lead ltd-clamp-2" title="{{ $row->title }}">{{ $row->title }}</span>
                            @if($row->nepaliTitle)<span class="ltd-sub ltd-clamp-1" title="{{ $row->nepaliTitle }}">{{ $row->nepaliTitle }}</span>@endif
                        </td>
                        <td><span class="badge badge-{{ $row->publication_state === 'Active' ? 'success' : ($row->publication_state === 'Scheduled' ? 'info' : ($row->publication_state === 'Draft' ? 'secondary' : 'dark')) }}">{{ $row->publication_state }}</span></td>
                        <td class="ltd-col-narrow">
                            <span class="d-block small">{{ __('Published') }}: {{ $row->publish_at?->format('M j, Y g:i A') ?? __('Immediately') }}</span>
                            <span class="d-block small text-muted">{{ __('Expires') }}: {{ $row->expires_at?->format('M j, Y g:i A') ?? __('No expiration') }}</span>
                        </td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <div class="ltd-actions">
                                <a href="{{ URL::to('/admin/edit-notice/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route($row->status === 'Archived' ? 'noticeUnarchive' : 'noticeArchive', $row->id) }}" method="POST" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ $row->status === 'Archived' ? 'Restore as draft' : 'Archive' }}</button></form>
                                <form action="{{ route('deleteNotice' , $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Move this notice to Trash?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $notices])
    </div>
@endsection
