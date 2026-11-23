@extends('admin.layout.master')

@section('title', 'Traffic Signs')


@section('content_header')

@endsection

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Traffic Signs</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Traffic Signs</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Traffic Signs</h3>
            <a href="{{ URL::to('/admin/add-traffic-sign/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Traffic Sign
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $trafficSigns,
            'sortOptions' => ['created_at' => 'Date added', 'name' => 'English name', 'nepaliSignName' => 'Nepali name', 'id' => 'ID'],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteTrafficSigns'),
            'confirm' => __('Delete the selected traffic signs? This cannot be undone.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle ltd-list-table">
                <thead>
                <tr>
                    <th><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                    <th>ID</th>
                    <th>Sign</th>
                    <th>Traffic Sign</th>
                    <th>Description</th>
                    <th>Added by</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($trafficSigns as $row)
                    <tr>
                        <td class="ltd-col-narrow"><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                        <td class="ltd-id">{{ $row->id }}</td>
                        <td class="ltd-col-narrow">@if($row->getFirstMediaUrl())<img src="{{ $row->getFirstMediaUrl() }}" class="ltd-thumb" alt="{{ $row->name }}">@else<span class="text-muted small">{{ __('No image') }}</span>@endif</td>
                        <td class="ltd-col-wide">
                            <span class="ltd-lead d-block">{{ $row->name }}</span>
                            @if($row->nepaliSignName)<span class="ltd-sub">{{ $row->nepaliSignName }}</span>@endif
                        </td>
                        <td class="ltd-col-wide"><span class="ltd-clamp-2" title="{{ $row->description }}">{{ $row->description }}</span></td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <div class="ltd-actions">
                                <a href="{{ URL::to('/admin/edit-traffic-sign/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteTrafficSign', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this traffic sign?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $trafficSigns])
    </div>
@endsection
