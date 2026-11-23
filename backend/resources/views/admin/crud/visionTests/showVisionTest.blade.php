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
            <table class="table table-hover align-middle ltd-list-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                        <th>ID</th>
                        <th>Plate</th>
                        <th>Test</th>
                        <th>Added by</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visionTests as $row)
                        <tr>
                            <td class="ltd-col-narrow"><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                            <td class="ltd-id">{{ $row->id }}</td>
                            <td class="ltd-col-narrow">@if($row->getFirstMediaUrl())<img src="{{ $row->getFirstMediaUrl() }}" alt="Vision test {{ $row->testNumber }}" class="ltd-thumb">@else<span class="text-muted small">{{ __('No image') }}</span>@endif</td>
                            <td>
                                <span class="ltd-lead d-block" style="font-size:1.15rem">{{ $row->testNumber }}</span>
                                <span class="ltd-sub">{{ __('Vision test') }}</span>
                            </td>
                            <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                            <td>
                                <div class="ltd-actions">
                                    <a href="{{ route('editVisionTest', $row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                    <form action="{{ route('deleteVisionTest', $row->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this vision test?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $visionTests])
    </div>
@endsection
