@extends('admin.layout.master')

@section('title', 'Tutorials')

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Tutorials</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="ltd-panel__title mb-0">Tutorials</h2>
            <a href="{{ URL::to('/admin/add-tutorial/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Tutorial
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $tutorials,
            'sortOptions' => ['created_at' => 'Date added', 'title' => 'Title', 'id' => 'ID'],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteTutorials'),
            'confirm' => __('Delete the selected tutorials? This cannot be undone.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle ltd-list-table">
                <th scope="col"ead>
                <tr>
                    <th scope="col"><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                    <th scope="col">ID</th>
                    <th scope="col">Thumbnail</th>
                    <th scope="col">Tutorial</th>
                    <th scope="col">Video</th>
                    <th scope="col">Added by</th>
                    <th scope="col">Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($tutorials as $row)
                    <tr>
                        <td class="ltd-col-narrow"><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                        <td class="ltd-id">{{ $row->id }}</td>
                        <td class="ltd-col-narrow">@if($url=$row->thumbnailUrl())<img src="{{ $url }}" class="ltd-thumb ltd-thumb--wide" alt="">@else<span class="text-muted small">{{ __('No thumbnail') }}</span>@endif</td>
                        <td class="ltd-col-wide">
                            <span class="ltd-lead ltd-clamp-2" title="{{ $row->title }}">{{ $row->title }}</span>
                            @if($row->description)<span class="ltd-sub ltd-clamp-2" title="{{ $row->description }}">{{ $row->description }}</span>@endif
                        </td>
                        <td class="ltd-col-narrow">@if($row->videoLink)<a href="{{ $row->videoLink }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-danger" title="{{ $row->videoLink }}"><i class="fab fa-youtube"></i> {{ __('Watch') }}</a>@else<span class="text-muted small">{{ __('No video') }}</span>@endif</td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <div class="ltd-actions">
                                <a href="{{ URL::to('/admin/edit-tutorial/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteTutorial', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this tutorial?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $tutorials])
    </div>
@endsection
