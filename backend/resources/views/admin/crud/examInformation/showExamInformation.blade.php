@extends('admin.layout.master')

@section('title', 'Exam Information')

@section('content')

    <div class="ltd-page-header">
        <div>
            <span class="ltd-page-header__eyebrow">Admin Panel</span>
            <h1>Exam Information</h1>
        </div>
        <ol class="ltd-breadcrumb">
            <li><a href="/admin">Home</a></li>
            <li>Exam Information</li>
        </ol>
    </div>

    <div class="ltd-panel">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h3 class="ltd-panel__title mb-0">Exam Information</h3>
            <a href="{{ URL::to('/admin/add-exam-information/') }}" class="btn btn-sm btn-success">
                <i class="nav-icon fas fa-plus"></i> Add Exam Information
            </a>
        </div>

        @include('admin.crud.partials.table-controls', [
            'items' => $examInformation,
            'filters' => ['language' => ['label' => 'Language category', 'options' => ['English' => 'English', 'Nepali' => 'Nepali']]],
            'sortOptions' => ['created_at' => 'Date added', 'name' => 'Title', 'id' => 'ID'],
        ])

        @include('admin.crud.partials.bulk-delete', [
            'action' => route('bulkDeleteExamInformation'),
            'confirm' => __('Delete the selected exam information? This cannot be undone.'),
        ])

        <div class="table-responsive">
            <table class="table table-hover align-middle ltd-list-table">
                <thead>
                <tr>
                    <th><input type="checkbox" class="bulk-delete-select-all" aria-label="{{ __('Select all on this page') }}"></th>
                    <th>ID</th>
                    <th>Exam Information</th>
                    <th>Language</th>
                    <th>PDF</th>
                    <th>Added by</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($examInformation as $row)
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
                                <a href="{{ URL::to('/admin/edit-exam-information/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                                <form action="{{ route('deleteExamInformation', $row->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this exam information?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $examInformation])
    </div>
@endsection
