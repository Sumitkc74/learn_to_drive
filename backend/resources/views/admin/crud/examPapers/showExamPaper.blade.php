@extends('admin.layout.master')

@section('title', 'Question Banks')

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Question Banks</h1>
        </div>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h2 class="ltd-panel__title mb-0">Question Banks</h2>
            <a href="{{ URL::to('/admin/add-exam-paper/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Question Bank
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $examPapers,
            'filters' => ['language' => ['label' => 'Language category', 'options' => ['English' => 'English', 'Nepali' => 'Nepali']]],
            'sortOptions' => ['created_at' => 'Date added', 'name' => 'Title', 'id' => 'ID'],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteExamPapers'),
            'confirm' => __('Delete the selected question banks? This cannot be undone.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle ltd-list-table">
                <th scope="col"ead>
                <tr>
                    <th scope="col"><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                    <th scope="col">ID</th>
                    <th scope="col">Question Bank</th>
                    <th scope="col">Language</th>
                    <th scope="col">PDF</th>
                    <th scope="col">Added by</th>
                    <th scope="col">Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($examPapers as $row)
                    <tr>
                        <td class="ltd-col-narrow"><input type="checkbox" name="ids[]" value="{{ $row->id }}" form="bulk-delete-form" class="bulk-delete-row" aria-label="{{ __('Select row') }}"></td>
                        <td class="ltd-id">{{ $row->id }}</td>
                        <td class="ltd-col-wide">
                            <span class="ltd-lead ltd-clamp-2" title="{{ $row->name }}">{{ $row->name }}</span>
                            @if($row->description)<span class="ltd-sub ltd-clamp-2" title="{{ $row->description }}">{{ $row->description }}</span>@endif
                        </td>
                        <td><span class="badge badge-info">{{ $row->language }}</span></td>
                        <td class="ltd-col-narrow">@if($pdf = $row->pdfMedia())<a href="{{ $pdf->getUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary" title="{{ __('Open PDF') }}"><i class="fas fa-file-pdf"></i> PDF</a>@else<span class="text-muted small">{{ __('PDF unavailable') }}</span>@endif</td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <div class="ltd-actions">
                                <a href="{{ URL::to('/admin/edit-exam-paper/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteExamPaper', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this question bank?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $examPapers])
    </div>
@endsection
