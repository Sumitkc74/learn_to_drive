@extends('admin.layout.master')

@section('title', 'Notices')

@section('content')
    @include('admin.layout.flash')

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
            <a href="{{ URL::to('/admin/add-notice/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Notice
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $notices,
            'sortOptions' => ['created_at' => 'Date added', 'title' => 'English title', 'nepaliTitle' => 'Nepali title', 'status' => 'Status', 'publish_at' => 'Publish date', 'expires_at' => 'Expiration', 'id' => 'ID'],
            'filters' => ['status' => ['label' => 'Statuses', 'options' => ['Draft' => 'Draft', 'Published' => 'Published', 'Archived' => 'Archived']]],
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Nepali Title</th>
                    <th>Status</th>
                    <th>Publish date</th>
                    <th>Expires</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($notices as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->nepaliTitle }}</td>
                        <td><span class="badge badge-{{ $row->publication_state === 'Active' ? 'success' : ($row->publication_state === 'Scheduled' ? 'info' : ($row->publication_state === 'Draft' ? 'secondary' : 'dark')) }}">{{ $row->publication_state }}</span></td>
                        <td>{{ $row->publish_at?->format('M j, Y g:i A') ?? 'Immediately' }}</td>
                        <td>{{ $row->expires_at?->format('M j, Y g:i A') ?? 'No expiration' }}</td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-notice/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteNotice', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this notice?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $notices])
    </div>
@endsection
