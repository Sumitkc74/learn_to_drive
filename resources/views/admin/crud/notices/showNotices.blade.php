@extends('admin.layout.master')

@section('title', 'Notices')

@section('page-script')
    <style type='text/css'>

    </style>
@endsection

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

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Nepali Title</th>
                    <th>Nepali Description</th>
                    <th>Link</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($notices as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->title }}</td>
                        <td>{{ $row->description }}</td>
                        <td>{{ $row->nepaliTitle }}</td>
                        <td>{{ $row->nepaliDescription }}</td>
                        <td>{{ $row->link }}</td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-notice/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <a href="{{ URL::to('/admin/delete-notice/'.$row->id) }}" class="btn btn-sm btn-danger"><i class="nav-icon fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('page-script')
    <script type='text/javacript'>

    </script>
@endsection
