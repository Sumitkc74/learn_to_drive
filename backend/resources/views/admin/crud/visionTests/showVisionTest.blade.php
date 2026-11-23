@extends('admin.layout.master')

@section('title', 'Vision Tests')

@section('content')
    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Vision Tests</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Vision Tests</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Vision Tests</h3>
            <a href="{{ route('addVisionTest') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Test
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $visionTests,
            'sortOptions' => ['created_at' => 'Date added', 'testNumber' => 'Test number', 'id' => 'ID'],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteVisionTests'),
            'confirm' => __('Delete the selected vision tests? This cannot be undone.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                        <th>ID</th>
                        <th>Test Number</th>
                        <th>Image</th>
                        <th>Added by</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visionTests as $row)
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                            <td>{{ $row->id }}</td>
                            <td>{{ $row->testNumber }}</td>
                            <td><img src="{{ $row->getFirstMediaUrl() }}" alt="Vision test {{ $row->testNumber }}" width="100"></td>
                            <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                            <td>
                                <a href="{{ route('editVisionTest', $row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteVisionTest', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vision test?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $visionTests])
    </div>
@endsection
