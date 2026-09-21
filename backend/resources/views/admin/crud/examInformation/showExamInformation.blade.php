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

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Language</th>
                    <th>PDF</th>
                    <th>Added by</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                    @foreach ($examInformation as $row)
                    <tr>
                        <td>{{ $row->id }}</td>
                        <td>{{ $row->name }}</td>
                        <td>{{ $row->description }}</td>
                        <td>{{ $row->language }}</td>
                        <td>@if($pdf = $row->pdfMedia())<a href="{{ $pdf->getUrl() }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">Open PDF</a>@else<span class="text-muted">PDF unavailable</span>@endif</td>
                        <td>@include('admin.crud.partials.creator', ['record' => $row])</td>
                        <td>
                            <a href="{{ URL::to('/admin/edit-exam-information/'.$row->id) }}" class="btn btn-sm btn-info"><i class="nav-icon fas fa-edit"></i> Edit</a>
                            <form action="{{ route('deleteExamInformation', $row->id) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this exam information?')"><i class="nav-icon fas fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @include('admin.crud.partials.table-pagination', ['items' => $examInformation])
    </div>
@endsection
